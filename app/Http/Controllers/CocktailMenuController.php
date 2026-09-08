<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\CocktailMenuCategory;
use App\Models\CocktailMenuItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CocktailMenuController extends Controller
{
    public function index()
    {
        $kpis = [
            'total' => CocktailMenuItem::count(),
            'activos' => CocktailMenuItem::where('activo', true)->count(),
            'destacados' => CocktailMenuItem::where('destacado', true)->count(),
            'categorias' => CocktailMenuCategory::where('activo', true)->count(),
        ];

        $categories = CocktailMenuCategory::orderBy('orden')->get();

        // Productos tipo servicio (opcion = 2) disponibles para vincular
        $serviceProducts = Product::where('opcion', 2)
            ->select('id', 'descripcion', 'precio_venta')
            ->orderBy('descripcion')
            ->get();

        return view('admin.cocktail_menu.index', [
            'kpis' => $kpis,
            'categories' => $categories,
            'serviceProducts' => $serviceProducts,
            'signo' => $this->signo_pais(),
        ]);
    }

    public function get()
    {
        $items = CocktailMenuItem::with('category', 'product')
            ->orderBy('orden')
            ->orderByDesc('id');

        $signo = $this->signo_pais();

        return datatables()
            ->of($items)
            ->addColumn('imagen_thumb', function (CocktailMenuItem $item) {
                return '<img src="' . $item->image_url . '" alt="' . e($item->nombre) . '" class="rounded shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">';
            })
            ->editColumn('nombre', function (CocktailMenuItem $item) {
                $badges = [];
                $categoryName = $item->category ? $item->category->nombre : 'Sin Categoría';
                $badges[] = '<span class="badge bg-light text-dark border">' . e($categoryName) . '</span>';

                if ($item->es_autor) {
                    $badges[] = '<span class="badge bg-warning-subtle text-warning ms-1"><i class="ri-magic-line me-1"></i>De Autor</span>';
                }

                if ($item->destacado) {
                    $badges[] = '<span class="badge bg-primary-subtle text-primary ms-1"><i class="ri-star-line me-1"></i>Destacado</span>';
                }

                return '<div>
                            <div class="fw-bold text-dark fs-14">' . e($item->nombre) . '</div>
                            <div class="mt-1">' . implode('', $badges) . '</div>
                        </div>';
            })
            ->editColumn('descripcion_corta', function (CocktailMenuItem $item) {
                $desc = Str::limit($item->descripcion_corta, 90);
                $garnish = $item->garnish ? '<div class="small text-muted fst-italic mt-1"><i class="ri-sparkling-line me-1 text-warning"></i>' . e($item->garnish) . '</div>' : '';
                return '<div class="small text-secondary">' . e($desc) . '</div>' . $garnish;
            })
            ->editColumn('cristaleria', function (CocktailMenuItem $item) {
                return $item->cristaleria ? '<span class="badge bg-light text-muted border"><i class="ri-goblet-line me-1"></i>' . e($item->cristaleria) . '</span>' : '-';
            })
            ->editColumn('precio', function (CocktailMenuItem $item) use ($signo) {
                return '<span class="fw-bold text-dark">' . $signo . ' ' . number_format((float) $item->precio, 2) . '</span>';
            })
            ->editColumn('activo', function (CocktailMenuItem $item) {
                $checked = $item->activo ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input btn-toggle-status" type="checkbox" data-id="' . $item->id . '" ' . $checked . '>
                        </div>';
            })
            ->addColumn('acciones', function (CocktailMenuItem $item) {
                return '<div class="d-flex gap-1 justify-content-center">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-cocktail" data-id="' . $item->id . '" title="Editar Cóctel">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-cocktail" data-id="' . $item->id . '" data-nombre="' . e($item->nombre) . '" title="Eliminar Cóctel">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>';
            })
            ->rawColumns(['imagen_thumb', 'nombre', 'descripcion_corta', 'cristaleria', 'precio', 'activo', 'acciones'])
            ->toJson();
    }

    public function detail(Request $request)
    {
        $id = (int) $request->input('id');
        $item = CocktailMenuItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => false,
                'msg' => 'Cóctel no encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'item' => array_merge($item->toArray(), [
                'image_url' => $item->image_url,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $id = (int) $request->input('id', 0);

        $rules = [
            'nombre' => 'required|string|max:150',
            'menu_category_id' => 'required|integer|exists:cocktail_menu_categories,id',
            'precio' => 'nullable|numeric|min:0',
            'cristaleria' => 'nullable|string|max:100',
            'garnish' => 'nullable|string|max:255',
            'descripcion_corta' => 'nullable|string|max:500',
            'orden' => 'nullable|integer|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'nombre.required' => 'El nombre del cóctel es obligatorio.',
            'menu_category_id.required' => 'Debe seleccionar una categoría del menú.',
            'imagen.image' => 'El archivo debe ser una imagen válida (JPG, PNG, WEBP).',
            'imagen.max' => 'La imagen no puede pesar más de 4MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        $item = $id > 0 ? CocktailMenuItem::find($id) : new CocktailMenuItem();

        if (!$item) {
            return response()->json([
                'status' => false,
                'msg' => 'Cóctel no encontrado.',
            ], 404);
        }

        $item->nombre = mb_strtoupper(trim((string) $request->input('nombre')));
        $item->menu_category_id = (int) $request->input('menu_category_id');
        $item->product_id = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        $item->descripcion_corta = trim((string) $request->input('descripcion_corta'));
        $item->cristaleria = trim((string) $request->input('cristaleria'));
        $item->garnish = trim((string) $request->input('garnish'));
        $item->precio = $request->filled('precio') ? round((float) $request->input('precio'), 2) : 0.00;
        $item->es_autor = $request->boolean('es_autor');
        $item->destacado = $request->boolean('destacado');
        $item->activo = $request->boolean('activo', true);

        if ($request->filled('orden')) {
            $item->orden = (int) $request->input('orden');
        } elseif (!$item->exists) {
            $maxOrden = CocktailMenuItem::where('menu_category_id', $item->menu_category_id)->max('orden');
            $item->orden = ($maxOrden ?? 0) + 1;
        }

        // Subida de imagen
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = 'cocktail_' . Str::slug($item->nombre) . '_' . time() . '.' . $file->getClientOriginalExtension();
            File::ensureDirectoryExists(public_path('files/cocktails'));

            // Eliminar imagen anterior si existe
            if ($item->imagen && File::exists(public_path('files/cocktails/' . $item->imagen))) {
                File::delete(public_path('files/cocktails/' . $item->imagen));
            }

            $file->move(public_path('files/cocktails'), $filename);
            $item->imagen = $filename;
        }

        $item->save();

        return response()->json([
            'status' => true,
            'msg' => $id > 0 ? 'Cóctel actualizado correctamente.' : 'Cóctel agregado a la carta exitosamente.',
            'item' => $item,
        ]);
    }

    public function toggle_status(Request $request)
    {
        $id = (int) $request->input('id');
        $item = CocktailMenuItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => false,
                'msg' => 'Cóctel no encontrado.',
            ], 404);
        }

        $item->activo = !$item->activo;
        $item->save();

        return response()->json([
            'status' => true,
            'msg' => $item->activo ? 'Cóctel activado en la carta.' : 'Cóctel desactivado de la carta.',
            'activo' => $item->activo,
        ]);
    }

    public function delete(Request $request)
    {
        $id = (int) $request->input('id');
        $item = CocktailMenuItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => false,
                'msg' => 'Cóctel no encontrado.',
            ], 404);
        }

        if ($item->imagen && File::exists(public_path('files/cocktails/' . $item->imagen))) {
            File::delete(public_path('files/cocktails/' . $item->imagen));
        }

        $item->delete();

        return response()->json([
            'status' => true,
            'msg' => 'Cóctel eliminado de la carta.',
        ]);
    }

    public function store_category(Request $request)
    {
        $id = (int) $request->input('id', 0);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'icono' => 'nullable|string|max:50',
            'orden' => 'nullable|integer',
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        $category = $id > 0 ? CocktailMenuCategory::find($id) : new CocktailMenuCategory();

        if (!$category) {
            return response()->json([
                'status' => false,
                'msg' => 'Categoría no encontrada.',
            ], 404);
        }

        $category->nombre = trim((string) $request->input('nombre'));
        $category->descripcion = trim((string) $request->input('descripcion'));
        $category->icono = trim((string) $request->input('icono', 'ri-goblet-line'));
        $category->orden = (int) $request->input('orden', 0);
        $category->activo = true;
        $category->save();

        return response()->json([
            'status' => true,
            'msg' => $id > 0 ? 'Categoría actualizada correctamente.' : 'Categoría creada correctamente.',
            'category' => $category,
        ]);
    }

    public function delete_category(Request $request)
    {
        $id = (int) $request->input('id');
        $category = CocktailMenuCategory::withCount('items')->find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'msg' => 'Categoría no encontrada.',
            ], 404);
        }

        if ($category->items_count > 0) {
            return response()->json([
                'status' => false,
                'msg' => 'No se puede eliminar la categoría porque contiene ' . $category->items_count . ' cóctel(es). Reasigne o elimine los cócteles primero.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'msg' => 'Categoría eliminada correctamente.',
        ]);
    }

    public function tablet(Request $request)
    {
        $categories = CocktailMenuCategory::active()
            ->with(['activeItems'])
            ->get();

        $allCocktails = CocktailMenuItem::active()
            ->with('category')
            ->orderBy('orden')
            ->get();

        $destacados = CocktailMenuItem::active()
            ->destacados()
            ->with('category')
            ->orderBy('orden')
            ->get();

        $business = Business::first();
        $signo = $this->signo_pais();

        // Parámetro show_prices: 1 (mostrar) o 0 (ocultar). Por defecto muestra, pero el switch en el tablet lo alterna sin recargar.
        $showPrices = $request->boolean('show_prices', true);

        return view('admin.cocktail_menu.tablet', [
            'categories' => $categories,
            'allCocktails' => $allCocktails,
            'destacados' => $destacados,
            'business' => $business,
            'signo' => $signo,
            'showPrices' => $showPrices,
        ]);
    }
}
