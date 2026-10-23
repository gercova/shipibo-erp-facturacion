<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractInstallment;
use App\Models\PayMode;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractReportController extends Controller
{
    /**
     * Muestra el panel interactivo centralizado de Reportes de Contratos.
     */
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // Métricas ejecutivas consolidadas
        $totalContracts = Contract::count();
        $activeContracts = Contract::whereIn('status', [Contract::STATUS_STR_VIGENTE, 'firmado'])
            ->orWhere('estado', Contract::STATUS_SIGNED)
            ->count();

        $overdueInstallmentsCount = ContractInstallment::where(function ($q) {
            $q->where('estado', ContractInstallment::STATUS_PENDING)
                ->orWhere('status', 'pending');
        })
            ->whereDate('fecha_vencimiento', '<', $today)
            ->count();

        $overdueInstallmentsAmount = (float) ContractInstallment::where(function ($q) {
            $q->where('estado', ContractInstallment::STATUS_PENDING)
                ->orWhere('status', 'pending');
        })
            ->whereDate('fecha_vencimiento', '<', $today)
            ->sum('monto');

        $activeContractsTotal = (float) Contract::where(function ($q) {
            $q->where('estado', Contract::STATUS_SIGNED)
                ->orWhereIn('status', [Contract::STATUS_STR_VIGENTE, 'firmado']);
        })->sum('total');

        $guaranteesTotal = round($activeContractsTotal * 0.20, 2);

        $adjustmentsTotal = (float) ContractAdjustment::whereIn('type', [
            ContractAdjustment::TYPE_BREAKAGE,
            ContractAdjustment::TYPE_MISSING,
        ])->sum('amount');

        $preliminariesTotal = Contract::where(function ($q) {
            $q->whereNotNull('preliminary_number')
                ->orWhere('source', Contract::SOURCE_WEB)
                ->orWhere('status', Contract::STATUS_STR_PRELIMINAR);
        })->count();

        $preliminariesConverted = Contract::where(function ($q) {
            $q->whereNotNull('preliminary_number')
                ->orWhere('source', Contract::SOURCE_WEB)
                ->orWhere('status', Contract::STATUS_STR_PRELIMINAR);
        })
            ->where(function ($q) {
                $q->where('contract_number', 'LIKE', 'CON-%')
                    ->orWhereIn('status', [Contract::STATUS_STR_VIGENTE, Contract::STATUS_STR_CERRADO])
                    ->orWhere('estado', Contract::STATUS_SIGNED);
            })
            ->count();

        $conversionRate = $preliminariesTotal > 0
            ? round(($preliminariesConverted / $preliminariesTotal) * 100, 1)
            : 0;

        return view('admin.reports.contracts.index', [
            'signo' => $this->signo_pais(),
            'defaultDateFrom' => $startOfMonth,
            'defaultDateTo' => $endOfMonth,
            'clients' => Client::orderBy('nombres')->get(['id', 'nombres', 'nro_documento']),
            'warehouses' => Warehouse::orderBy('descripcion')->get(['id', 'descripcion']),
            'payModes' => PayMode::orderBy('descripcion')->get(['id', 'descripcion']),
            'kpis' => [
                'total_contracts' => $totalContracts,
                'active_contracts' => $activeContracts,
                'overdue_count' => $overdueInstallmentsCount,
                'overdue_amount' => $overdueInstallmentsAmount,
                'guarantees_total' => $guaranteesTotal,
                'adjustments_total' => $adjustmentsTotal,
                'preliminaries_total' => $preliminariesTotal,
                'preliminaries_converted' => $preliminariesConverted,
                'conversion_rate' => $conversionRate,
            ],
        ]);
    }

    /**
     * Reporte 1: Contratos agrupados por Estado (DataTables Server-Side).
     */
    public function getContractsByStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $clientId = $request->input('client_id');
        $warehouseId = $request->input('warehouse_id');

        $query = Contract::with(['client', 'warehouse', 'installments'])
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                if (is_numeric($status)) {
                    $q->where('estado', (int) $status);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('fecha_emision', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('fecha_emision', '<=', $dateTo))
            ->when($clientId, fn ($q) => $q->where('idcliente', $clientId))
            ->when($warehouseId, fn ($q) => $q->where('idalmacen', $warehouseId))
            ->orderByDesc('id');

        return datatables()->of($query)
            ->addColumn('codigo_contrato', function ($c) {
                $prelim = $c->preliminary_number ? '<br><small class="text-muted">Previo: '.e($c->preliminary_number).'</small>' : '';

                return '<strong>'.e($c->contract_number).'</strong>'.$prelim;
            })
            ->addColumn('cliente_info', function ($c) {
                $doc = $c->client?->nro_documento ? '<br><small class="text-muted">'.e($c->client->nro_documento).'</small>' : '';

                return '<div>'.e($c->client?->nombres ?: 'Sin cliente').$doc.'</div>';
            })
            ->addColumn('fechas_info', function ($c) {
                $evento = $c->fecha_evento ? $c->fecha_evento->format('d/m/Y') : '-';
                $emision = $c->fecha_emision ? $c->fecha_emision->format('d/m/Y') : '-';

                return '<div><small class="text-muted">Emisión:</small> '.$emision.'<br><small class="text-primary fw-semibold">Evento:</small> '.$evento.'</div>';
            })
            ->addColumn('finanzas_info', function ($c) {
                $paid = $c->paid_amount;
                $pending = $c->pending_amount;

                return '<div class="text-end font-monospace">'
                    .'<strong>'.$this->signo_pais().' '.number_format((float) $c->total, 2).'</strong><br>'
                    .'<small class="text-success">Pag: '.number_format($paid, 2).'</small> | '
                    .'<small class="text-danger">Pend: '.number_format($pending, 2).'</small>'
                    .'</div>';
            })
            ->addColumn('estado_badge', function ($c) {
                return $c->status_badge;
            })
            ->rawColumns(['codigo_contrato', 'cliente_info', 'fechas_info', 'finanzas_info', 'estado_badge'])
            ->toJson();
    }

    /**
     * Reporte 2: Cuotas Vencidas y Por Vencer (DataTables Server-Side).
     */
    public function getInstallmentsDue(Request $request): JsonResponse
    {
        $dueFilter = $request->input('due_filter', 'all'); // overdue, upcoming, today, all
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $clientId = $request->input('client_id');
        $today = Carbon::today()->toDateString();

        $query = ContractInstallment::with(['contract.client'])
            ->where(function ($q) {
                $q->where('estado', ContractInstallment::STATUS_PENDING)
                    ->orWhere('status', 'pending');
            })
            ->when($dueFilter === 'overdue', fn ($q) => $q->whereDate('fecha_vencimiento', '<', $today))
            ->when($dueFilter === 'upcoming', fn ($q) => $q->whereDate('fecha_vencimiento', '>', $today))
            ->when($dueFilter === 'today', fn ($q) => $q->whereDate('fecha_vencimiento', '=', $today))
            ->when($dateFrom, fn ($q) => $q->whereDate('fecha_vencimiento', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('fecha_vencimiento', '<=', $dateTo))
            ->when($clientId, function ($q) use ($clientId) {
                $q->whereHas('contract', fn ($sub) => $sub->where('idcliente', $clientId));
            })
            ->orderBy('fecha_vencimiento', 'asc');

        return datatables()->of($query)
            ->addColumn('contrato_info', function ($i) {
                return '<strong>'.e($i->contract?->contract_number ?? 'N/A').'</strong>';
            })
            ->addColumn('cliente_info', function ($i) {
                $client = $i->contract?->client;
                $doc = $client?->nro_documento ? '<br><small class="text-muted">'.e($client->nro_documento).'</small>' : '';

                return '<div>'.e($client?->nombres ?: 'N/A').$doc.'</div>';
            })
            ->addColumn('cuota_detalle', function ($i) {
                return '<div><strong>Cuota #'.e($i->numero_cuota).'</strong><br><small class="text-muted">'.e($i->descripcion ?: 'Cuota pactada').'</small></div>';
            })
            ->editColumn('fecha_vencimiento', function ($i) {
                return $i->fecha_vencimiento ? $i->fecha_vencimiento->format('d/m/Y') : '-';
            })
            ->addColumn('dias_alerta', function ($i) use ($today) {
                if (! $i->fecha_vencimiento) {
                    return '<span class="badge bg-secondary">Sin fecha</span>';
                }
                $venc = $i->fecha_vencimiento->toDateString();
                $diff = Carbon::parse($today)->diffInDays(Carbon::parse($venc), false);

                if ($diff < 0) {
                    return '<span class="badge bg-danger text-white"><i class="ri-alarm-warning-line me-1"></i> Vencida hace '.abs($diff).' d</span>';
                } elseif ($diff === 0) {
                    return '<span class="badge bg-warning text-dark"><i class="ri-time-line me-1"></i> Vence Hoy</span>';
                } else {
                    return '<span class="badge bg-info-subtle text-info"><i class="ri-calendar-line me-1"></i> En '.$diff.' días</span>';
                }
            })
            ->editColumn('monto', function ($i) {
                return '<div class="text-end fw-bold font-monospace text-dark">'
                    .$this->signo_pais().' '.number_format((float) $i->monto, 2)
                    .'</div>';
            })
            ->rawColumns(['contrato_info', 'cliente_info', 'cuota_detalle', 'fecha_vencimiento', 'dias_alerta', 'monto'])
            ->toJson();
    }

    /**
     * Reporte 3: Garantías Contractuales Recibidas y Retenidas (DataTables Server-Side).
     */
    public function getGuarantees(Request $request): JsonResponse
    {
        $clientId = $request->input('client_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Contract::with(['client', 'adjustments', 'installments'])
            ->where(function ($q) {
                $q->where('estado', Contract::STATUS_SIGNED)
                    ->orWhereIn('status', [Contract::STATUS_STR_VIGENTE, Contract::STATUS_STR_CERRADO, 'firmado']);
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('fecha_evento', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('fecha_evento', '<=', $dateTo))
            ->when($clientId, fn ($q) => $q->where('idcliente', $clientId))
            ->orderByDesc('fecha_evento');

        return datatables()->of($query)
            ->addColumn('codigo_contrato', function ($c) {
                return '<strong>'.e($c->contract_number).'</strong>';
            })
            ->addColumn('cliente_info', function ($c) {
                return e($c->client?->nombres ?: 'Sin cliente');
            })
            ->addColumn('fecha_evento_fmt', function ($c) {
                return $c->fecha_evento ? $c->fecha_evento->format('d/m/Y') : '-';
            })
            ->addColumn('total_contrato', function ($c) {
                return '<div class="text-end font-monospace">'.$this->signo_pais().' '.number_format((float) $c->total, 2).'</div>';
            })
            ->addColumn('garantia_20', function ($c) {
                $guarantee = round((float) $c->total * 0.20, 2);

                return '<div class="text-end font-monospace fw-bold text-primary">'
                    .$this->signo_pais().' '.number_format($guarantee, 2)
                    .'</div>';
            })
            ->addColumn('ajustes_descontados', function ($c) {
                $breakages = (float) $c->adjustments->whereIn('type', [
                    ContractAdjustment::TYPE_BREAKAGE,
                    ContractAdjustment::TYPE_MISSING,
                ])->sum('amount');

                $textClass = $breakages > 0 ? 'text-danger fw-bold' : 'text-muted';

                return '<div class="text-end font-monospace '.$textClass.'">-'.$this->signo_pais().' '.number_format($breakages, 2).'</div>';
            })
            ->addColumn('garantia_neta_devolver', function ($c) {
                $guarantee = round((float) $c->total * 0.20, 2);
                $breakages = (float) $c->adjustments->whereIn('type', [
                    ContractAdjustment::TYPE_BREAKAGE,
                    ContractAdjustment::TYPE_MISSING,
                ])->sum('amount');
                $net = max(0, $guarantee - $breakages);

                return '<div class="text-end font-monospace fw-bold text-success">'
                    .$this->signo_pais().' '.number_format($net, 2)
                    .'</div>';
            })
            ->addColumn('estado_garantia', function ($c) {
                $breakages = (float) $c->adjustments->whereIn('type', [
                    ContractAdjustment::TYPE_BREAKAGE,
                    ContractAdjustment::TYPE_MISSING,
                ])->sum('amount');

                if ((int) $c->estado === Contract::STATUS_COMPLETED || $c->status === Contract::STATUS_STR_CERRADO) {
                    return '<span class="badge bg-secondary text-white"><i class="ri-check-line me-1"></i> Liquidada</span>';
                }

                if ($breakages > 0) {
                    return '<span class="badge bg-warning text-dark"><i class="ri-alert-line me-1"></i> Con Descuentos</span>';
                }

                return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="ri-shield-check-line me-1"></i> Retenida Intacta</span>';
            })
            ->rawColumns(['codigo_contrato', 'cliente_info', 'fecha_evento_fmt', 'total_contrato', 'garantia_20', 'ajustes_descontados', 'garantia_neta_devolver', 'estado_garantia'])
            ->toJson();
    }

    /**
     * Reporte 4: Ajustes por Roturas y Faltantes de Menaje (DataTables Server-Side).
     */
    public function getAdjustments(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $clientId = $request->input('client_id');

        $query = ContractAdjustment::with(['contract.client', 'product', 'user'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when(! $type, function ($q) {
                // Por defecto muestra roturas y faltantes primero
                $q->whereIn('type', [
                    ContractAdjustment::TYPE_BREAKAGE,
                    ContractAdjustment::TYPE_MISSING,
                    ContractAdjustment::TYPE_ADDITIONAL,
                ]);
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($clientId, function ($q) use ($clientId) {
                $q->whereHas('contract', fn ($sub) => $sub->where('idcliente', $clientId));
            })
            ->orderByDesc('id');

        return datatables()->of($query)
            ->addColumn('fecha_fmt', function ($a) {
                return $a->created_at ? $a->created_at->format('d/m/Y H:i') : '-';
            })
            ->addColumn('contrato_info', function ($a) {
                return '<strong>'.e($a->contract?->contract_number ?? 'N/A').'</strong>';
            })
            ->addColumn('cliente_info', function ($a) {
                return e($a->contract?->client?->nombres ?: 'N/A');
            })
            ->addColumn('tipo_badge', function ($a) {
                return $a->type_badge;
            })
            ->addColumn('producto_descripcion', function ($a) {
                $prod = $a->product ? '<strong>'.e($a->product->nombre ?: $a->product->descripcion).'</strong><br>' : '';

                return $prod.'<small class="text-muted">'.e($a->description).'</small>';
            })
            ->addColumn('registrado_por', function ($a) {
                return e($a->user?->nombres ?: 'Sistema');
            })
            ->editColumn('amount', function ($a) {
                return '<div class="text-end fw-bold font-monospace text-danger">-'.$this->signo_pais().' '.number_format((float) $a->amount, 2).'</div>';
            })
            ->rawColumns(['fecha_fmt', 'contrato_info', 'cliente_info', 'tipo_badge', 'producto_descripcion', 'registrado_por', 'amount'])
            ->toJson();
    }

    /**
     * Reporte 5: Recaudación por Cuota y Medio de Pago (DataTables Server-Side).
     */
    public function getRevenueByPaymentMethod(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $payModeId = $request->input('pay_mode_id');
        $invoicedFilter = $request->input('invoiced_filter'); // invoiced, unbilled, all

        $query = ContractInstallment::with(['contract.client', 'payMode', 'archingCash', 'billing.typeDocument'])
            ->where(function ($q) {
                $q->where('estado', ContractInstallment::STATUS_PAID)
                    ->orWhereNotNull('fecha_pago');
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('fecha_pago', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('fecha_pago', '<=', $dateTo))
            ->when($payModeId, fn ($q) => $q->where('idpago', $payModeId))
            ->when($invoicedFilter === 'invoiced', fn ($q) => $q->whereNotNull('billing_id'))
            ->when($invoicedFilter === 'unbilled', fn ($q) => $q->whereNull('billing_id'))
            ->orderByDesc('fecha_pago');

        return datatables()->of($query)
            ->addColumn('fecha_pago_fmt', function ($i) {
                return $i->fecha_pago ? Carbon::parse($i->fecha_pago)->format('d/m/Y') : '-';
            })
            ->addColumn('contrato_info', function ($i) {
                return '<strong>'.e($i->contract?->contract_number ?? 'N/A').'</strong>';
            })
            ->addColumn('cliente_info', function ($i) {
                return e($i->contract?->client?->nombres ?: 'N/A');
            })
            ->addColumn('cuota_info', function ($i) {
                return 'Cuota #'.e($i->numero_cuota).' ('.e($i->descripcion ?: 'Abono').')';
            })
            ->addColumn('medio_pago_badge', function ($i) {
                $desc = $i->payMode?->descripcion ?: $i->metodo_pago ?: 'Efectivo';

                return '<span class="badge bg-light text-dark border">'.e($desc).'</span>';
            })
            ->addColumn('caja_arqueo_info', function ($i) {
                if ($i->archingCash) {
                    return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Arqueo #'.$i->archingCash->id.'</span>';
                }

                return '<span class="text-muted small">No asignado</span>';
            })
            ->addColumn('facturacion_info', function ($i) {
                if ($i->billing) {
                    $b = $i->billing;

                    return '<span class="badge bg-success-subtle text-success border border-success-subtle" title="Facturado">'
                        .e($b->serie.'-'.$b->correlativo).'</span>';
                }

                return '<span class="badge bg-secondary-subtle text-secondary">Recibo Simple (Caja)</span>';
            })
            ->addColumn('reconciliation_badge', function ($i) {
                $key = $i->reconciliation_key ?: "INST-{$i->id}";

                return '<span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">'.e($key).'</span>';
            })
            ->editColumn('amount', function ($i) {
                return '<div class="text-end fw-bold font-monospace text-success">'
                    .$this->signo_pais().' '.number_format((float) $i->monto, 2)
                    .'</div>';
            })
            ->rawColumns(['fecha_pago_fmt', 'contrato_info', 'cliente_info', 'cuota_info', 'medio_pago_badge', 'caja_arqueo_info', 'facturacion_info', 'reconciliation_badge', 'amount'])
            ->toJson();
    }

    /**
     * Reporte 6: Transacciones Preliminares (Convertidas vs. Perdidas) (DataTables Server-Side).
     */
    public function getPreliminaryTransactions(Request $request): JsonResponse
    {
        $statusFilter = $request->input('conversion_status', 'all'); // converted, lost, negotiation, all
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Contract::with(['client', 'user'])
            ->where(function ($q) {
                $q->whereNotNull('preliminary_number')
                    ->orWhere('source', Contract::SOURCE_WEB)
                    ->orWhere('status', Contract::STATUS_STR_PRELIMINAR)
                    ->orWhere('contract_number', 'LIKE', 'PRE-%');
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('id');

        return datatables()->of($query)
            ->addColumn('codigo_preliminar', function ($c) {
                $cod = $c->preliminary_number ?: $c->contract_number;

                return '<span class="badge bg-light text-dark border font-monospace">'.e($cod).'</span>';
            })
            ->addColumn('contrato_oficial', function ($c) {
                if (str_starts_with((string) $c->contract_number, 'CON-') || in_array($c->status, [Contract::STATUS_STR_VIGENTE, Contract::STATUS_STR_CERRADO], true)) {
                    return '<strong class="text-primary">'.e($c->contract_number).'</strong>';
                }

                return '<span class="text-muted small">Pendiente Formalizar</span>';
            })
            ->addColumn('cliente_info', function ($c) {
                return e($c->client?->nombres ?: 'Público General');
            })
            ->addColumn('fecha_evento_fmt', function ($c) {
                return $c->fecha_evento ? $c->fecha_evento->format('d/m/Y') : '-';
            })
            ->addColumn('monto_estimado', function ($c) {
                return '<div class="text-end font-monospace fw-bold">'
                    .$this->signo_pais().' '.number_format((float) $c->total, 2)
                    .'</div>';
            })
            ->addColumn('estado_conversion', function ($c) {
                $isConverted = str_starts_with((string) $c->contract_number, 'CON-')
                    || in_array($c->status, [Contract::STATUS_STR_VIGENTE, Contract::STATUS_STR_CERRADO], true)
                    || (int) $c->estado === Contract::STATUS_SIGNED;

                $isLost = (int) $c->estado === Contract::STATUS_VOIDED
                    || $c->status === Contract::STATUS_STR_ANULADO;

                if ($isConverted) {
                    return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="ri-checkbox-circle-line me-1"></i> Convertida a Contrato</span>';
                } elseif ($isLost) {
                    return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="ri-close-circle-line me-1"></i> Perdida / Descartada</span>';
                } else {
                    return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="ri-time-line me-1"></i> En Negociación</span>';
                }
            })
            ->addColumn('origen_badge', function ($c) {
                return match ($c->source) {
                    Contract::SOURCE_WEB => '<span class="badge bg-info-subtle text-info"><i class="ri-global-line me-1"></i> Web Cotizador</span>',
                    default => '<span class="badge bg-light text-secondary border"><i class="ri-user-line me-1"></i> Interno / Counter</span>',
                };
            })
            ->rawColumns(['codigo_preliminar', 'contrato_oficial', 'cliente_info', 'fecha_evento_fmt', 'monto_estimado', 'estado_conversion', 'origen_badge'])
            ->toJson();
    }
}
