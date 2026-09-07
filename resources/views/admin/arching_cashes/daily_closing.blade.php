@extends('admin.layout')

@section('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, .08);
        background: #fff;
        padding: 1.15rem 1.25rem;
        transition: transform .15s ease, box-shadow .15s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .4rem 1rem rgba(0, 0, 0, .06);
    }
    .kpi-icon-circle {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .kpi-title {
        font-size: 0.82rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: .5px;
        margin-bottom: 0.25rem;
    }
    .kpi-amount {
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.15;
    }
    .kpi-subtitle {
        font-size: 0.78rem;
        font-weight: 500;
    }

    /* Píldoras de filtro rápido */
    .filter-pills-container {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-bottom: 1.1rem;
    }
    .btn-filter-status {
        border-radius: 30px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 0.4rem 0.95rem;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #495057;
        transition: all .15s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .btn-filter-status:hover {
        background: #f8f9fa;
        border-color: #ced4da;
    }
    .btn-filter-status.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
        box-shadow: 0 2px 6px rgba(13, 110, 253, .25);
    }
    .btn-filter-status.active .badge {
        background: #fff !important;
        color: #0d6efd !important;
    }

    /* Tabs modernas */
    .nav-tabs-custom .nav-link {
        font-weight: 600;
        font-size: 0.92rem;
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
        border-radius: 0;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #0d6efd;
    }
    .nav-tabs-custom .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom: 3px solid #0d6efd;
    }

    /* Tarjetas de medios de pago */
    .payment-method-card {
        border-radius: 10px;
        border: 1px solid #e9ecef;
        background: #fdfdfd;
        padding: 1rem;
        transition: border-color .15s ease;
    }
    .payment-method-card:hover {
        border-color: #b1d2ff;
        background: #fff;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Encabezado y Navegación -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.arching_cashes') }}">Arqueos de Caja</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cierre del Día</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold mb-0 text-dark">
                    <i class="ri-calendar-check-line text-primary me-1"></i> Cierre de Caja del Día
                </h3>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                    {{ $todayDate }}
                </span>
                @if($openArching)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="ri-lock-unlock-line me-1"></i> Caja Abierta
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                        <i class="ri-lock-line me-1"></i> Caja Cerrada
                    </span>
                @endif
            </div>
            <div class="text-muted small mt-1">
                <i class="ri-store-2-line me-1"></i> Almacén: <strong>{{ $activeWarehouse?->descripcion ?: 'General' }}</strong>
                <span class="mx-1">•</span>
                <i class="ri-money-dollar-box-line me-1"></i> Caja: <strong>{{ $assignedCash?->descripcion ?: 'Caja Principal' }}</strong>
                <span class="mx-1">•</span>
                <i class="ri-user-line me-1"></i> Responsable: <strong>{{ Auth::user()->nombres }}</strong>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefreshDailyClosing">
                <i class="ri-refresh-line me-1"></i> Actualizar
            </button>
            <a href="{{ route('admin.arching_cashes') }}" class="btn btn-outline-primary btn-sm">
                <i class="ri-list-check-2 me-1"></i> Historial Arqueos
            </a>
            <button type="button" class="btn btn-primary btn-sm btn-print-closing-ticket">
                <i class="ri-printer-line me-1"></i> Imprimir Ticket Cierre
            </button>
        </div>
    </div>

    <!-- 6 Tarjetas KPI del Día (Calculadas vía agregación SQL ultra liviana) -->
    <div class="row g-3 mb-4">
        <!-- 1. Ventas Pagadas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card border-success-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="kpi-title text-success">Ventas Pagadas</span>
                    <div class="kpi-icon-circle bg-success-subtle text-success">
                        <i class="ri-checkbox-circle-line"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-amount text-success" id="kpi_sales_paid_total">
                        {{ $signo }} {{ number_format($summary['sales_paid_total'], 2) }}
                    </div>
                    <div class="kpi-subtitle text-muted mt-1" id="kpi_sales_paid_count">
                        {{ $summary['sales_paid_count'] }} comprobantes cobrados
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ventas Pendientes (Crédito) -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card border-warning-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="kpi-title text-warning-emphasis">Ventas Pendientes</span>
                    <div class="kpi-icon-circle bg-warning-subtle text-warning">
                        <i class="ri-time-line"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-amount text-warning-emphasis" id="kpi_sales_pending_total">
                        {{ $signo }} {{ number_format($summary['sales_pending_total'], 2) }}
                    </div>
                    <div class="kpi-subtitle text-muted mt-1" id="kpi_sales_pending_count">
                        {{ $summary['sales_pending_count'] }} crédito / por cobrar
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Ventas Devueltas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card border-info-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="kpi-title text-info">Devoluciones</span>
                    <div class="kpi-icon-circle bg-info-subtle text-info">
                        <i class="ri-arrow-go-back-line"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-amount text-info" id="kpi_sales_returned_total">
                        -{{ $signo }} {{ number_format($summary['sales_returned_total'], 2) }}
                    </div>
                    <div class="kpi-subtitle text-muted mt-1" id="kpi_sales_returned_count">
                        {{ $summary['sales_returned_count'] }} notas de crédito
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Ventas Anuladas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card border-danger-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="kpi-title text-danger">Anuladas</span>
                    <div class="kpi-icon-circle bg-danger-subtle text-danger">
                        <i class="ri-close-circle-line"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-amount text-danger" id="kpi_sales_cancelled_total">
                        {{ $signo }} {{ number_format($summary['sales_cancelled_total'], 2) }}
                    </div>
                    <div class="kpi-subtitle text-muted mt-1" id="kpi_sales_cancelled_count">
                        {{ $summary['sales_cancelled_count'] }} comprobantes anulados
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Gastos del Día -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card border-danger-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="kpi-title text-danger">Gastos del Día</span>
                    <div class="kpi-icon-circle bg-danger-subtle text-danger">
                        <i class="ri-hand-coin-line"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-amount text-danger" id="kpi_expenses_total">
                        -{{ $signo }} {{ number_format($summary['expenses_total'], 2) }}
                    </div>
                    <div class="kpi-subtitle text-muted mt-1" id="kpi_expenses_count">
                        {{ $summary['expenses_count'] }} egresos registrados
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Balance Efectivo en Caja -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card border-primary bg-primary-subtle text-primary">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="kpi-title text-primary">Efectivo en Caja</span>
                    <div class="kpi-icon-circle bg-primary text-white">
                        <i class="ri-safe-2-line"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-amount text-primary fw-bolder" id="kpi_expected_cash">
                        {{ $signo }} {{ number_format($summary['expected_cash_in_box'], 2) }}
                    </div>
                    <div class="kpi-subtitle text-muted mt-1">
                        Apertura + Ef. - Gastos
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal con Pestañas -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pb-0 pt-3">
            <ul class="nav nav-tabs nav-tabs-custom" id="dailyClosingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#tab-sales" type="button" role="tab" aria-selected="true">
                        <i class="ri-shopping-bag-3-line me-1"></i> Ventas del Día
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#tab-expenses" type="button" role="tab" aria-selected="false">
                        <i class="ri-hand-coin-line me-1"></i> Gastos / Egresos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button" role="tab" aria-selected="false">
                        <i class="ri-wallet-3-line me-1"></i> Métodos de Pago
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reconciliation-tab" data-bs-toggle="tab" data-bs-target="#tab-reconciliation" type="button" role="tab" aria-selected="false">
                        <i class="ri-scales-3-line me-1"></i> Arqueo & Cierre
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="dailyClosingTabsContent">
                
                <!-- TAB 1: VENTAS DEL DÍA -->
                <div class="tab-pane fade show active" id="tab-sales" role="tabpanel" aria-labelledby="sales-tab">
                    <!-- Píldoras de Filtro Rápido -->
                    <div class="filter-pills-container">
                        <button type="button" class="btn-filter-status active" data-status="all">
                            Todas las Ventas 
                            <span class="badge bg-secondary text-white" id="badge-pill-all">
                                {{ $summary['sales_paid_count'] + $summary['sales_pending_count'] + $summary['sales_returned_count'] + $summary['sales_cancelled_count'] }}
                            </span>
                        </button>
                        <button type="button" class="btn-filter-status" data-status="paid">
                            <i class="ri-checkbox-circle-fill text-success"></i> Pagadas 
                            <span class="badge bg-success text-white" id="badge-pill-paid">{{ $summary['sales_paid_count'] }}</span>
                        </button>
                        <button type="button" class="btn-filter-status" data-status="pending">
                            <i class="ri-time-fill text-warning"></i> Pendientes / Crédito 
                            <span class="badge bg-warning text-dark" id="badge-pill-pending">{{ $summary['sales_pending_count'] }}</span>
                        </button>
                        <button type="button" class="btn-filter-status" data-status="returned">
                            <i class="ri-arrow-go-back-fill text-info"></i> Devueltas (N.C.) 
                            <span class="badge bg-info text-white" id="badge-pill-returned">{{ $summary['sales_returned_count'] }}</span>
                        </button>
                        <button type="button" class="btn-filter-status" data-status="cancelled">
                            <i class="ri-close-circle-fill text-danger"></i> Anuladas 
                            <span class="badge bg-danger text-white" id="badge-pill-cancelled">{{ $summary['sales_cancelled_count'] }}</span>
                        </button>
                    </div>

                    <!-- Tabla de Ventas (Server-Side) -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-daily-sales">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Hora</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th class="text-center">Medio Pago</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Cargado asíncronamente vía AJAX DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: GASTOS DEL DÍA -->
                <div class="tab-pane fade" id="tab-expenses" role="tabpanel" aria-labelledby="expenses-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Egresos Operativos Registrados Hoy</h6>
                            <small class="text-muted">Gastos de caja chica, fletes, suministros o retiros menores</small>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegisterExpense">
                            <i class="ri-add-line me-1"></i> Registrar Gasto
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-daily-expenses">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Hora</th>
                                    <th>Comprobante</th>
                                    <th>Motivo / Concepto</th>
                                    <th>Beneficiario</th>
                                    <th class="text-center">Medio</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Cargado asíncronamente vía AJAX DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: MÉTODOS DE PAGO -->
                <div class="tab-pane fade" id="tab-payments" role="tabpanel" aria-labelledby="payments-tab">
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-1">Recaudación por Medio de Pago</h6>
                        <small class="text-muted">Distribución de cobros realizados durante la jornada actual</small>
                    </div>

                    <div class="row g-3">
                        @foreach($summary['payment_methods'] as $key => $method)
                        <div class="col-md-4 col-sm-6">
                            <div class="payment-method-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="kpi-icon-circle bg-{{ $method['color'] }}-subtle text-{{ $method['color'] }}" style="width: 36px; height: 36px; font-size: 1.1rem;">
                                            <i class="{{ $method['icon'] }}"></i>
                                        </div>
                                        <span class="fw-bold text-dark">{{ $method['label'] }}</span>
                                    </div>
                                    <span class="badge bg-light text-secondary border">{{ $method['count'] }} op.</span>
                                </div>
                                <div class="text-end">
                                    <span class="fs-4 fw-bold text-dark font-monospace">
                                        {{ $signo }} {{ number_format($method['total'], 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- TAB 4: ARQUEO & CIERRE FORMAL -->
                <div class="tab-pane fade" id="tab-reconciliation" role="tabpanel" aria-labelledby="reconciliation-tab">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="border rounded-3 p-4 bg-light mb-4">
                                <h5 class="fw-bold text-dark mb-3">
                                    <i class="ri-scales-3-line text-primary me-2"></i> Conciliación de Efectivo en Caja
                                </h5>

                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle mb-0">
                                        <tbody>
                                            <tr class="border-bottom">
                                                <td class="text-muted py-2">Monto de Apertura (Saldo Inicial)</td>
                                                <td class="text-end fw-bold py-2 font-monospace">
                                                    {{ $signo }} {{ number_format($summary['opening_amount'], 2) }}
                                                </td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted py-2">
                                                    (+) Ventas Pagadas en Efectivo
                                                    <small class="d-block text-secondary">Ingresos reales al cajón de dinero</small>
                                                </td>
                                                <td class="text-end fw-bold text-success py-2 font-monospace">
                                                    +{{ $signo }} {{ number_format($summary['payment_methods']['Efectivo']['total'] ?? $summary['sales_paid_total'], 2) }}
                                                </td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted py-2">
                                                    (-) Egresos / Gastos en Efectivo
                                                    <small class="d-block text-secondary">Gastos operativos pagados de caja</small>
                                                </td>
                                                <td class="text-end fw-bold text-danger py-2 font-monospace" id="close_expenses_cash">
                                                    -{{ $signo }} {{ number_format($summary['expenses_cash_total'], 2) }}
                                                </td>
                                            </tr>
                                            <tr class="table-primary rounded">
                                                <td class="fw-bold text-primary py-3 fs-5">
                                                    (=) Total Efectivo Esperado en Caja
                                                </td>
                                                <td class="text-end fw-bold text-primary py-3 fs-4 font-monospace" id="close_final_cash">
                                                    {{ $signo }} {{ number_format($summary['expected_cash_in_box'], 2) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="text-center">
                                @if($openArching)
                                    <div class="alert alert-info border-0 mb-3 small">
                                        <i class="ri-information-line me-1"></i>
                                        Al cerrar la caja, se actualizará el monto final y se registrará el fin de turno de <strong>{{ Auth::user()->nombres }}</strong>.
                                    </div>
                                    <button type="button" class="btn btn-danger btn-lg px-5 shadow-sm" id="btnActionCloseCash">
                                        <i class="ri-lock-line me-1"></i> Realizar Cierre de Caja del Día
                                    </button>
                                @else
                                    <div class="alert alert-secondary border-0 mb-0">
                                        <i class="ri-checkbox-circle-line text-success me-1"></i>
                                        La caja para esta jornada ya se encuentra cerrada. Puede imprimir el ticket de comprobación.
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-primary btn-print-closing-ticket">
                                            <i class="ri-printer-line me-1"></i> Imprimir Ticket de Cierre
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal para Registrar Gasto -->
@include('admin.arching_cashes.modal-expense')
@endsection

@section('scripts')
@include('admin.arching_cashes.js-daily-closing')
@endsection
