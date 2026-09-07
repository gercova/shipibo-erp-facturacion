<?php

namespace App\Http\Controllers;

use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Business;
use App\Models\Cash;
use App\Models\DetailPayment;
use App\Models\Expense;
use App\Models\SaleNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyCashClosingController extends Controller
{
    /**
     * Muestra la vista principal del Cierre de Caja del Día.
     */
    public function index(Request $request)
    {
        $context = $this->resolveClosingContext();
        $summary = $this->calculateDailySummary($context);

        return view('admin.arching_cashes.daily_closing', [
            'todayDate' => Carbon::today()->format('d/m/Y'),
            'todayIso' => Carbon::today()->toDateString(),
            'signo' => $this->signo_pais(),
            'context' => $context,
            'summary' => $summary,
            'openArching' => $context['openArching'],
            'assignedCash' => $context['assignedCash'],
            'activeWarehouse' => $context['warehouse'],
        ]);
    }

    /**
     * Endpoint AJAX: Retorna datos de ventas del día en formato DataTables Server-Side.
     */
    public function sales_data(Request $request)
    {
        $context = $this->resolveClosingContext();
        $statusFilter = $request->input('status_filter', 'all');
        $today = Carbon::today()->toDateString();

        // 1. Consulta de Facturación (Billings) del día
        $billings = DB::table('billings')
            ->join('type_documents', 'billings.idtipo_comprobante', '=', 'type_documents.id')
            ->leftJoin('clients', 'billings.idcliente', '=', 'clients.id')
            ->leftJoin('pay_modes', 'billings.idpago', '=', 'pay_modes.id')
            ->whereDate('billings.fecha_emision', $today)
            ->when($context['warehouseId'] > 0, function ($q) use ($context) {
                $q->where('billings.idalmacen', $context['warehouseId']);
            })
            ->selectRaw("
                'billing' as origin_type,
                billings.id as origin_id,
                billings.fecha_emision as fecha,
                TIME_FORMAT(billings.hora, '%H:%i:%s') as hora,
                CONCAT(billings.serie, '-', billings.correlativo) as documento,
                type_documents.descripcion as tipo_documento,
                type_documents.codigo as tipo_documento_codigo,
                COALESCE(clients.nombres, 'PÚBLICO GENERAL') as cliente,
                COALESCE(clients.nro_documento, '') as cliente_doc,
                COALESCE(pay_modes.descripcion, billings.sunat_forma_pago, 'Contado') as metodo_pago,
                billings.modo_pago,
                billings.anulado,
                billings.total,
                CASE
                    WHEN billings.anulado = 1 THEN 'cancelled'
                    WHEN type_documents.codigo = '07' THEN 'returned'
                    WHEN billings.modo_pago = 2 OR billings.sunat_forma_pago = 'Credito' THEN 'pending'
                    ELSE 'paid'
                END as classification_status
            ");

        // 2. Consulta de Notas de Venta del día
        $saleNotes = DB::table('sale_notes')
            ->leftJoin('clients', 'sale_notes.idcliente', '=', 'clients.id')
            ->whereDate('sale_notes.fecha_emision', $today)
            ->when($context['warehouseId'] > 0, function ($q) use ($context) {
                $q->whereIn('sale_notes.idarqueocaja', function ($sub) use ($context) {
                    $sub->select('id')->from('arching_cashes')->where('idalmacen', $context['warehouseId']);
                });
            })
            ->selectRaw("
                'sale_note' as origin_type,
                sale_notes.id as origin_id,
                sale_notes.fecha_emision as fecha,
                TIME_FORMAT(sale_notes.hora, '%H:%i:%s') as hora,
                CONCAT(sale_notes.serie, '-', sale_notes.correlativo) as documento,
                'NOTA DE VENTA' as tipo_documento,
                '02' as tipo_documento_codigo,
                COALESCE(clients.nombres, 'PÚBLICO GENERAL') as cliente,
                COALESCE(clients.nro_documento, '') as cliente_doc,
                CASE WHEN sale_notes.modo_pago = 2 THEN 'Crédito' ELSE 'Contado' END as metodo_pago,
                sale_notes.modo_pago,
                CASE WHEN sale_notes.estado = 2 THEN 1 ELSE 0 END as anulado,
                sale_notes.total,
                CASE
                    WHEN sale_notes.estado = 2 THEN 'cancelled'
                    WHEN sale_notes.estado = 0 OR sale_notes.modo_pago = 2 THEN 'pending'
                    ELSE 'paid'
                END as classification_status
            ");

        $unionQuery = DB::query()->fromSub($billings->unionAll($saleNotes), 'today_sales');

        // Filtrado por píldora de clasificación seleccionada
        if ($statusFilter !== 'all') {
            $unionQuery->where('classification_status', $statusFilter);
        }

        return datatables()
            ->of($unionQuery)
            ->editColumn('hora', function ($row) {
                return '<span class="text-secondary small fw-medium">' . e($row->hora ?: '--:--') . '</span>';
            })
            ->addColumn('documento_info', function ($row) {
                $badgeClass = match ($row->tipo_documento_codigo) {
                    '01' => 'bg-primary-subtle text-primary',
                    '03' => 'bg-info-subtle text-info',
                    '07' => 'bg-warning-subtle text-warning',
                    default => 'bg-secondary-subtle text-secondary',
                };

                return '<div class="d-flex flex-column">'
                    . '<span class="fw-bold text-dark">' . e($row->documento) . '</span>'
                    . '<span class="badge ' . $badgeClass . ' text-uppercase" style="width: fit-content; font-size: 0.68rem; padding: 2px 6px;">'
                    . e($row->tipo_documento)
                    . '</span>'
                    . '</div>';
            })
            ->addColumn('cliente_info', function ($row) {
                $doc = ! empty($row->cliente_doc) ? '<small class="text-muted d-block">' . e($row->cliente_doc) . '</small>' : '';
                return '<div><div class="fw-semibold text-truncate" style="max-width: 220px;" title="' . e($row->cliente) . '">' . e($row->cliente) . '</div>' . $doc . '</div>';
            })
            ->editColumn('metodo_pago', function ($row) {
                return '<span class="badge bg-light text-dark border">' . e(ucfirst($row->metodo_pago)) . '</span>';
            })
            ->addColumn('status_badge', function ($row) {
                return match ($row->classification_status) {
                    'paid' => '<span class="badge bg-success-subtle text-success px-2 py-1"><i class="ri-checkbox-circle-line me-1"></i>Pagada</span>',
                    'pending' => '<span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1"><i class="ri-time-line me-1"></i>Pendiente</span>',
                    'returned' => '<span class="badge bg-info-subtle text-info px-2 py-1"><i class="ri-arrow-go-back-line me-1"></i>Devuelta</span>',
                    'cancelled' => '<span class="badge bg-danger-subtle text-danger px-2 py-1"><i class="ri-close-circle-line me-1"></i>Anulada</span>',
                    default => '<span class="badge bg-secondary-subtle text-secondary px-2 py-1">Otro</span>',
                };
            })
            ->editColumn('total', function ($row) {
                $isNegative = $row->classification_status === 'returned' || $row->classification_status === 'cancelled';
                $sign = $isNegative ? '-' : '';
                $textClass = match ($row->classification_status) {
                    'cancelled' => 'text-decoration-line-through text-danger',
                    'returned' => 'text-info',
                    'pending' => 'text-warning-emphasis',
                    default => 'text-success fw-bold',
                };

                return '<div class="text-end ' . $textClass . ' font-monospace">'
                    . $sign . $this->signo_pais() . ' ' . number_format((float) $row->total, 2, '.', '')
                    . '</div>';
            })
            ->rawColumns(['hora', 'documento_info', 'cliente_info', 'metodo_pago', 'status_badge', 'total'])
            ->toJson();
    }

    /**
     * Endpoint AJAX: Retorna datos de gastos del día en formato DataTables Server-Side.
     */
    public function expenses_data(Request $request)
    {
        $context = $this->resolveClosingContext();
        $today = Carbon::today()->toDateString();

        $expenses = Expense::query()
            ->with(['user:id,nombres'])
            ->whereDate('fecha', $today)
            ->where('estado', 1)
            ->when($context['warehouseId'] > 0, function ($q) use ($context) {
                $q->where('idalmacen', $context['warehouseId']);
            })
            ->orderByDesc('id');

        return datatables()
            ->of($expenses)
            ->editColumn('hora', function ($expense) {
                return '<span class="text-secondary small fw-medium">' . e($expense->hora ?: '--:--') . '</span>';
            })
            ->addColumn('comprobante_info', function ($expense) {
                $nro = $expense->nro_comprobante ? ' ' . e($expense->nro_comprobante) : '';
                return '<span class="badge bg-light text-dark border">' . e($expense->tipo_comprobante) . $nro . '</span>';
            })
            ->editColumn('motivo', function ($expense) {
                $obs = $expense->observaciones ? '<small class="text-muted d-block">' . e($expense->observaciones) . '</small>' : '';
                return '<div><strong class="text-dark">' . e($expense->motivo) . '</strong>' . $obs . '</div>';
            })
            ->editColumn('beneficiario', function ($expense) {
                return e($expense->beneficiario ?: '-');
            })
            ->editColumn('metodo_pago', function ($expense) {
                return '<span class="badge bg-secondary-subtle text-secondary">' . e($expense->metodo_pago) . '</span>';
            })
            ->editColumn('monto', function ($expense) {
                return '<div class="text-end text-danger fw-bold font-monospace">-' . $this->signo_pais() . ' ' . number_format((float) $expense->monto, 2, '.', '') . '</div>';
            })
            ->addColumn('acciones', function ($expense) {
                return '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-expense" data-id="' . $expense->id . '" title="Anular gasto">
                            <i class="ri-delete-bin-line"></i>
                        </button>';
            })
            ->rawColumns(['hora', 'comprobante_info', 'motivo', 'metodo_pago', 'monto', 'acciones'])
            ->toJson();
    }

    /**
     * Registra un nuevo egreso de caja menor para el día de hoy.
     */
    public function store_expense(Request $request)
    {
        if (! $request->ajax()) {
            return response()->json(['status' => false, 'msg' => 'Petición inválida.'], 400);
        }

        $validated = $request->validate([
            'motivo' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'tipo_comprobante' => 'nullable|string|max:50',
            'nro_comprobante' => 'nullable|string|max:50',
            'metodo_pago' => 'nullable|string|max:50',
            'beneficiario' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $context = $this->resolveClosingContext();
        $user = Auth::user();

        $expense = Expense::create([
            'idarqueocaja' => $context['openArching']?->id,
            'idcaja' => $context['assignedCash']?->id,
            'idalmacen' => $context['warehouseId'] ?: null,
            'idusuario' => $user->id,
            'fecha' => Carbon::today()->toDateString(),
            'hora' => date('H:i:s'),
            'tipo_comprobante' => $validated['tipo_comprobante'] ?: 'RECIBO',
            'nro_comprobante' => $validated['nro_comprobante'] ?? null,
            'motivo' => trim($validated['motivo']),
            'monto' => round((float) $validated['monto'], 2),
            'metodo_pago' => $validated['metodo_pago'] ?: 'Efectivo',
            'beneficiario' => $validated['beneficiario'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'estado' => 1,
        ]);

        // Recalcular métricas para actualización en vivo de KPIs
        $newSummary = $this->calculateDailySummary($context);

        return response()->json([
            'status' => true,
            'msg' => 'Gasto registrado correctamente.',
            'expense' => $expense,
            'summary' => $newSummary,
        ]);
    }

    /**
     * Anula un gasto del día de hoy.
     */
    public function delete_expense(Request $request)
    {
        if (! $request->ajax()) {
            return response()->json(['status' => false, 'msg' => 'Petición inválida.'], 400);
        }

        $expense = Expense::where('fecha', Carbon::today()->toDateString())
            ->where('id', (int) $request->input('id'))
            ->first();

        if (! $expense) {
            return response()->json(['status' => false, 'msg' => 'Gasto no encontrado o no pertenece al día de hoy.'], 404);
        }

        $expense->update(['estado' => 0]);

        $context = $this->resolveClosingContext();
        $newSummary = $this->calculateDailySummary($context);

        return response()->json([
            'status' => true,
            'msg' => 'Gasto anulado correctamente.',
            'summary' => $newSummary,
        ]);
    }

    /**
     * Cierre formal de la caja del día si hay arqueo abierto.
     */
    public function close_cash(Request $request)
    {
        if (! $request->ajax()) {
            return response()->json(['status' => false, 'msg' => 'Petición inválida.'], 400);
        }

        $context = $this->resolveClosingContext();
        $arching = $context['openArching'];

        if (! $arching) {
            return response()->json([
                'status' => false,
                'msg' => 'No hay una caja abierta actualmente para cerrar.',
            ], 422);
        }

        if ((int) $arching->idusuario !== (int) Auth::id()) {
            return response()->json([
                'status' => false,
                'msg' => 'Solo el usuario que aperturó esta caja puede realizar el cierre formal.',
            ], 422);
        }

        $summary = $this->calculateDailySummary($context);

        $arching->update([
            'estado' => 2,
            'fecha_fin' => Carbon::today()->toDateString(),
            'monto_final' => (float) $summary['expected_cash_in_box'],
            'total_ventas' => (int) $summary['sales_paid_count'],
        ]);

        return response()->json([
            'status' => true,
            'msg' => 'Caja cerrada correctamente con éxito.',
        ]);
    }

    /**
     * Generación del Ticket PDF de Cierre de Caja del Día.
     */
    public function print_ticket(Request $request)
    {
        $context = $this->resolveClosingContext();
        $summary = $this->calculateDailySummary($context);
        $business = Business::find(1);

        $pdf = Pdf::loadView('admin.arching_cashes.daily_closing_ticket', [
            'business' => $business,
            'user' => Auth::user(),
            'context' => $context,
            'summary' => $summary,
            'signo' => $this->signo_pais(),
            'currentDate' => Carbon::today()->format('d/m/Y'),
            'currentTime' => date('H:i:s'),
        ])->setPaper([0, 0, 226.77, 950.00], 'portrait');

        $fileName = 'cierre-caja-' . date('Ymd') . '-' . ($context['openArching']?->id ?? 'dia') . '.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * Resuelve el contexto activo (Caja asignada, Almacén activo, Arqueo de hoy).
     */
    protected function resolveClosingContext(): array
    {
        $user = Auth::user()->loadMissing(['roles', 'activeWarehouse']);
        $warehouse = $user->activeWarehouse;
        $warehouseId = (int) ($user->idalmacen ?: 0);

        // Resolver caja asignada
        $assignedCash = null;
        if (! empty($user->idcaja)) {
            $assignedCash = Cash::find((int) $user->idcaja);
        }
        if (! $assignedCash) {
            $assignedCash = Cash::orderBy('id')->first();
            if ($assignedCash && (int) $user->idcaja !== (int) $assignedCash->id) {
                $user->idcaja = (int) $assignedCash->id;
                $user->save();
            }
        }

        // Buscar arqueo del día
        $openArching = ArchingCash::query()
            ->with(['cash', 'user'])
            ->where('idusuario', $user->id)
            ->whereDate('fecha_inicio', Carbon::today()->toDateString())
            ->where('estado', 1)
            ->latest('id')
            ->first();

        // Si no hay abierto hoy por el usuario, buscar el último arqueo abierto de la caja
        if (! $openArching && $assignedCash) {
            $openArching = ArchingCash::query()
                ->with(['cash', 'user'])
                ->where('idcaja', $assignedCash->id)
                ->where('estado', 1)
                ->latest('id')
                ->first();
        }

        return [
            'user' => $user,
            'warehouse' => $warehouse,
            'warehouseId' => $warehouseId,
            'assignedCash' => $assignedCash,
            'openArching' => $openArching,
        ];
    }

    /**
     * Calcula los KPIs consolidados del día mediante agregaciones SQL ultrarrápidas.
     */
    protected function calculateDailySummary(array $context): array
    {
        $today = Carbon::today()->toDateString();
        $whId = $context['warehouseId'];

        // 1. Billings del día (Facturas / Boletas / Notas de Crédito)
        $billingPaidQuery = DB::table('billings')
            ->join('type_documents', 'billings.idtipo_comprobante', '=', 'type_documents.id')
            ->whereDate('billings.fecha_emision', $today)
            ->where('billings.anulado', 0)
            ->whereIn('type_documents.codigo', ['01', '03'])
            ->where(function ($q) {
                $q->where('billings.modo_pago', 1)
                    ->orWhere('billings.sunat_forma_pago', 'Contado');
            })
            ->when($whId > 0, fn($q) => $q->where('billings.idalmacen', $whId));

        $billingPaidCount = (clone $billingPaidQuery)->count();
        $billingPaidTotal = (float) (clone $billingPaidQuery)->sum('billings.total');

        $billingPendingQuery = DB::table('billings')
            ->join('type_documents', 'billings.idtipo_comprobante', '=', 'type_documents.id')
            ->whereDate('billings.fecha_emision', $today)
            ->where('billings.anulado', 0)
            ->whereIn('type_documents.codigo', ['01', '03'])
            ->where(function ($q) {
                $q->where('billings.modo_pago', 2)
                    ->orWhere('billings.sunat_forma_pago', 'Credito');
            })
            ->when($whId > 0, fn($q) => $q->where('billings.idalmacen', $whId));

        $billingPendingCount = (clone $billingPendingQuery)->count();
        $billingPendingTotal = (float) (clone $billingPendingQuery)->sum('billings.total');

        $billingReturnedQuery = DB::table('billings')
            ->join('type_documents', 'billings.idtipo_comprobante', '=', 'type_documents.id')
            ->whereDate('billings.fecha_emision', $today)
            ->where('billings.anulado', 0)
            ->where('type_documents.codigo', '07')
            ->when($whId > 0, fn($q) => $q->where('billings.idalmacen', $whId));

        $billingReturnedCount = (clone $billingReturnedQuery)->count();
        $billingReturnedTotal = (float) (clone $billingReturnedQuery)->sum('billings.total');

        $billingCancelledQuery = DB::table('billings')
            ->join('type_documents', 'billings.idtipo_comprobante', '=', 'type_documents.id')
            ->whereDate('billings.fecha_emision', $today)
            ->where('billings.anulado', 1)
            ->when($whId > 0, fn($q) => $q->where('billings.idalmacen', $whId));

        $billingCancelledCount = (clone $billingCancelledQuery)->count();
        $billingCancelledTotal = (float) (clone $billingCancelledQuery)->sum('billings.total');

        // 2. SaleNotes del día (Notas de Venta)
        $saleNotePaidQuery = DB::table('sale_notes')
            ->whereDate('fecha_emision', $today)
            ->where('estado', 1);

        $saleNotePaidCount = (clone $saleNotePaidQuery)->count();
        $saleNotePaidTotal = (float) (clone $saleNotePaidQuery)->sum('total');

        $saleNotePendingQuery = DB::table('sale_notes')
            ->whereDate('fecha_emision', $today)
            ->where(function ($q) {
                $q->where('estado', 0)->orWhere('modo_pago', 2);
            })
            ->where('estado', '!=', 2);

        $saleNotePendingCount = (clone $saleNotePendingQuery)->count();
        $saleNotePendingTotal = (float) (clone $saleNotePendingQuery)->sum('total');

        $saleNoteCancelledQuery = DB::table('sale_notes')
            ->whereDate('fecha_emision', $today)
            ->where('estado', 2);

        $saleNoteCancelledCount = (clone $saleNoteCancelledQuery)->count();
        $saleNoteCancelledTotal = (float) (clone $saleNoteCancelledQuery)->sum('total');

        // Totales clasificados de Ventas
        $salesPaidCount = $billingPaidCount + $saleNotePaidCount;
        $salesPaidTotal = $billingPaidTotal + $saleNotePaidTotal;

        $salesPendingCount = $billingPendingCount + $saleNotePendingCount;
        $salesPendingTotal = $billingPendingTotal + $saleNotePendingTotal;

        $salesReturnedCount = $billingReturnedCount;
        $salesReturnedTotal = $billingReturnedTotal;

        $salesCancelledCount = $billingCancelledCount + $saleNoteCancelledCount;
        $salesCancelledTotal = $billingCancelledTotal + $saleNoteCancelledTotal;

        // 3. Gastos del día (Expenses)
        $expensesQuery = DB::table('expenses')
            ->whereDate('fecha', $today)
            ->where('estado', 1)
            ->when($whId > 0, fn($q) => $q->where('idalmacen', $whId));

        $expensesCount = (clone $expensesQuery)->count();
        $expensesTotal = (float) (clone $expensesQuery)->sum('monto');
        $expensesCashTotal = (float) (clone $expensesQuery)->where('metodo_pago', 'Efectivo')->sum('monto');

        // 4. Monto inicial de caja abierta
        $openingAmount = (float) ($context['openArching']?->monto_inicial ?? 0);

        // 5. Desglose de ingresos por método de pago
        $paymentMethods = $this->calculateDailyPaymentMethods($today, $context);

        // Efectivo ingresado por ventas
        $cashSalesIn = (float) ($paymentMethods['Efectivo']['total'] ?? $salesPaidTotal);

        // Balance de efectivo esperado en caja: Apertura + Ventas Efectivo - Gastos Efectivo
        $expectedCashInBox = max(0, $openingAmount + $cashSalesIn - $expensesCashTotal);

        // Flujo neto del día (Ventas Cobradas - Devoluciones - Gastos)
        $netDayFlow = $salesPaidTotal - $salesReturnedTotal - $expensesTotal;

        return [
            'opening_amount' => $openingAmount,
            'sales_paid_count' => $salesPaidCount,
            'sales_paid_total' => $salesPaidTotal,
            'sales_pending_count' => $salesPendingCount,
            'sales_pending_total' => $salesPendingTotal,
            'sales_returned_count' => $salesReturnedCount,
            'sales_returned_total' => $salesReturnedTotal,
            'sales_cancelled_count' => $salesCancelledCount,
            'sales_cancelled_total' => $salesCancelledTotal,
            'expenses_count' => $expensesCount,
            'expenses_total' => $expensesTotal,
            'expenses_cash_total' => $expensesCashTotal,
            'expected_cash_in_box' => $expectedCashInBox,
            'net_day_flow' => $netDayFlow,
            'payment_methods' => $paymentMethods,
        ];
    }

    /**
     * Consolida los montos cobrados en el día agrupados por método de pago.
     */
    protected function calculateDailyPaymentMethods(string $today, array $context): array
    {
        $methods = [
            'Efectivo' => ['label' => 'Efectivo', 'total' => 0.0, 'count' => 0, 'icon' => 'ri-money-dollar-circle-line', 'color' => 'success'],
            'Yape' => ['label' => 'Yape', 'total' => 0.0, 'count' => 0, 'icon' => 'ri-smartphone-line', 'color' => 'purple'],
            'Plin' => ['label' => 'Plin', 'total' => 0.0, 'count' => 0, 'icon' => 'ri-qr-code-line', 'color' => 'info'],
            'Tarjeta' => ['label' => 'Tarjeta', 'total' => 0.0, 'count' => 0, 'icon' => 'ri-bank-card-line', 'color' => 'primary'],
            'Transferencia' => ['label' => 'Transferencia', 'total' => 0.0, 'count' => 0, 'icon' => 'ri-exchange-line', 'color' => 'warning'],
            'Otros' => ['label' => 'Otros', 'total' => 0.0, 'count' => 0, 'icon' => 'ri-wallet-3-line', 'color' => 'secondary'],
        ];

        // 1. Cobros en detail_payments registrados hoy
        $detailPaymentsQuery = DB::table('detail_payments')
            ->join('pay_modes', 'detail_payments.idpago', '=', 'pay_modes.id')
            ->where('detail_payments.estado', 1);

        if (! empty($context['openArching'])) {
            $detailPaymentsQuery->where(function ($q) use ($context, $today) {
                $q->where('detail_payments.idarqueocaja', $context['openArching']->id)
                  ->orWhereDate('detail_payments.created_at', $today);
            });
        } else {
            $detailPaymentsQuery->whereDate('detail_payments.created_at', $today);
        }

        $detailPayments = $detailPaymentsQuery
            ->select('pay_modes.descripcion as modo', 'detail_payments.monto')
            ->get();

        if ($detailPayments->isNotEmpty()) {
            foreach ($detailPayments as $dp) {
                $category = $this->classifyPaymentMethod($dp->modo);
                $methods[$category]['total'] += (float) $dp->monto;
                $methods[$category]['count'] += 1;
            }
        } else {
            // Alternativa: Si no hay en detail_payments, consultar por billings y sale_notes pagadas hoy
            $billings = DB::table('billings')
                ->leftJoin('pay_modes', 'billings.idpago', '=', 'pay_modes.id')
                ->join('type_documents', 'billings.idtipo_comprobante', '=', 'type_documents.id')
                ->whereDate('billings.fecha_emision', $today)
                ->where('billings.anulado', 0)
                ->whereIn('type_documents.codigo', ['01', '03'])
                ->where(function ($q) {
                    $q->where('billings.modo_pago', 1)->orWhere('billings.sunat_forma_pago', 'Contado');
                })
                ->select('pay_modes.descripcion as modo', 'billings.total', 'billings.payment_breakdown')
                ->get();

            foreach ($billings as $b) {
                $breakdown = ! empty($b->payment_breakdown) ? json_decode($b->payment_breakdown, true) : null;
                if (is_array($breakdown) && ! empty($breakdown)) {
                    foreach ($breakdown as $item) {
                        $cat = $this->classifyPaymentMethod($item['method'] ?? 'Efectivo');
                        $methods[$cat]['total'] += (float) ($item['amount'] ?? 0);
                        $methods[$cat]['count'] += 1;
                    }
                } else {
                    $cat = $this->classifyPaymentMethod($b->modo ?: 'Efectivo');
                    $methods[$cat]['total'] += (float) $b->total;
                    $methods[$cat]['count'] += 1;
                }
            }

            $saleNotes = DB::table('sale_notes')
                ->whereDate('fecha_emision', $today)
                ->where('estado', 1)
                ->select('total', 'payment_breakdown')
                ->get();

            foreach ($saleNotes as $sn) {
                $breakdown = ! empty($sn->payment_breakdown) ? json_decode($sn->payment_breakdown, true) : null;
                if (is_array($breakdown) && ! empty($breakdown)) {
                    foreach ($breakdown as $item) {
                        $cat = $this->classifyPaymentMethod($item['method'] ?? 'Efectivo');
                        $methods[$cat]['total'] += (float) ($item['amount'] ?? 0);
                        $methods[$cat]['count'] += 1;
                    }
                } else {
                    $methods['Efectivo']['total'] += (float) $sn->total;
                    $methods['Efectivo']['count'] += 1;
                }
            }
        }

        return $methods;
    }

    /**
     * Normaliza la etiqueta del medio de pago.
     */
    protected function classifyPaymentMethod(?string $method): string
    {
        $normalized = mb_strtolower(trim((string) $method), 'UTF-8');

        return match (true) {
            str_contains($normalized, 'efectivo') || str_contains($normalized, 'cash') || str_contains($normalized, 'contado') => 'Efectivo',
            str_contains($normalized, 'yape') => 'Yape',
            str_contains($normalized, 'plin') => 'Plin',
            str_contains($normalized, 'tarjeta') || str_contains($normalized, 'visa') || str_contains($normalized, 'master') || str_contains($normalized, 'debito') || str_contains($normalized, 'crédito') => 'Tarjeta',
            str_contains($normalized, 'transfer') || str_contains($normalized, 'deposito') || str_contains($normalized, 'banco') => 'Transferencia',
            default => 'Otros',
        };
    }
}
