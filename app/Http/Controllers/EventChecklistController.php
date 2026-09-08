<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\EventChecklist;
use App\Models\EventChecklistItem;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventChecklistController extends Controller
{
    public function index()
    {
        $warehouseId = $this->currentWarehouseId();
        $query = EventChecklist::query();

        if ($warehouseId > 0) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('idalmacen', $warehouseId)->orWhereNull('idalmacen');
            });
        }

        $now = Carbon::now();

        return view('admin.event_checklists.index', [
            'kpi_total' => (clone $query)->count(),
            'kpi_dispatched' => (clone $query)->where('estado', EventChecklist::STATUS_DISPATCHED)->count(),
            'kpi_returned_ok' => (clone $query)->where('estado', EventChecklist::STATUS_RETURNED_OK)->count(),
            'kpi_incidents' => (clone $query)->where('estado', EventChecklist::STATUS_WITH_INCIDENTS)->count(),
            'signo' => $this->signo_pais(),
        ]);
    }

    public function get(Request $request)
    {
        $warehouseId = $this->currentWarehouseId();
        $checklists = EventChecklist::query()
            ->select('event_checklists.*', 'contracts.contract_number', 'clients.nombres as cliente')
            ->join('contracts', 'event_checklists.contract_id', '=', 'contracts.id')
            ->join('clients', 'contracts.idcliente', '=', 'clients.id')
            ->orderBy('event_checklists.id', 'DESC');

        if ($warehouseId > 0) {
            $checklists->where(function ($q) use ($warehouseId) {
                $q->where('event_checklists.idalmacen', $warehouseId)->orWhereNull('event_checklists.idalmacen');
            });
        }

        return datatables()
            ->of($checklists)
            ->editColumn('fecha_evento', function ($row) {
                return Carbon::parse($row->fecha_evento)->format('d/m/Y');
            })
            ->addColumn('progreso_salida', function ($row) {
                $pct = $row->progressLlevado();
                return '<div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: ' . $pct . '%;" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="small fw-bold text-success">' . $row->items_llevados . '/' . $row->total_items . ' (' . $pct . '%)</span>
                        </div>';
            })
            ->addColumn('progreso_retorno', function ($row) {
                $pct = $row->progressDevuelto();
                $color = $row->items_con_incidencia > 0 ? 'bg-danger' : 'bg-info';
                return '<div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px;">
                                <div class="progress-bar ' . $color . '" role="progressbar" style="width: ' . $pct . '%;" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="small fw-bold text-dark">' . $row->items_devueltos . '/' . $row->total_items . ' (' . $pct . '%)</span>
                        </div>';
            })
            ->addColumn('estado_badge', function ($row) {
                return $row->status_badge;
            })
            ->addColumn('acciones', function ($row) {
                $id = $row->id;
                return '<div class="d-flex gap-1 justify-content-center">
                            <a href="' . route('admin.event_checklists.tablet', $id) . '" class="btn btn-sm btn-primary py-1 px-2" title="Abrir en Modo Tablet">
                                <i class="ri-tablet-line me-1"></i> Modo Tablet
                            </a>
                            <a href="' . route('admin.event_checklists.report', $id) . '" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Reporte de Cierre">
                                <i class="ri-file-chart-line"></i>
                            </a>
                            <a href="' . route('admin.event_checklists.pdf', $id) . '" target="_blank" class="btn btn-sm btn-outline-info py-1 px-2" title="Descargar PDF">
                                <i class="ri-download-line"></i>
                            </a>
                        </div>';
            })
            ->rawColumns(['progreso_salida', 'progreso_retorno', 'estado_badge', 'acciones'])
            ->make(true);
    }

    public function generateFromContract($contractId)
    {
        $contract = Contract::with(['client', 'items.product.category'])->findOrFail($contractId);

        // Check if a checklist already exists for this contract
        $existing = EventChecklist::where('contract_id', $contract->id)->latest('id')->first();
        if ($existing) {
            return redirect()->route('admin.event_checklists.tablet', $existing->id)
                ->with('info', 'Checklist abierto para el contrato ' . $contract->contract_number);
        }

        try {
            DB::beginTransaction();

            $codigo = $this->generateNextCode();
            $titulo = 'Checklist - ' . $contract->title . ' (' . ($contract->client?->nombres ?: 'Cliente') . ')';

            $checklist = EventChecklist::create([
                'contract_id' => $contract->id,
                'codigo' => $codigo,
                'titulo' => $titulo,
                'fecha_evento' => $contract->fecha_evento,
                'hora_evento' => $contract->hora_evento,
                'lugar_evento' => $contract->lugar_evento,
                'responsable_montaje' => Auth::user()?->nombres ?: (Auth::user()?->name ?: 'Encargado de Evento'),
                'responsable_desmontaje' => null,
                'estado' => EventChecklist::STATUS_PLANNED,
                'total_items' => 0,
                'items_llevados' => 0,
                'items_devueltos' => 0,
                'items_con_incidencia' => 0,
                'idusuario' => Auth::id(),
                'idalmacen' => $contract->idalmacen ?: $this->currentWarehouseId(),
            ]);

            $order = 1;
            foreach ($contract->items as $item) {
                // If product is assigned, check opcion (opcion 1 = physical product or rental tool)
                // If product is opcion 2 (pure service like bartender labor), skip unless specified
                $product = $item->product;
                if ($product && $product->opcion == 2) {
                    continue;
                }

                $categoria = $product?->rentable
                    ? 'Herramientas de Barra / Alquiler'
                    : ($product?->category?->descripcion ?: 'Insumos y Menaje de Barra');

                EventChecklistItem::create([
                    'checklist_id' => $checklist->id,
                    'contract_item_id' => $item->id,
                    'idproducto' => $item->idproducto,
                    'descripcion' => $item->descripcion,
                    'categoria' => $categoria,
                    'cantidad_planeada' => $item->cantidad,
                    'cantidad_llevada' => 0.00,
                    'llevado' => false,
                    'cantidad_devuelta' => 0.00,
                    'devuelto' => false,
                    'tiene_incidencia' => false,
                    'orden' => $order++,
                ]);
            }

            $checklist->recalculateCounters();

            DB::commit();

            return redirect()->route('admin.event_checklists.tablet', $checklist->id)
                ->with('success', 'Checklist generado exitosamente a partir de los ítems del contrato.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al generar el checklist: ' . $e->getMessage());
        }
    }

    public function tablet($id)
    {
        $checklist = EventChecklist::with([
            'contract.client',
            'items.userLlevado',
            'items.userDevuelto',
            'user'
        ])->findOrFail($id);

        $business = Business::first();

        return view('admin.event_checklists.tablet', [
            'checklist' => $checklist,
            'contract' => $checklist->contract,
            'client' => $checklist->contract?->client,
            'items' => $checklist->items,
            'business' => $business,
            'signo' => $this->signo_pais(),
        ]);
    }

    public function updateItem(Request $request, $id)
    {
        $checklist = EventChecklist::findOrFail($id);
        $itemId = (int) $request->input('item_id');
        $item = EventChecklistItem::where('checklist_id', $checklist->id)->findOrFail($itemId);

        $action = $request->input('action') ?: $request->input('field');

        try {
            DB::beginTransaction();

            $userId = Auth::id();

            if ($action === 'toggle_llevado' || $action === 'llevado') {
                $qty = $request->has('cantidad') ? floatval($request->input('cantidad')) : ($request->has('value') ? ($request->input('value') == 1 ? $item->cantidad_planeada : 0) : null);
                if ($request->has('value') && $request->input('value') == 0 && $item->llevado) {
                    $item->toggleLlevado($userId);
                } elseif ($request->has('value') && $request->input('value') == 1 && !$item->llevado) {
                    $item->toggleLlevado($userId, $qty);
                } else {
                    $item->toggleLlevado($userId, $qty);
                }
            } elseif ($action === 'toggle_devuelto' || $action === 'devuelto') {
                $qty = $request->has('cantidad') ? floatval($request->input('cantidad')) : null;
                if ($request->has('value') && $request->input('value') == 0 && $item->devuelto) {
                    $item->toggleDevuelto($userId);
                } elseif ($request->has('value') && $request->input('value') == 1 && !$item->devuelto) {
                    $item->toggleDevuelto($userId, $qty);
                } else {
                    $item->toggleDevuelto($userId, $qty);
                }
            } elseif ($action === 'report_incident' || $action === 'incident') {
                $tipo = $request->input('tipo_incidencia', 'danado');
                $obs = $request->input('observaciones');
                $cost = floatval($request->input('costo_penalidad_estimado', 0));
                $qty = $request->has('cantidad_afectada') ? floatval($request->input('cantidad_afectada')) : null;
                $item->reportIncident($tipo, $qty ?: $obs, $obs ?: $cost, $cost);
            } elseif ($action === 'clear_incident' || $action === 'incident_clear') {
                $item->clearIncident();
            }

            $checklist->refresh();
            $checklist->recalculateCounters();

            DB::commit();

            $counters = [
                'total_items' => $checklist->total_items,
                'total_llevados' => $checklist->items_llevados,
                'items_llevados' => $checklist->items_llevados,
                'total_devueltos' => $checklist->items_devueltos,
                'items_devueltos' => $checklist->items_devueltos,
                'total_incidencias' => $checklist->items_con_incidencia,
                'items_con_incidencia' => $checklist->items_con_incidencia,
                'progress_llevado' => $checklist->progressLlevado(),
                'pct_llevado' => $checklist->progressLlevado(),
                'progress_devuelto' => $checklist->progressDevuelto(),
                'pct_devuelto' => $checklist->progressDevuelto(),
                'status_badge' => $checklist->status_badge,
            ];

            return response()->json([
                'status' => true,
                'msg' => 'Ítem actualizado correctamente.',
                'item' => [
                    'id' => $item->id,
                    'llevado' => $item->llevado ? 1 : 0,
                    'cantidad_llevada' => (float) $item->cantidad_llevada,
                    'user_llevado_name' => $item->user_llevado_name,
                    'user_llevado' => $item->user_llevado_name,
                    'fecha_llevado' => $item->fecha_llevado ? $item->fecha_llevado->format('H:i d/m') : null,
                    'fecha_llevado_formatted' => $item->fecha_llevado_formatted,
                    'devuelto' => $item->devuelto ? 1 : 0,
                    'cantidad_devuelta' => (float) $item->cantidad_devuelta,
                    'user_devuelto_name' => $item->user_devuelto_name,
                    'user_devuelto' => $item->user_devuelto_name,
                    'fecha_devuelto' => $item->fecha_devuelto ? $item->fecha_devuelto->format('H:i d/m') : null,
                    'fecha_devuelto_formatted' => $item->fecha_devuelto_formatted,
                    'tiene_incidencia' => $item->tiene_incidencia ? 1 : 0,
                    'tipo_incidencia' => $item->tipo_incidencia,
                    'observaciones' => $item->observaciones,
                ],
                'counters' => $counters,
                'checklist' => $counters,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => 'Error al actualizar el ítem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addItem(Request $request, $id)
    {
        $checklist = EventChecklist::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:255',
            'cantidad' => 'required|numeric|min:0.01',
            'categoria' => 'nullable|string|max:100',
        ], [
            'descripcion.required' => 'Debe ingresar la descripción del ítem.',
            'cantidad.required' => 'La cantidad debe ser mayor a 0.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'msg' => $validator->errors()->first()
            ], 422);
        }

        try {
            $nextOrder = ($checklist->items()->max('orden') ?? 0) + 1;

            $item = EventChecklistItem::create([
                'checklist_id' => $checklist->id,
                'contract_item_id' => null,
                'idproducto' => null,
                'descripcion' => trim($request->input('descripcion')),
                'categoria' => trim($request->input('categoria') ?: 'Insumos / Herramientas Extra'),
                'cantidad_planeada' => floatval($request->input('cantidad')),
                'cantidad_llevada' => 0.00,
                'llevado' => false,
                'cantidad_devuelta' => 0.00,
                'devuelto' => false,
                'tiene_incidencia' => false,
                'orden' => $nextOrder,
            ]);

            $checklist->recalculateCounters();

            return response()->json([
                'status' => true,
                'msg' => 'Ítem adicional agregado al checklist.',
                'item' => $item
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'msg' => 'Error al agregar ítem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkToggle(Request $request, $id)
    {
        $checklist = EventChecklist::findOrFail($id);
        $mode = $request->input('mode'); // 'all_llevado' or 'all_devuelto'
        $userId = Auth::id();
        $now = Carbon::now();

        try {
            DB::beginTransaction();

            if ($mode === 'all_llevado') {
                foreach ($checklist->items as $item) {
                    if (!$item->llevado) {
                        $item->llevado = true;
                        $item->cantidad_llevada = $item->cantidad_planeada;
                        $item->idusuario_llevado = $userId;
                        $item->fecha_llevado = $now;
                        $item->save();
                    }
                }
            } elseif ($mode === 'all_devuelto') {
                foreach ($checklist->items as $item) {
                    if (!$item->devuelto && !$item->tiene_incidencia) {
                        $item->devuelto = true;
                        $item->cantidad_devuelta = $item->cantidad_llevada > 0 ? $item->cantidad_llevada : $item->cantidad_planeada;
                        $item->idusuario_devuelto = $userId;
                        $item->fecha_devuelto = $now;
                        $item->save();
                    }
                }
            }

            $checklist->recalculateCounters();

            DB::commit();

            return response()->json([
                'status' => true,
                'msg' => $mode === 'all_llevado' ? 'Todos los ítems marcados como LLEVADOS.' : 'Todos los ítems conformes marcados como DEVUELTOS.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => 'Error al procesar acción masiva: ' . $e->getMessage()
            ], 500);
        }
    }

    public function report($id)
    {
        $checklist = EventChecklist::with([
            'contract.client',
            'items.userLlevado',
            'items.userDevuelto',
            'user'
        ])->findOrFail($id);

        $contract = $checklist->contract;
        $incidents = $checklist->items->where('tiene_incidencia', true);
        $missing = $checklist->items->where('llevado', true)->where('devuelto', false)->where('tiene_incidencia', false);

        $guarantee20 = $contract ? round($contract->total * 0.20, 2) : 0.00;

        return view('admin.event_checklists.report', [
            'checklist' => $checklist,
            'contract' => $contract,
            'client' => $contract?->client,
            'items' => $checklist->items,
            'incidents' => $incidents,
            'missing' => $missing,
            'guarantee20' => $guarantee20,
            'guaranteeAmount' => $guarantee20,
            'signo' => $this->signo_pais(),
        ]);
    }

    public function pdfReport($id)
    {
        $checklist = EventChecklist::with([
            'contract.client',
            'items.userLlevado',
            'items.userDevuelto',
            'user'
        ])->findOrFail($id);

        $contract = $checklist->contract;
        $business = Business::first();
        $guarantee20 = $contract ? round($contract->total * 0.20, 2) : 0.00;

        $data = [
            'checklist' => $checklist,
            'contract' => $contract,
            'client' => $contract?->client,
            'items' => $checklist->items,
            'incidents' => $checklist->items->where('tiene_incidencia', true),
            'missing' => $checklist->items->where('llevado', true)->where('devuelto', false)->where('tiene_incidencia', false),
            'business' => $business,
            'guarantee20' => $guarantee20,
            'guaranteeAmount' => $guarantee20,
            'signo' => $this->signo_pais(),
        ];

        $pdf = Pdf::loadView('admin.event_checklists.pdf', $data)->setPaper('A4', 'portrait');

        return $pdf->stream('Checklist_' . $checklist->codigo . '.pdf');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $checklist = EventChecklist::find($id);

        if (!$checklist) {
            return response()->json([
                'status' => false,
                'msg' => 'El checklist no existe o ya fue eliminado.'
            ], 404);
        }

        try {
            $checklist->delete();
            return response()->json([
                'status' => true,
                'msg' => 'Checklist eliminado correctamente.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'msg' => 'Error al eliminar checklist: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function generateNextCode(): string
    {
        $year = date('Y');
        $latest = EventChecklist::where('codigo', 'LIKE', "CHK-$year-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latest && preg_match('/CHK-\d{4}-(\d+)/', $latest->codigo, $matches)) {
            $correlativo = intval($matches[1]) + 1;
        } else {
            $correlativo = 1;
        }

        return sprintf("CHK-%s-%04d", $year, $correlativo);
    }
}
