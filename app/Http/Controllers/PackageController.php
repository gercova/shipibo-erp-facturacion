<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Validator;
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
    public function get(): JsonResponse
    {
        $packages = Package::with(['priceTiers', 'items'])
            ->orderByDesc('id');

        $signo = $this->signo_pais();

        return datatables()
            ->of($packages)
            ->addColumn('imagen_thumb', function (Package $package) {
                return '<img src="'.$package->image_url.'" alt="'.e($package->name).'" class="rounded shadow-sm" style="width: 52px; height: 52px; object-fit: cover;">';
            })
            ->editColumn('name', function (Package $package) {
                $modeBadges = [
                    'tiered' => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="ri-stack-line me-1"></i>Escalonado (Tiers)</span>',
                    'fixed' => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="ri-lock-line me-1"></i>Precio Fijo</span>',
                    'per_guest' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="ri-user-line me-1"></i>Por Persona</span>',
                ];
                $badge = $modeBadges[$package->price_mode] ?? '';

                return '<div>
                            <div class="fw-bold text-dark fs-14">'.e($package->name).'</div>
                            <div class="mt-1">'.$badge.'</div>
                        </div>';
            })
            ->addColumn('guest_range', function (Package $package) {
                return '<span class="badge bg-light text-dark border"><i class="ri-group-line me-1 text-primary"></i>'.e($package->guest_range_label).'</span>';
            })
            ->addColumn('pricing_summary', function (Package $package) use ($signo) {
                if ($package->price_mode === 'tiered') {
                    $tiersCount = $package->priceTiers->count();
                    if ($tiersCount === 0) {
                        return '<span class="text-warning small"><i class="ri-alert-line me-1"></i>Sin tramos</span>';
                    }
                    $minPrice = $package->priceTiers->min('price');
                    $maxPrice = $package->priceTiers->max('price');
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
            ->addColumn('items_count', function (Package $package) {
                $count = $package->items->count();

                return '<span class="badge rounded-pill bg-light text-secondary border">'.$count.' ítems</span>';
            })
            ->editColumn('active', function (Package $package) {
                $checked = $package->active ? 'checked' : '';

                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input btn-toggle-status" type="checkbox" data-id="'.$package->id.'" '.$checked.'>
                        </div>';
            })
            ->addColumn('acciones', function (Package $package) {
                return '<div class="d-flex gap-1 justify-content-center">
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
            ->rawColumns(['imagen_thumb', 'name', 'guest_range', 'pricing_summary', 'items_count', 'active', 'acciones'])
            ->toJson();
    }

    /**
     * Retrieve package details with relations for editing or simulation.
     */
    public function detail(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $package = Package::with([
            'items.product',
            'items.cocktail.category',
            'priceTiers',
        ])->find($id);

        if (! $package) {
            return response()->json([
                'status' => false,
                'msg' => 'Paquete no encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'package' => array_merge($package->toArray(), [
                'image_url' => $package->image_url,
                'guest_range_label' => $package->guest_range_label,
            ]),
        ]);
    }

    /**
     * Store or update a package.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->save($request);
    }

    /**
     * Save package handler (compatible with both 'save' and 'store' routes).
     */
    public function save(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $rules = [
            'name' => 'required|string|max:150',
            'price_mode' => 'required|in:fixed,per_guest,tiered',
            'min_guests' => 'required|integer|min:1',
            'max_guests' => 'nullable|integer|gte:min_guests',
            'base_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'name.required' => 'El nombre del paquete es obligatorio.',
            'price_mode.required' => 'Debe seleccionar una modalidad de precio.',
            'min_guests.required' => 'Debe especificar el número mínimo de invitados.',
            'max_guests.gte' => 'El máximo de invitados debe ser mayor o igual al mínimo.',
            'image.image' => 'El archivo debe ser una imagen válida (JPG, PNG, WEBP).',
            'image.max' => 'La imagen no puede exceder los 4MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $package = $id > 0 ? Package::find($id) : new Package;

            if (! $package) {
                return response()->json([
                    'status' => false,
                    'msg' => 'Paquete no encontrado.',
                ], 404);
            }

            $package->name = mb_strtoupper(trim((string) $request->input('name')));
            $package->price_mode = (string) $request->input('price_mode');
            $package->min_guests = (int) $request->input('min_guests');
            $package->max_guests = $request->filled('max_guests') ? (int) $request->input('max_guests') : null;
            $package->base_price = $request->filled('base_price') ? round((float) $request->input('base_price'), 2) : 0.00;
            $package->description = trim((string) $request->input('description'));
            $package->active = $request->boolean('active', true);

            // Handle image upload
            if ($request->hasFile('image')) {
                $dir = public_path('files/packages');
                if (! File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true, true);
                }

                if (! empty($package->image)) {
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

            // Save Tiers (if price_mode === 'tiered')
            if ($package->price_mode === 'tiered') {
                $tiersData = $request->input('tiers', []);
                if (is_string($tiersData)) {
                    $tiersData = json_decode($tiersData, true) ?: [];
                }

                // Delete existing tiers
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

                // Validate tiers for overlaps
                $loadedPackage = Package::with('priceTiers')->find($package->id);
                $this->calculator->validateGuestRanges($loadedPackage, $package->min_guests);
            }

            // Save Items
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
                    'unit_price' => isset($item['unit_price']) && $item['unit_price'] !== '' ? round((float) $item['unit_price'], 2) : null,
                    'notes' => ! empty($item['notes']) ? trim((string) $item['notes']) : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'msg' => $id > 0 ? 'Paquete actualizado correctamente.' : 'Paquete registrado exitosamente.',
                'package_id' => $package->id,
            ]);
        } catch (TierOverlapException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'msg' => 'Error de validación de tramos: '.$e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'msg' => 'Ocurrió un error al guardar el paquete: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a package.
     */
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

    /**
     * Toggle active status.
     */
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

    /**
     * Server-side dynamic price calculation simulator endpoint.
     */
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
