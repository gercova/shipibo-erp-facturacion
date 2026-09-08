<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractInstallment;
use App\Models\ContractItem;
use App\Models\IdentityDocumentType;
use App\Models\Product;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Luecano\NumeroALetras\NumeroALetras;

class ContractController extends Controller
{
    public function index()
    {
        $warehouseId = $this->currentWarehouseId();
        $query = Contract::query();
        if ($warehouseId > 0) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('idalmacen', $warehouseId)->orWhereNull('idalmacen');
            });
        }

        $now = Carbon::now();

        return view('admin.contracts.list', [
            'kpi_total_count' => (clone $query)->count(),
            'kpi_signed_count' => (clone $query)->where('estado', Contract::STATUS_SIGNED)->count(),
            'kpi_events_month' => (clone $query)->whereYear('fecha_evento', $now->year)->whereMonth('fecha_evento', $now->month)->count(),
            'kpi_total_amount' => (clone $query)->where('estado', '!=', Contract::STATUS_VOIDED)->sum('total'),
            'signo' => $this->signo_pais(),
        ]);
    }

    public function get(Request $request)
    {
        $warehouseId = $this->currentWarehouseId();
        $contracts = Contract::query()
            ->select('contracts.*', 'clients.nombres as cliente', 'clients.nro_documento as dni_ruc')
            ->join('clients', 'contracts.idcliente', '=', 'clients.id')
            ->with('installments')
            ->orderBy('contracts.id', 'DESC');

        if ($warehouseId > 0) {
            $contracts->where(function ($q) use ($warehouseId) {
                $q->where('contracts.idalmacen', $warehouseId)->orWhereNull('contracts.idalmacen');
            });
        }

        // Custom filtering from columns
        if ($request->has('columns')) {
            $cols = $request->input('columns');

            // Contract number filter
            if (!empty($cols[0]['search']['value'])) {
                $contracts->where('contracts.contract_number', 'LIKE', '%' . $cols[0]['search']['value'] . '%');
            }
            // Event date filter
            if (!empty($cols[1]['search']['value'])) {
                $contracts->whereDate('contracts.fecha_evento', $cols[1]['search']['value']);
            }
            // Document filter
            if (!empty($cols[2]['search']['value'])) {
                $contracts->where('clients.nro_documento', 'LIKE', '%' . $cols[2]['search']['value'] . '%');
            }
            // Client filter
            if (!empty($cols[3]['search']['value'])) {
                $contracts->where('clients.nombres', 'LIKE', '%' . $cols[3]['search']['value'] . '%');
            }
            // Total filter
            if (!empty($cols[4]['search']['value'])) {
                $contracts->where('contracts.total', 'LIKE', '%' . $cols[4]['search']['value'] . '%');
            }
            // Status filter
            if (isset($cols[5]['search']['value']) && $cols[5]['search']['value'] !== '') {
                $contracts->where('contracts.estado', $cols[5]['search']['value']);
            }
        }

        return datatables()
            ->of($contracts)
            ->editColumn('fecha_evento', function ($row) {
                $time = $row->hora_evento ? ' ' . Carbon::parse($row->hora_evento)->format('H:i') : '';
                return Carbon::parse($row->fecha_evento)->format('d/m/Y') . $time;
            })
            ->editColumn('fecha_emision', function ($row) {
                return Carbon::parse($row->fecha_emision)->format('d/m/Y');
            })
            ->editColumn('total', function ($row) {
                return $this->signo_pais() . ' ' . number_format($row->total, 2);
            })
            ->addColumn('estado_badge', function ($row) {
                $html = '<div class="d-flex flex-column align-items-center gap-1">';
                $html .= '<div>' . $row->status_badge . '</div>';
                $financialBadge = $row->financial_alert_badge;
                if ($financialBadge) {
                    $html .= '<div>' . $financialBadge . '</div>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('acciones', function ($row) {
                $id = $row->id;
                $btn = '<div class="dropdown">
                            <a href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M2 18H9V20H2V18ZM2 11H11V13H2V11ZM2 4H22V6H2V4ZM20.674 13.0251L21.8301 12.634L22.8301 14.366L21.914 15.1711C21.9704 15.4386 22 15.7158 22 16C22 16.2842 21.9704 16.5614 21.914 16.8289L22.8301 17.634L21.8301 19.366L20.674 18.9749C20.2635 19.3441 19.7763 19.6295 19.2391 19.8044L19 21H17L16.7609 19.8044C16.2237 19.6295 15.7365 19.3441 15.326 18.9749L14.1699 19.366L13.1699 17.634L14.086 16.8289C14.0296 16.5614 14 16.2842 14 16C14 15.7158 14.0296 15.4386 14.086 15.1711L13.1699 14.366L14.1699 12.634L15.326 13.0251C15.7365 12.6559 16.2237 12.3705 16.7609 12.1956L17 11H19L19.2391 12.1956C19.7763 12.3705 20.2635 12.6559 20.674 13.0251ZM18 18C19.1046 18 20 17.1046 20 16C20 14.8954 19.1046 14 18 14C16.8954 14 16 14.8954 16 16C16 17.1046 16.8954 18 18 18Z"></path></svg>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item btn-detail-contract" data-id="' . $id . '" href="javascript:void(0);">
                                    <i class="ri-eye-line me-2"></i> Ver detalle
                                </a>
                                <a class="dropdown-item btn-print-contract" data-id="' . $id . '" href="javascript:void(0);">
                                    <i class="ri-printer-line me-2"></i> Imprimir A4
                                </a>
                                <a class="dropdown-item" href="' . route('admin.contracts.download', $id) . '" target="_blank">
                                    <i class="ri-download-2-line me-2"></i> Descargar PDF
                                </a>
                                <a class="dropdown-item" href="' . route('admin.contracts.edit', $id) . '">
                                    <i class="ri-edit-line me-2"></i> Editar
                                </a>
                                <a class="dropdown-item text-primary" href="' . route('admin.event_checklists.generate', $id) . '">
                                    <i class="ri-checkbox-multiple-line me-2"></i> Checklist del Evento
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger btn-delete-contract" data-id="' . $id . '" href="javascript:void(0);">
                                    <i class="ri-delete-bin-line me-2"></i> Eliminar
                                </a>
                            </div>
                        </div>';
                return $btn;
            })
            ->rawColumns(['estado_badge', 'acciones'])
            ->make(true);
    }

    public function create()
    {
        $business = Business::first();
        $clients = Client::orderBy('nombres', 'asc')->get();
        $products = Product::where('opcion', 1)->orderBy('descripcion', 'asc')->get();
        $typeDocuments = IdentityDocumentType::where('estado', 1)->orderBy('descripcion')->get();

        $nextNumber = $this->generateNextContractNumber();
        $defaultClauses = $this->getDefaultClauseTemplates($business);

        return view('admin.contracts.create', [
            'business' => $business,
            'clients' => $clients,
            'products' => $products,
            'typeDocuments' => $typeDocuments,
            'nextNumber' => $nextNumber,
            'defaultClauses' => $defaultClauses,
            'signo' => $this->signo_pais(),
            'moneda' => $this->moneda_pais(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Contract::validationRules(), [
            'idcliente.required' => 'Debe seleccionar un cliente contratante.',
            'contract_number.required' => 'El número de contrato es obligatorio.',
            'contract_number.unique' => 'Este número de contrato ya ha sido registrado.',
            'fecha_evento.required' => 'La fecha del evento es obligatoria.',
            'fecha_emision.required' => 'La fecha de emisión del contrato es obligatoria.',
            'items.required' => 'Debe agregar al menos un servicio o producto al contrato.',
            'items.min' => 'Debe agregar al menos un servicio o producto al contrato.',
            'items.*.descripcion.required' => 'La descripción del servicio/producto es obligatoria.',
            'items.*.cantidad.required' => 'La cantidad debe ser mayor a 0.',
            'items.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors(),
                'type' => 'warning'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $business = Business::first();

            // Calculate item subtotal and totals
            $itemsData = $request->input('items', []);
            $subtotal = 0;
            foreach ($itemsData as $item) {
                $qty = floatval($item['cantidad'] ?? 1);
                $price = floatval($item['precio_unitario'] ?? 0);
                $subtotal += ($qty * $price);
            }

            // In typical Peruvian services, check if IGV applies or total equals subtotal
            $applyIgv = $request->boolean('apply_igv', false);
            $igv = $applyIgv ? round($subtotal * 0.18, 2) : 0.00;
            $total = $subtotal + $igv;

            // Validate installments if provided
            $installmentsData = $request->input('installments', []);
            if (!empty($installmentsData)) {
                $sumInstallments = round((float) collect($installmentsData)->sum(fn($i) => floatval($i['monto'] ?? 0)), 2);
                if (abs($sumInstallments - $total) > 0.05) {
                    return response()->json([
                        'status' => false,
                        'msg' => 'La suma de las cuotas (' . number_format($sumInstallments, 2) . ') debe coincidir con el total del contrato (' . number_format($total, 2) . ').',
                        'type' => 'warning'
                    ], 422);
                }
            }

            // Handle digital signature
            $clientSignatureFile = null;
            if ($request->filled('signature_client_data')) {
                $clientSignatureFile = $this->saveSignatureFromBase64(
                    $request->input('signature_client_data'),
                    $request->input('contract_number'),
                    'client'
                );
            } elseif ($request->hasFile('signature_client_file')) {
                $file = $request->file('signature_client_file');
                $filename = 'sig_client_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('files/contracts/signatures'), $filename);
                $clientSignatureFile = 'files/contracts/signatures/' . $filename;
            }

            $contract = Contract::create([
                'contract_number' => $request->input('contract_number'),
                'title' => $request->input('title', 'CONTRATO DE PRESTACIÓN DE SERVICIOS'),
                'idcliente' => $request->input('idcliente'),
                'provider_name' => $request->input('provider_name') ?: ($business?->razon_social ?: $business?->nombre_comercial),
                'provider_document' => $request->input('provider_document') ?: $business?->ruc,
                'provider_representative' => $request->input('provider_representative') ?: $business?->representante,
                'fecha_evento' => $request->input('fecha_evento'),
                'hora_evento' => $request->input('hora_evento'),
                'lugar_evento' => $request->input('lugar_evento'),
                'fecha_emision' => $request->input('fecha_emision'),
                'fecha_vencimiento' => $request->input('fecha_vencimiento'),
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'moneda' => $request->input('moneda', $this->moneda_pais()),
                'observaciones' => $request->input('observaciones'),
                'firma_cliente' => $clientSignatureFile,
                'estado' => $request->input('estado', Contract::STATUS_SIGNED),
                'idusuario' => Auth::id(),
                'idalmacen' => $this->currentWarehouseId(),
            ]);

            // Save Items
            foreach ($itemsData as $item) {
                $qty = floatval($item['cantidad'] ?? 1);
                $price = floatval($item['precio_unitario'] ?? 0);
                $lineSubtotal = round($qty * $price, 2);

                ContractItem::create([
                    'contract_id' => $contract->id,
                    'idproducto' => !empty($item['idproducto']) ? $item['idproducto'] : null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $qty,
                    'precio_unitario' => $price,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            // Save Clauses
            $clausesData = $request->input('clauses', []);
            $order = 1;
            foreach ($clausesData as $clause) {
                if (!empty($clause['titulo']) && !empty($clause['contenido'])) {
                    ContractClause::create([
                        'contract_id' => $contract->id,
                        'titulo' => trim($clause['titulo']),
                        'contenido' => trim($clause['contenido']),
                        'orden' => $order++,
                    ]);
                }
            }

            // Sync Installments (50% initial down payment + remaining installments schedule)
            $this->syncInstallments($contract, $installmentsData, $total, $request->input('fecha_emision'), $request->input('fecha_evento'));

            DB::commit();

            // Generate initial A4 PDF
            $pdfFileName = $this->generatePdfFile($contract->id);

            return response()->json([
                'status' => true,
                'msg' => 'Contrato registrado y firmado exitosamente.',
                'id' => $contract->id,
                'pdf' => $pdfFileName,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => 'Ocurrió un error al guardar el contrato: ' . $e->getMessage(),
                'type' => 'error'
            ], 500);
        }
    }

    public function edit($id)
    {
        $contract = Contract::with(['client', 'items.product', 'clauses', 'installments'])->find($id);
        abort_if(!$contract, 404);

        $business = Business::first();
        $clients = Client::orderBy('nombres', 'asc')->get();
        $products = Product::where('opcion', 1)->orderBy('descripcion', 'asc')->get();
        $typeDocuments = IdentityDocumentType::where('estado', 1)->orderBy('descripcion')->get();

        return view('admin.contracts.edit', [
            'contract' => $contract,
            'business' => $business,
            'clients' => $clients,
            'products' => $products,
            'typeDocuments' => $typeDocuments,
            'signo' => $this->signo_pais(),
            'moneda' => $this->moneda_pais(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'status' => false,
                'msg' => 'El contrato no existe.',
                'type' => 'warning'
            ], 404);
        }

        $validator = Validator::make($request->all(), Contract::validationRules($id), [
            'idcliente.required' => 'Debe seleccionar un cliente contratante.',
            'contract_number.required' => 'El número de contrato es obligatorio.',
            'contract_number.unique' => 'Este número de contrato ya ha sido registrado.',
            'fecha_evento.required' => 'La fecha del evento es obligatoria.',
            'fecha_emision.required' => 'La fecha de emisión del contrato es obligatoria.',
            'items.required' => 'Debe agregar al menos un servicio o producto al contrato.',
            'items.min' => 'Debe agregar al menos un servicio o producto al contrato.',
            'items.*.descripcion.required' => 'La descripción del servicio/producto es obligatoria.',
            'items.*.cantidad.required' => 'La cantidad debe ser mayor a 0.',
            'items.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors(),
                'type' => 'warning'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $business = Business::first();

            // Calculate item subtotal and totals
            $itemsData = $request->input('items', []);
            $subtotal = 0;
            foreach ($itemsData as $item) {
                $qty = floatval($item['cantidad'] ?? 1);
                $price = floatval($item['precio_unitario'] ?? 0);
                $subtotal += ($qty * $price);
            }

            $applyIgv = $request->boolean('apply_igv', false);
            $igv = $applyIgv ? round($subtotal * 0.18, 2) : 0.00;
            $total = $subtotal + $igv;

            // Validate installments if provided
            $installmentsData = $request->input('installments', []);
            if (!empty($installmentsData)) {
                $sumInstallments = round((float) collect($installmentsData)->sum(fn($i) => floatval($i['monto'] ?? 0)), 2);
                if (abs($sumInstallments - $total) > 0.05) {
                    return response()->json([
                        'status' => false,
                        'msg' => 'La suma de las cuotas (' . number_format($sumInstallments, 2) . ') debe coincidir con el total del contrato (' . number_format($total, 2) . ').',
                        'type' => 'warning'
                    ], 422);
                }
            }

            // Handle digital signature updates
            $clientSignatureFile = $contract->firma_cliente;
            if ($request->filled('signature_client_data')) {
                // Remove previous file if exists
                if ($clientSignatureFile && File::exists(public_path($clientSignatureFile))) {
                    File::delete(public_path($clientSignatureFile));
                }
                $clientSignatureFile = $this->saveSignatureFromBase64(
                    $request->input('signature_client_data'),
                    $request->input('contract_number'),
                    'client'
                );
            } elseif ($request->hasFile('signature_client_file')) {
                if ($clientSignatureFile && File::exists(public_path($clientSignatureFile))) {
                    File::delete(public_path($clientSignatureFile));
                }
                $file = $request->file('signature_client_file');
                $filename = 'sig_client_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('files/contracts/signatures'), $filename);
                $clientSignatureFile = 'files/contracts/signatures/' . $filename;
            }

            $contract->update([
                'contract_number' => $request->input('contract_number'),
                'title' => $request->input('title', 'CONTRATO DE PRESTACIÓN DE SERVICIOS'),
                'idcliente' => $request->input('idcliente'),
                'provider_name' => $request->input('provider_name') ?: ($business?->razon_social ?: $business?->nombre_comercial),
                'provider_document' => $request->input('provider_document') ?: $business?->ruc,
                'provider_representative' => $request->input('provider_representative') ?: $business?->representante,
                'fecha_evento' => $request->input('fecha_evento'),
                'hora_evento' => $request->input('hora_evento'),
                'lugar_evento' => $request->input('lugar_evento'),
                'fecha_emision' => $request->input('fecha_emision'),
                'fecha_vencimiento' => $request->input('fecha_vencimiento'),
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'moneda' => $request->input('moneda', $contract->moneda),
                'observaciones' => $request->input('observaciones'),
                'firma_cliente' => $clientSignatureFile,
                'estado' => $request->input('estado', $contract->estado),
            ]);

            // Sync items
            $contract->items()->delete();
            foreach ($itemsData as $item) {
                $qty = floatval($item['cantidad'] ?? 1);
                $price = floatval($item['precio_unitario'] ?? 0);
                $lineSubtotal = round($qty * $price, 2);

                ContractItem::create([
                    'contract_id' => $contract->id,
                    'idproducto' => !empty($item['idproducto']) ? $item['idproducto'] : null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $qty,
                    'precio_unitario' => $price,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            // Sync clauses
            $contract->clauses()->delete();
            $clausesData = $request->input('clauses', []);
            $order = 1;
            foreach ($clausesData as $clause) {
                if (!empty($clause['titulo']) && !empty($clause['contenido'])) {
                    ContractClause::create([
                        'contract_id' => $contract->id,
                        'titulo' => trim($clause['titulo']),
                        'contenido' => trim($clause['contenido']),
                        'orden' => $order++,
                    ]);
                }
            }

            // Sync Installments
            $this->syncInstallments($contract, $installmentsData, $total, $request->input('fecha_emision'), $request->input('fecha_evento'));

            DB::commit();

            // Re-generate A4 PDF
            $pdfFileName = $this->generatePdfFile($contract->id);

            return response()->json([
                'status' => true,
                'msg' => 'Contrato actualizado correctamente.',
                'id' => $contract->id,
                'pdf' => $pdfFileName,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => 'Ocurrió un error al actualizar el contrato: ' . $e->getMessage(),
                'type' => 'error'
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json([
                'status' => false,
                'msg' => 'El contrato no existe o ya fue eliminado.',
                'type' => 'warning'
            ], 404);
        }

        try {
            // Delete signature files
            if ($contract->firma_cliente && File::exists(public_path($contract->firma_cliente))) {
                File::delete(public_path($contract->firma_cliente));
            }
            if ($contract->firma_proveedor && File::exists(public_path($contract->firma_proveedor))) {
                File::delete(public_path($contract->firma_proveedor));
            }

            // Delete generated PDF if exists
            $pdfPath = public_path('files/contracts/' . $contract->contract_number . '.pdf');
            if (File::exists($pdfPath)) {
                File::delete($pdfPath);
            }

            $contract->delete();

            return response()->json([
                'status' => true,
                'msg' => 'Contrato eliminado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'msg' => 'Error al eliminar el contrato: ' . $e->getMessage(),
                'type' => 'error'
            ], 500);
        }
    }

    public function detail(Request $request)
    {
        $id = (int) $request->input('id');
        $contract = Contract::with(['client.tipoDocumento', 'items', 'clauses', 'installments'])->find($id);

        if (!$contract) {
            return response()->json([
                'status' => false,
                'msg' => 'El contrato no existe.',
                'type' => 'warning'
            ], 404);
        }

        $formattedInstallments = $contract->installments->map(function ($inst) {
            return [
                'id' => $inst->id,
                'numero_cuota' => $inst->numero_cuota,
                'descripcion' => $inst->descripcion,
                'monto' => (float) $inst->monto,
                'porcentaje' => (float) $inst->porcentaje,
                'fecha_vencimiento' => $inst->fecha_vencimiento ? $inst->fecha_vencimiento->format('d/m/Y') : '-',
                'fecha_vencimiento_raw' => $inst->fecha_vencimiento ? $inst->fecha_vencimiento->format('Y-m-d') : null,
                'fecha_pago' => $inst->fecha_pago ? $inst->fecha_pago->format('d/m/Y') : '-',
                'estado' => $inst->estado,
                'is_paid' => $inst->isPaid(),
                'is_overdue' => $inst->isOverdue(),
                'is_due_today' => $inst->isDueToday(),
                'badge' => $inst->status_badge,
                'metodo_pago' => $inst->metodo_pago,
                'referencia_pago' => $inst->referencia_pago,
                'observaciones' => $inst->observaciones,
            ];
        });

        return response()->json([
            'status' => true,
            'contract' => $contract,
            'client' => $contract->client,
            'items' => $contract->items,
            'clauses' => $contract->clauses,
            'installments' => $formattedInstallments,
            'has_overdue' => $contract->hasOverdueInstallments(),
            'paid_amount' => $contract->paid_amount,
            'pending_amount' => $contract->pending_amount,
            'status_label' => $contract->status_label,
            'signo' => $this->signo_pais(),
        ]);
    }

    public function pay_installment(Request $request, $id)
    {
        $installment = ContractInstallment::with('contract')->find($id);
        if (!$installment) {
            return response()->json([
                'status' => false,
                'msg' => 'La cuota no existe o ya fue eliminada.',
                'type' => 'warning'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'nullable|string|max:50',
            'referencia_pago' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:255',
        ], [
            'fecha_pago.required' => 'Debe ingresar la fecha de pago.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'msg' => $validator->errors()->first(),
                'type' => 'warning'
            ], 422);
        }

        $installment->update([
            'estado' => ContractInstallment::STATUS_PAID,
            'fecha_pago' => $request->input('fecha_pago', date('Y-m-d')),
            'metodo_pago' => $request->input('metodo_pago', 'Efectivo'),
            'referencia_pago' => $request->input('referencia_pago'),
            'observaciones' => $request->input('observaciones'),
        ]);

        // Re-generate contract PDF with updated payment details if desired
        $this->generatePdfFile($installment->contract_id);

        return response()->json([
            'status' => true,
            'msg' => 'Pago de la cuota #' . $installment->numero_cuota . ' registrado exitosamente.',
            'installment' => $installment,
        ]);
    }

    public function print_a4(Request $request)
    {
        $id = (int) $request->input('id');
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json([
                'status' => false,
                'msg' => 'El contrato no existe.',
                'type' => 'warning'
            ], 404);
        }

        $fileName = $this->generatePdfFile($contract->id);

        return response()->json([
            'status' => true,
            'pdf' => $fileName,
        ]);
    }

    public function download_pdf($id)
    {
        $contract = Contract::find($id);
        abort_if(!$contract, 404);

        $fileName = $this->generatePdfFile($contract->id);
        $filePath = public_path('files/contracts/' . $fileName);

        return response()->download($filePath, $contract->contract_number . '.pdf');
    }

    protected function generatePdfFile($contractId): string
    {
        $contract = Contract::with(['client.tipoDocumento', 'items', 'clauses', 'installments'])->findOrFail($contractId);
        $business = Business::first();

        $formatter = new NumeroALetras();
        $numeroLetras = $formatter->toWords($contract->total, 2);

        $data = [
            'contract' => $contract,
            'client' => $contract->client,
            'items' => $contract->items,
            'clauses' => $contract->clauses,
            'installments' => $contract->installments,
            'business' => $business,
            'logo' => $business?->logo,
            'numero_letras' => $numeroLetras,
            'signo' => $this->signo_pais(),
            'moneda' => $contract->moneda ?: $this->moneda_pais(),
        ];

        $folder = public_path('files/contracts');
        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0777, true, true);
        }

        $fileName = $contract->contract_number . '.pdf';
        $pdf = Pdf::loadView('admin.contracts.pdf', $data)->setPaper('A4', 'portrait');
        $pdf->save($folder . '/' . $fileName);

        return $fileName;
    }

    protected function saveSignatureFromBase64(string $dataUrl, string $contractNumber, string $prefix = 'client'): string
    {
        $folder = public_path('files/contracts/signatures');
        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0777, true, true);
        }

        // Clean dataUrl
        if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
            $dataUrl = substr($dataUrl, strpos($dataUrl, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            $type = 'png';
        }

        $decodedData = base64_decode($dataUrl);
        $safeNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', $contractNumber);
        $filename = 'sig_' . $prefix . '_' . $safeNumber . '_' . time() . '.' . $type;
        $filePath = $folder . '/' . $filename;

        file_put_contents($filePath, $decodedData);

        return 'files/contracts/signatures/' . $filename;
    }

    protected function generateNextContractNumber(): string
    {
        $year = date('Y');
        $latest = Contract::where('contract_number', 'LIKE', "CON-$year-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latest && preg_match('/CON-\d{4}-(\d+)/', $latest->contract_number, $matches)) {
            $correlativo = intval($matches[1]) + 1;
        } else {
            $correlativo = 1;
        }

        return sprintf("CON-%s-%04d", $year, $correlativo);
    }

    protected function syncInstallments(Contract $contract, array $installmentsData, float $total, string $fechaEmision, ?string $fechaEvento): void
    {
        // If installmentsData is empty, auto-generate default 50% initial down payment + 50% remaining balance
        if (empty($installmentsData)) {
            $monto1 = round($total * 0.50, 2);
            $monto2 = round($total - $monto1, 2);
            $dueDate2 = $fechaEvento ?: Carbon::parse($fechaEmision)->addDays(15)->format('Y-m-d');

            $installmentsData = [
                [
                    'numero_cuota' => 1,
                    'descripcion' => 'Adelanto Inicial (50%)',
                    'porcentaje' => 50,
                    'monto' => $monto1,
                    'fecha_vencimiento' => $fechaEmision,
                    'estado' => 0,
                ],
                [
                    'numero_cuota' => 2,
                    'descripcion' => 'Saldo Final (50%)',
                    'porcentaje' => 50,
                    'monto' => $monto2,
                    'fecha_vencimiento' => $dueDate2,
                    'estado' => 0,
                ]
            ];
        }

        // Keep track of existing paid status if updating
        $existingPaidMap = [];
        foreach ($contract->installments as $existingInst) {
            if ($existingInst->isPaid()) {
                $existingPaidMap[$existingInst->numero_cuota] = [
                    'estado' => $existingInst->estado,
                    'fecha_pago' => $existingInst->fecha_pago?->format('Y-m-d'),
                    'metodo_pago' => $existingInst->metodo_pago,
                    'referencia_pago' => $existingInst->referencia_pago,
                    'observaciones' => $existingInst->observaciones,
                ];
            }
        }

        // Delete previous installments
        $contract->installments()->delete();

        $cuotasJson = [];
        $order = 1;

        foreach ($installmentsData as $inst) {
            $monto = round(floatval($inst['monto'] ?? 0), 2);
            if ($monto <= 0) continue;

            $fechaVenc = !empty($inst['fecha_vencimiento']) ? $inst['fecha_vencimiento'] : $fechaEmision;
            $desc = !empty($inst['descripcion']) ? trim($inst['descripcion']) : ($order === 1 ? 'Adelanto Inicial (50%)' : "Cuota {$order}");
            $porcentaje = !empty($inst['porcentaje']) ? floatval($inst['porcentaje']) : ($total > 0 ? round(($monto / $total) * 100, 2) : 0);

            // Restore paid status if matched by installment number
            $isPreviouslyPaid = isset($existingPaidMap[$order]);
            $estado = $isPreviouslyPaid ? $existingPaidMap[$order]['estado'] : intval($inst['estado'] ?? 0);
            $fechaPago = $isPreviouslyPaid ? $existingPaidMap[$order]['fecha_pago'] : (!empty($inst['fecha_pago']) ? $inst['fecha_pago'] : null);
            $metodoPago = $isPreviouslyPaid ? $existingPaidMap[$order]['metodo_pago'] : ($inst['metodo_pago'] ?? null);
            $referenciaPago = $isPreviouslyPaid ? $existingPaidMap[$order]['referencia_pago'] : ($inst['referencia_pago'] ?? null);
            $observaciones = $isPreviouslyPaid ? $existingPaidMap[$order]['observaciones'] : ($inst['observaciones'] ?? null);

            ContractInstallment::create([
                'contract_id' => $contract->id,
                'numero_cuota' => $order,
                'descripcion' => $desc,
                'monto' => $monto,
                'porcentaje' => $porcentaje,
                'fecha_vencimiento' => $fechaVenc,
                'fecha_pago' => $fechaPago,
                'estado' => $estado,
                'metodo_pago' => $metodoPago,
                'referencia_pago' => $referenciaPago,
                'observaciones' => $observaciones,
            ]);

            $cuotasJson[] = [
                'nro' => $order,
                'monto' => number_format($monto, 2, '.', ''),
                'fecha_vencimiento' => $fechaVenc,
            ];

            $order++;
        }

        $contract->cuotas = $cuotasJson;
        $contract->save();
    }

    protected function getDefaultClauseTemplates($business): array
    {
        $companyName = $business?->razon_social ?: ($business?->nombre_comercial ?: 'LA EMPRESA');
        $ruc = $business?->ruc ?: '---';

        return [
            [
                'titulo' => 'PRIMERA: PARTES CONTRATANTES',
                'contenido' => "El presente contrato se celebra entre {$companyName}, con RUC N° {$ruc} (en adelante, EL PRESTADOR), y la persona natural o jurídica individualizada en la sección de datos generales (en adelante, EL CLIENTE). Ambas partes declaran contar con plena capacidad civil y legal para suscribir este instrumento."
            ],
            [
                'titulo' => 'SEGUNDA: OBJETO DEL CONTRATO',
                'contenido' => 'EL PRESTADOR se compromete a brindar a favor de EL CLIENTE los servicios y/o productos especificados y detallados en la tabla de ítems del presente contrato, con los más altos estándares de calidad, profesionalismo y puntualidad.'
            ],
            [
                'titulo' => 'TERCERA: DE LA FECHA, HORA Y LUGAR DEL EVENTO',
                'contenido' => 'Los servicios contratados se prestarán estrictamente en la fecha, horario y ubicación o local señalados expresamente en la carátula y cláusulas de este contrato. Cualquier cambio de fecha o dirección requerirá acuerdo previo por escrito con un mínimo de anticipación de 7 días calendario y estará sujeto a disponibilidad.'
            ],
            [
                'titulo' => 'CUARTA: DEL PRECIO Y CONDICIONES DE PAGO',
                'contenido' => 'El costo total de la prestación asciende al monto indicado en el resumen de valores de este contrato. Se cancelará según las condiciones acordadas: un adelanto para reserva de fecha y el saldo restante antes o al inicio de la ejecución del evento o entrega de los bienes contratados.'
            ],
            [
                'titulo' => 'QUINTA: OBLIGACIONES DE LAS PARTES',
                'contenido' => 'EL PRESTADOR se compromete a disponer del personal calificado, materiales, equipos y logística necesarios para el cumplimiento oportuno del servicio. EL CLIENTE se compromete a facilitar el acceso oportuno al recinto del evento, conexiones técnicas indispensables y cumplir con el cronograma de pagos convenido.'
            ],
            [
                'titulo' => 'SEXTA: POLÍTICA DE CANCELACIÓN Y PENALIDADES',
                'contenido' => 'En caso de que EL CLIENTE decida resolver o cancelar el servicio de forma unilateral, los montos entregados como anticipo o reserva no serán reembolsables, constituyendo indemnización por gastos operativos y bloqueo de agenda. Si la cancelación ocurriere por fuerza mayor comprobada, las partes coordinarán una nueva fecha dentro de los 60 días siguientes.'
            ],
            [
                'titulo' => 'SÉPTIMA: CONFORMIDAD Y JURISDICCIÓN',
                'contenido' => 'Ambas partes expresan su absoluta conformidad con el contenido de todas y cada una de las cláusulas del presente contrato, el cual firman de manera digital o presencial. Para cualquier controversia no resuelta de mutuo acuerdo, las partes se someten expresamente a la jurisdicción y competencia de los jueces y tribunales correspondientes.'
            ],
            [
                'titulo' => 'OCTAVA: DE LA GARANTÍA POR PÉRDIDAS O DAÑOS (20%)',
                'contenido' => 'EL CLIENTE se compromete a constituir o asumir un fondo de garantía equivalente al 20% del valor total del contrato, destinado a cubrir eventuales roturas, pérdidas, extravíos o deterioros de cristalería, barras móviles, utensilios, equipos de coctelería o menaje suministrados durante el evento. Dicho monto o saldo remanente será liquidado o reintegrado a EL CLIENTE una vez culminado el evento e inventariado el material conforme por ambas partes.'
            ]
        ];
    }
}
