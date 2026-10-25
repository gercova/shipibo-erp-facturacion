<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackageValidate;
use App\Models\CocktailMenuItem;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackagePriceTier;
use App\Models\Product;
use App\Services\Packages\Exceptions\GuestOutOfRangeException;
use App\Services\Packages\Exceptions\TierOverlapException;
use App\Services\Packages\PackagePriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(
        protected PackagePriceCalculator $calculator
    ) {}

    /**
     * Render the packages index view.
     */
    public function index(): View
    {
        $kpis = [
            'total' => Package::count(),
            'activos' => Package::where('active', true)->count(),
            'escalonados' => Package::where('price_mode', 'tiered')->count(),
            'por_persona' => Package::where('price_mode', 'per_guest')->count(),
        ];
        // Available products: Services (opcion=2) and rentable physical items
        $products = Product::where('opcion', 2)
            ->orWhere('rentable', true)
            ->select('id', 'descripcion', 'precio_venta', 'opcion', 'rentable')
            ->orderBy('descripcion')
            ->get();

        // Active cocktails for selection
        $cocktails = CocktailMenuItem::with('category')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.packages.index', [
            'kpis' => $kpis,
            'products' => $products,
            'cocktails' => $cocktails,
            'signo' => $this->signo_pais(),
        ]);
    }

    /**
     * DataTables server-side feed for packages.
     */
    public function get(): JsonResponse {
        $packages   = Package::with(['priceTiers', 'items'])->orderBy('id', 'asc');
        $signo      = $this->signo_pais();

        return datatables()
            ->of($packages)
            ->addColumn('order_num', function (Package $package) {
                $num        = $package->order_num ?: sprintf('%02d', $package->id);
                $color      = $package->color ?: '#275c34';
                $glasses    = $package->badge_glasses ?: 'Vasos';
                return '<div class="text-center">
                            <span class="badge px-2 py-1 fs-12 fw-bold" style="background-color: '.$color.'; color: #ffffff;">'.$num.'</span>
                            <div class="small fw-semibold mt-1" style="font-size: 0.72rem; color: #555;">'.e($glasses).'</div>
                        </div>';
            })
            ->addColumn('imagen_thumb', function (Package $package) {
                return '<div class="text-center">
                            <img src="'.$package->image_url.'" alt="'.e($package->name).'" class="rounded-3 shadow-sm border p-1 bg-white" style="width: 48px; height: 48px; object-fit: contain;">
                        </div>';
            })
            ->editColumn('name', function (Package $package) {
                $color      = $package->color ?: '#275c34';
                $tagline    = $package->tagline ? '<div class="text-muted small text-truncate" style="max-width: 250px;" title="'.e($package->tagline).'">'.e($package->tagline).'</div>' : '';
                $modeBadges = [
                    'tiered'    => '<span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.7rem;"><i class="ri-stack-line me-1"></i>Escalonado</span>',
                    'fixed'     => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 0.7rem;"><i class="ri-lock-line me-1"></i>Fijo</span>',
                    'per_guest' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;"><i class="ri-user-line me-1"></i>Por Persona</span>',
                ];
                $badge          = $modeBadges[$package->price_mode] ?? '';
                $barmanBadge    = $package->is_barman ? '<span class="badge bg-warning text-dark border border-warning-subtle ms-1" style="font-size: 0.7rem;">Servicio Barman</span>' : '';
                return '<div>
                            <div class="fw-bold fs-14" style="color: '.$color.';">'.e($package->name).$barmanBadge.'</div>
                            '.$tagline.'
                            <div class="mt-1">'.$badge.'</div>
                        </div>';
            })
            ->addColumn('capacity', function (Package $package) {
                $ideal      = $package->ideal_capacity ? '<div class="text-success small fw-semibold"><i class="ri-check-line"></i> '.e($package->ideal_capacity).'</div>' : '';
                $default    = $package->default_guests ? '<small class="text-muted d-block">Sugerido: <strong>'.$package->default_guests.'</strong> inv.</small>' : '';
                return '<div>
                            <span class="badge bg-light text-dark border"><i class="ri-group-line me-1 text-primary"></i>'.e($package->guest_range_label).'</span>
                            '.$ideal.'
                            '.$default.'
                        </div>';
            })
            ->addColumn('colors', function (Package $package) {
                $color      = $package->color ?: '#275c34';
                $lightBg    = $package->light_bg ?: '#e9edea';
                return '<div class="d-flex align-items-center gap-1">
                            <span class="d-inline-block rounded-circle border shadow-sm" style="width: 20px; height: 20px; background-color: '.$color.';" title="Primario: '.$color.'"></span>
                            <span class="d-inline-block rounded-circle border shadow-sm" style="width: 20px; height: 20px; background-color: '.$lightBg.';" title="Fondo: '.$lightBg.'"></span>
                            <span class="font-monospace small text-muted ms-1" style="font-size: 0.72rem;">'.$color.'</span>
                        </div>';
            })
            ->addColumn('pricing_summary', function (Package $package) use ($signo) {
                if ($package->price_mode === 'tiered') {
                    $tiersCount = $package->priceTiers->count();
                    if ($tiersCount === 0) {
                        return '<span class="text-warning small"><i class="ri-alert-line me-1"></i>Sin tramos</span>';
                    }
                    $minPrice   = $package->priceTiers->min('price');
                    $maxPrice   = $package->priceTiers->max('price');
                    $priceLabel = $minPrice == $maxPrice
                        ? "{$signo} ".number_format((float) $minPrice, 2)
                        : "{$signo} ".number_format((float) $minPrice, 2).' - '.number_format((float) $maxPrice, 2);
                    return '<div>
                                <div class="fw-bold text-success">'.$priceLabel.'</div>
                                <small class="text-muted">'.$tiersCount.' tramo(s) de invitados</small>
                            </div>';
                }

                if ($package->price_mode === 'per_guest') {
                    return '<span class="fw-bold text-primary">'.$signo.' '.number_format((float) $package->base_price, 2).' <small class="text-muted fw-normal">/ inv.</small></span>';
                }

                return '<span class="fw-bold text-dark">'.$signo.' '.number_format((float) $package->base_price, 2).'</span>';
            })
            ->addColumn('specifications', function (Package $package) {
                $features = $package->features ?: [];
                if (empty($features)) {
                    return '<span class="text-muted small fst-italic">Sin especificar</span>';
                }
                $firstTwo = array_slice($features, 0, 2);
                $html     = '<div class="d-flex flex-column gap-1" style="font-size: 0.74rem;">';
                foreach ($firstTwo as $feat) {
                    $html .= '<span class="text-truncate text-dark" style="max-width: 220px;" title="'.e($feat).'"><i class="ri-check-line text-success me-1"></i>'.e($feat).'</span>';
                }
                if (count($features) > 2) {
                    $html .= '<span class="text-muted small fw-semibold">+ '.(count($features) - 2).' ítems más</span>';
                }
                $html .= '</div>';
                return $html;
            })
            ->editColumn('active', function (Package $package) {
                $checked = $package->active ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input btn-toggle-status" type="checkbox" data-id="'.$package->id.'" '.$checked.'>
                        </div>';
            })
            ->addColumn('acciones', function (Package $package) {
                return '<div class="d-flex gap-1 justify-content-center">
                            <a href="'.route('public.orders.index').'#section-packages" target="_blank" class="btn btn-sm btn-outline-success" title="Ver en Web /pedido">
                                <i class="ri-external-link-line"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-info btn-test-calc" data-id="'.$package->id.'" data-name="'.e($package->name).'" title="Simular Cotización">
                                <i class="ri-calculator-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-package" data-id="'.$package->id.'" title="Editar Paquete">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-package" data-id="'.$package->id.'" data-name="'.e($package->name).'" title="Eliminar Paquete">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>';
            })
            ->rawColumns(['order_num', 'imagen_thumb', 'name', 'capacity', 'colors', 'pricing_summary', 'specifications', 'active', 'acciones'])
            ->toJson();
    }

    public function detail(Request $request): JsonResponse {
        $id         = (int) $request->input('id');
        $package    = Package::with([
            'items.product',
            'items.cocktail.category',
            'priceTiers',
        ])->find($id);

        if (! $package) {
            return response()->json([
                'status'    => false,
                'msg'       => 'Paquete no encontrado.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'package' => array_merge($package->toArray(), [
                'image_url'         => $package->image_url,
                'guest_range_label' => $package->guest_range_label,
            ]),
        ]);
    }

    public function store(PackageValidate $request): JsonResponse {
        return $this->save($request);
    }

    public function save(PackageValidate $request): JsonResponse {
        // El FormRequest ya validó; si falla, responde 422 con el formato JSON.
        $validated  = $request->validated();
        $id         = (int) $request->input('id', 0);

        try {
            DB::beginTransaction();

            $package = $id > 0 ? Package::find($id) : new Package;

            if (! $package) {
                return response()->json([
                    'status'    => false,
                    'msg'       => 'Paquete no encontrado.',
                ], 404);
            }

            // ---- Campos validados (mass-assignment controlado) ----
            $package->fill([
                'name' => mb_strtoupper(trim($validated['name'])),
                'order_num' => isset($validated['order_num']) ? trim($validated['order_num']) : null,
                'badge_glasses' => isset($validated['badge_glasses']) ? trim($validated['badge_glasses']) : null,
                'tagline' => isset($validated['tagline']) ? trim($validated['tagline']) : null,
                'color' => $validated['color'] ?? '#275c34',
                'light_bg' => $validated['light_bg'] ?? '#e9edea',
                'price_mode' => $validated['price_mode'],
                'min_guests' => (int) $validated['min_guests'],
                'max_guests' => $validated['max_guests'] ?? null,
                'ideal_capacity' => isset($validated['ideal_capacity']) ? trim($validated['ideal_capacity']) : null,
                'default_guests' => $validated['default_guests'] ?? null,
                'base_price' => isset($validated['base_price']) ? round((float) $validated['base_price'], 2) : 0.00,
                'description' => trim((string) ($validated['description'] ?? '')),
            ]);

            // Booleanos: no forman parte de $validated si vienen como "on"/1, pero
            // como ya agregamos la regla, están en validated. Usamos fallback por si no vienen.
            $package->active = (bool) ($validated['active'] ?? true);
            $package->is_barman = (bool) ($validated['is_barman'] ?? false);

            // ---- Features (array | json | texto multilínea) ----
            if ($request->has('features')) {
                $raw = $request->input('features');
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $package->features = is_array($decoded)
                        ? $decoded
                        : array_values(array_filter(array_map('trim', explode("\n", $raw))));
                } elseif (is_array($raw)) {
                    $package->features = array_values(array_filter(array_map('trim', $raw)));
                }
            }

            // ---- Imagen: preset o subida ----
            if (! empty($validated['image_preset']) && ! $request->hasFile('image')) {
                $package->image = trim($validated['image_preset']);
            }

            if ($request->hasFile('image')) {
                $dir = public_path('files/packages');
                if (! File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true, true);
                }

                if (! empty($package->image) && ! str_starts_with($package->image, 'img/')) {
                    $old = $dir.'/'.$package->image;
                    if (File::exists($old)) {
                        File::delete($old);
                    }
                }

                $file = $request->file('image');
                $filename = 'pkg_'.time().'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
                $file->move($dir, $filename);
                $package->image = $filename;
            }

            $package->save();

            // ---- Tiers ----
            if ($package->price_mode === 'tiered') {
                $tiersData = $request->input('tiers', []);
                if (is_string($tiersData)) {
                    $tiersData = json_decode($tiersData, true) ?: [];
                }

                PackagePriceTier::where('package_id', $package->id)->delete();

                foreach ($tiersData as $tier) {
                    if (empty($tier['from_guests']) || empty($tier['to_guests']) || ! isset($tier['price'])) {
                        continue;
                    }
                    PackagePriceTier::create([
                        'package_id' => $package->id,
                        'from_guests' => (int) $tier['from_guests'],
                        'to_guests' => (int) $tier['to_guests'],
                        'price' => round((float) $tier['price'], 2),
                        'price_type' => (string) ($tier['price_type'] ?? 'total'),
                    ]);
                }

                $loadedPackage = Package::with('priceTiers')->find($package->id);
                $this->calculator->validateGuestRanges($loadedPackage, $package->min_guests);
            }

            // ---- Items ----
            $itemsData = $request->input('items', []);
            if (is_string($itemsData)) {
                $itemsData = json_decode($itemsData, true) ?: [];
            }

            PackageItem::where('package_id', $package->id)->delete();

            foreach ($itemsData as $item) {
                PackageItem::create([
                    'package_id' => $package->id,
                    'product_id' => ! empty($item['product_id']) ? (int) $item['product_id'] : null,
                    'cocktail_id' => ! empty($item['cocktail_id']) ? (int) $item['cocktail_id'] : null,
                    'quantity' => isset($item['quantity']) ? round((float) $item['quantity'], 2) : 1.00,
                    'is_per_guest' => ! empty($item['is_per_guest']),
                    'is_mandatory' => ! isset($item['is_mandatory']) || (bool) $item['is_mandatory'],
                    'unit_price' => isset($item['unit_price']) && $item['unit_price'] !== ''
                        ? round((float) $item['unit_price'], 2) : null,
                    'notes' => ! empty($item['notes']) ? trim((string) $item['notes']) : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'msg' => $id > 0
                    ? 'Paquete actualizado correctamente.'
                    : 'Paquete registrado exitosamente.',
                'package_id' => $package->id,
            ]);

        } catch (TierOverlapException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'msg' => 'Error de validación de tramos: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'msg' => 'Ocurrió un error al guardar el paquete: '.$e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $package = Package::find($id);

        if (! $package) {
            return response()->json([
                'status' => false,
                'msg' => 'Paquete no encontrado.',
            ], 404);
        }

        if (! empty($package->image)) {
            $path = public_path('files/packages/'.$package->image);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $package->delete();

        return response()->json([
            'status' => true,
            'msg' => 'Paquete eliminado correctamente.',
        ]);
    }

    public function toggle_status(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $package = Package::find($id);

        if (! $package) {
            return response()->json([
                'status' => false,
                'msg' => 'Paquete no encontrado.',
            ], 404);
        }

        $package->active = ! $package->active;
        $package->save();

        return response()->json([
            'status' => true,
            'msg' => $package->active ? 'Paquete activado.' : 'Paquete desactivado.',
            'active' => $package->active,
        ]);
    }

    public function calculate_price(Request $request): JsonResponse
    {
        $packageId = (int) $request->input('package_id');
        $guests = (int) $request->input('guests');
        $extras = (array) $request->input('extras', []);

        try {
            $result = $this->calculator->calculate($packageId, $guests, $extras);

            return response()->json([
                'status' => true,
                'data' => $result,
            ]);
        } catch (GuestOutOfRangeException|TierOverlapException $e) {
            return response()->json([
                'status' => false,
                'msg' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'msg' => 'Error al calcular cotización: '.$e->getMessage(),
            ], 500);
        }
    }
}
