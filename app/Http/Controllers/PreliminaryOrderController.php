<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreliminaryOrderController extends Controller
{
    /**
     * Display the preliminary orders queue.
     */
    public function index(): View
    {
        $kpis = [
            'total_preliminares' => Contract::where('status', Contract::STATUS_STR_PRELIMINAR)->count(),
            'hoy' => Contract::where('status', Contract::STATUS_STR_PRELIMINAR)
                ->whereDate('created_at', Carbon::today())
                ->count(),
            'convertidos' => Contract::where('source', Contract::SOURCE_WEB)
                ->whereIn('status', [Contract::STATUS_STR_BORRADOR, Contract::STATUS_STR_VIGENTE])
                ->count(),
            'monto_total' => (float) Contract::where('status', Contract::STATUS_STR_PRELIMINAR)->sum('total'),
        ];

        return view('admin.contracts.preliminary_orders', [
            'kpis' => $kpis,
            'signo' => $this->signo_pais(),
        ]);
    }

    /**
     * DataTables server-side feed for preliminary orders.
     */
    public function get(Request $request): JsonResponse
    {
        $query = Contract::with(['client', 'items'])
            ->where(function ($q) {
                $q->where('source', Contract::SOURCE_WEB)
                    ->orWhere('status', Contract::STATUS_STR_PRELIMINAR);
            });

        // Filter by status if provided
        if ($request->filled('status_filter')) {
            $query->where('status', $request->input('status_filter'));
        }

        // Filter by date range
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_evento', '>=', $request->input('fecha_inicio'));
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_evento', '<=', $request->input('fecha_fin'));
        }

        $query->orderByDesc('id');
        $signo = $this->signo_pais();

        return datatables()
            ->of($query)
            ->addColumn('codigo_preliminar', function (Contract $contract) {
                return '<div>
                            <span class="fw-bold text-dark fs-14">'.e($contract->contract_number).'</span>
                            <div class="mt-1"><span class="badge bg-light text-primary border"><i class="ri-barcode-line me-1"></i>'.e($contract->tracking_code).'</span></div>
                        </div>';
            })
            ->addColumn('cliente_info', function (Contract $contract) {
                $client = $contract->client;
                $name = $client ? e($client->nombres) : 'Cliente Web';
                $phone = $client && $client->telefono ? e($client->telefono) : '-';

                return '<div>
                            <div class="fw-bold text-dark">'.$name.'</div>
                            <div class="small text-muted"><i class="ri-phone-line me-1"></i>'.$phone.'</div>
                        </div>';
            })
            ->editColumn('fecha_evento', function (Contract $contract) {
                return $contract->fecha_evento
                    ? '<span class="badge bg-light text-dark border"><i class="ri-calendar-line me-1 text-primary"></i>'.$contract->fecha_evento->format('d/m/Y').'</span>'
                    : '-';
            })
            ->editColumn('guest_count', function (Contract $contract) {
                $count = $contract->guest_count ?: ($contract->items->max('cantidad') ?: '-');

                return '<span class="badge bg-info-subtle text-info border">'.$count.' pers.</span>';
            })
            ->editColumn('total', function (Contract $contract) use ($signo) {
                return '<div class="fw-bold text-dark fs-14 text-end">'.$signo.' '.number_format((float) $contract->total, 2).'</div>';
            })
            ->addColumn('status_badge', function (Contract $contract) {
                return match ($contract->status) {
                    Contract::STATUS_STR_PRELIMINAR => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="ri-time-line me-1"></i>Preliminar Web</span>',
                    Contract::STATUS_STR_BORRADOR => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="ri-draft-line me-1"></i>Borrador CON</span>',
                    Contract::STATUS_STR_VIGENTE => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="ri-checkbox-circle-line me-1"></i>Vigente</span>',
                    default => '<span class="badge bg-secondary-subtle text-secondary border">'.e($contract->status).'</span>',
                };
            })
            ->addColumn('whatsapp_action', function (Contract $contract) {
                $phone = $contract->client && $contract->client->telefono
                    ? preg_replace('/[^\d]/', '', (string) $contract->client->telefono)
                    : null;

                if (! $phone) {
                    return '-';
                }

                if (str_starts_with($phone, '51') && strlen($phone) === 11) {
                    $phone = substr($phone, 2);
                }

                $clientName = $contract->client ? $contract->client->nombres : 'Estimado cliente';
                $waText = urlencode("¡Hola {$clientName}! Te saluda el equipo de Shipibo Coctelería respecto a tu solicitud online {$contract->tracking_code}. Quisiéramos coordinar los detalles de tu evento.");
                $waUrl = "https://wa.me/51{$phone}?text={$waText}";

                return '<a href="'.$waUrl.'" target="_blank" class="btn btn-sm btn-outline-success fw-bold d-inline-flex align-items-center" title="Contactar por WhatsApp">
                            <i class="ri-whatsapp-line me-1"></i> Contactar
                        </a>';
            })
            ->addColumn('acciones', function (Contract $contract) {
                $btnConvert = $contract->status === Contract::STATUS_STR_PRELIMINAR
                    ? '<button type="button" class="btn btn-sm btn-success btn-convert-draft" data-id="'.$contract->id.'" data-code="'.e($contract->contract_number).'" title="Convertir a Borrador CON">
                            <i class="ri-arrow-right-up-line me-1"></i> Convertir a Borrador
                       </button>'
                    : '<span class="text-muted small"><i class="ri-check-line me-1"></i>Ya formalizado</span>';

                return '<div class="d-flex gap-1 justify-content-center align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-info btn-view-detail" data-id="'.$contract->id.'" title="Ver Detalle de Ítems">
                                <i class="ri-eye-line"></i>
                            </button>
                            '.$btnConvert.'
                        </div>';
            })
            ->rawColumns(['codigo_preliminar', 'cliente_info', 'fecha_evento', 'guest_count', 'total', 'status_badge', 'whatsapp_action', 'acciones'])
            ->toJson();
    }

    /**
     * Retrieve order details with items for modal.
     */
    public function detail(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $contract = Contract::with(['client', 'items'])->find($id);

        if (! $contract) {
            return response()->json([
                'status' => false,
                'msg' => 'Pedido preliminar no encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'contract' => $contract,
        ]);
    }

    /**
     * Convert preliminary order to official draft contract (CON-YYYY-XXXX).
     */
    public function convertToDraft(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $contract = Contract::with('client')->find($id);

        if (! $contract) {
            return response()->json([
                'status' => false,
                'msg' => 'Pedido no encontrado.',
            ], 404);
        }

        if ($contract->status !== Contract::STATUS_STR_PRELIMINAR) {
            return response()->json([
                'status' => false,
                'msg' => 'Este pedido ya no está en estado preliminar.',
            ], 422);
        }

        // Generate next formal CON-YYYY-XXXX contract number without modifying existing CON sequence
        $year = (int) date('Y');
        $latest = Contract::where('contract_number', 'LIKE', "CON-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $correlativo = ($latest && preg_match('/CON-\d{4}-(\d+)/', (string) $latest->contract_number, $matches))
            ? intval($matches[1]) + 1
            : 1;

        $newContractNumber = sprintf('CON-%s-%04d', $year, $correlativo);
        $clientName = $contract->client ? $contract->client->nombres : 'CLIENTE';

        $contract->contract_number = $newContractNumber;
        $contract->status = Contract::STATUS_STR_BORRADOR;
        $contract->estado = Contract::STATUS_DRAFT;
        $contract->title = 'CONTRATO DE PRESTACIÓN DE SERVICIOS - '.$clientName;
        $contract->observaciones = $contract->observaciones." | Convertido desde pedido web preliminar ({$contract->tracking_code}).";
        $contract->save();

        return response()->json([
            'status' => true,
            'msg' => "¡Pedido convertido exitosamente al Contrato Borrador {$newContractNumber}!",
            'new_number' => $newContractNumber,
            'contract_id' => $contract->id,
        ]);
    }
}
