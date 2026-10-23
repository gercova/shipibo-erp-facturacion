<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulatePublicOrderRequest;
use App\Http\Requests\StorePublicOrderRequest;
use App\Models\Business;
use App\Models\Client;
use App\Models\CocktailMenuCategory;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\IdentityDocumentType;
use App\Models\Package;
use App\Services\Packages\Exceptions\GuestOutOfRangeException;
use App\Services\Packages\Exceptions\TierOverlapException;
use App\Services\Packages\PackagePriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicOrderController extends Controller
{
    public function __construct(
        protected PackagePriceCalculator $calculator
    ) {}

    /**
     * Display public digital menu, packages, and interactive order form.
     */
    public function index(): View
    {
        $packages = Package::with(['items.product', 'items.cocktail.category', 'priceTiers'])
            ->where('active', true)
            ->orderBy('min_guests')
            ->get();

        $cocktailCategories = CocktailMenuCategory::with(['cocktails' => function ($q) {
            $q->where('activo', true)->orderBy('orden');
        }])
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $business = Business::first();
        $signo = $business && ! empty($business->simbolo) ? $business->simbolo : 'S/';

        // Versioned consent text for Law 29733 (Personal Data Protection)
        $consentVersion = config('shipibo.consent_version', 'v1.0');
        $consentDictionary = config('shipibo.consent_text_law_29733', []);
        $consentText = is_array($consentDictionary)
            ? ($consentDictionary[$consentVersion] ?? reset($consentDictionary))
            : (string) $consentDictionary;

        return view('public.orders.index', [
            'packages' => $packages,
            'cocktailCategories' => $cocktailCategories,
            'business' => $business,
            'signo' => $signo,
            'consentText' => $consentText,
            'consentVersion' => $consentVersion,
        ]);
    }

    /**
     * Recalculate price dynamically on server without saving.
     */
    public function simulate(SimulatePublicOrderRequest $request): JsonResponse {
        try {
            $packageId  = (int) $request->input('package_id');
            $guests     = (int) $request->input('guests');
            $extras     = (array) $request->input('extras', []);
            $result     = $this->calculator->calculate($packageId, $guests, $extras);
            return response()->json([
                'status' => true,
                'data'   => $result,
            ]);
        } catch (GuestOutOfRangeException|TierOverlapException $e) {
            return response()->json([
                'status' => false,
                'msg'    => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'msg' => 'No se pudo simular el precio del paquete: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store preliminary order (Law 29733 compliant, minimal PII, server-side pricing).
     */
    public function store(StorePublicOrderRequest $request): JsonResponse
    {
        // 1. Normalization
        $rawPhone = preg_replace('/[^\d]/', '', (string) $request->input('telefono'));
        $normalizedPhone = str_starts_with($rawPhone, '51') && strlen($rawPhone) === 11
            ? substr($rawPhone, 2)
            : $rawPhone;

        $name = mb_strtoupper(trim(strip_tags((string) $request->input('nombre'))));
        $packageId = (int) $request->input('package_id');
        $guests = (int) $request->input('guests');
        $eventDate = (string) $request->input('fecha_evento');
        $eventType = trim(strip_tags((string) $request->input('event_type', 'Celebración')));
        $extras = (array) $request->input('extras', []);

        try {
            DB::beginTransaction();

            // 4. Server-side Calculation (ignoring any amounts sent in request)
            $calc = $this->calculator->calculate($packageId, $guests, $extras);
            $package = Package::findOrFail($packageId);

            // 5. Client Deduplication by normalized phone (Doc Type 0: Sin Documento)
            $docType0 = IdentityDocumentType::where('codigo', '0')->value('id');
            if (! $docType0) {
                $docType0 = IdentityDocumentType::value('id') ?? 1;
            }

            $client = Client::where('telefono', $normalizedPhone)->first();
            if ($client) {
                // Reuse existing client record and update contact name
                $client->nombres = $name;
                $client->save();
            } else {
                $client = Client::create([
                    'iddoc' => $docType0,
                    'nro_documento' => 'SD-'.$normalizedPhone,
                    'nombres' => $name,
                    'telefono' => $normalizedPhone,
                    'direccion' => 'Tarapoto, San Martín',
                ]);
            }

            // 6. Preliminary Contract Creation (PRE-YYYY-XXXX, status='preliminar', source='web')
            $preliminaryNumber = Contract::generatePreliminaryNumber();
            $trackingCode = Contract::generateTrackingCode();
            $business = Business::first();

            $contract = Contract::create([
                'contract_number' => $preliminaryNumber,
                'title' => 'PEDIDO WEB PRELIMINAR - '.$package->name,
                'idcliente' => $client->id,
                'provider_name' => $business ? $business->razon_social : 'MYTEMS E.I.R.L.',
                'provider_document' => $business ? $business->ruc : '20600000000',
                'provider_representative' => 'Gerencia General',
                'fecha_evento' => $eventDate,
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->addDays(7)->toDateString(),
                'subtotal' => $calc['subtotal'],
                'igv' => $calc['igv'],
                'total' => $calc['total'],
                'moneda' => 'PEN',
                'observaciones' => "Pedido digital web. Paquete: {$package->name}. Invitados: {$guests}. Tipo evento: {$eventType}.",
                'estado' => Contract::STATUS_DRAFT,
                'status' => Contract::STATUS_STR_PRELIMINAR,
                'source' => Contract::SOURCE_WEB,
                'tracking_code' => $trackingCode,
                'guest_count' => $guests,
                'event_type' => $eventType,
                'consent_version' => config('shipibo.consent_version', 'v1.0'),
                'consent_accepted_at' => now(),
            ]);

            // 7. Save Contract Items
            foreach ($calc['breakdown'] as $line) {
                ContractItem::create([
                    'contract_id' => $contract->id,
                    'idproducto' => null,
                    'descripcion' => (string) $line['name'].(! empty($line['notes']) ? ' ('.$line['notes'].')' : ''),
                    'cantidad' => $line['quantity'],
                    'precio_unitario' => $line['unit_price'],
                    'subtotal' => $line['total'],
                ]);
            }

            DB::commit();

            // 8. Safe Logging (NO PII)
            Log::info('Nuevo pedido preliminar web registrado', [
                'contract_id' => $contract->id,
                'tracking_code' => $trackingCode,
                'package_id' => $packageId,
                'guests' => $guests,
                'total' => $calc['total'],
            ]);

            return response()->json([
                'status' => true,
                'msg' => '¡Tu solicitud ha sido recibida con éxito! Nuestro equipo coordinará contigo.',
                'tracking_code' => $trackingCode,
                'redirect_url' => route('public.orders.confirmation', ['tracking_code' => $trackingCode]),
            ]);
        } catch (GuestOutOfRangeException|TierOverlapException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'msg' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'msg' => 'Error al procesar su pedido web: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Client confirmation screen displaying tracking code and next steps.
     * Strictly avoids exposing legal clauses, internal markups or other clients data.
     */
    public function confirmation(string $tracking_code): View
    {
        $contract = Contract::with(['items', 'client'])
            ->where('tracking_code', $tracking_code)
            ->firstOrFail();

        $business = Business::first();
        $advisorPhone = $business && ! empty($business->telefono) ? preg_replace('/[^\d]/', '', $business->telefono) : '942691689';
        if (str_starts_with($advisorPhone, '51') && strlen($advisorPhone) === 11) {
            $advisorPhone = substr($advisorPhone, 2);
        }

        // WhatsApp direct link with tracking code without sharing sensitive PII
        $waGreeting = "¡Hola Shipibo Coctelería! Acabo de registrar mi cotización online con código {$contract->tracking_code} para mi evento del {$contract->fecha_evento->format('d/m/Y')}. Me gustaría recibir más detalles.";
        $waUrl = 'https://wa.me/51'.$advisorPhone.'?text='.urlencode($waGreeting);

        return view('public.orders.confirmation', [
            'contract' => $contract,
            'business' => $business,
            'waUrl' => $waUrl,
            'advisorPhone' => $advisorPhone,
        ]);
    }
}
