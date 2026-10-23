@extends('admin.layout')

@section('styles')
<style>
    .kpi-report-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kpi-report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .kpi-report-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .nav-tabs-report .nav-link {
        font-weight: 600;
        font-size: 0.9rem;
        color: #64748b;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.85rem 1.25rem;
        border-radius: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .nav-tabs-report .nav-link:hover {
        color: #0d6efd;
    }
    .nav-tabs-report .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom: 3px solid #0d6efd;
    }
    .filter-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.25rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.contracts') }}">Contratos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reportes Analíticos</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="ri-file-chart-line text-primary me-2"></i> Reportes Analíticos de Contratos & Conciliación
            </h3>
            <p class="text-muted small mb-0">Auditoría integral de ingresos por cuotas, garantías, ajustes de menaje y formalización preliminar.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.daily_closing') }}" class="btn btn-outline-primary btn-sm">
                <i class="ri-calendar-check-line me-1"></i> Cierre de Caja del Día
            </a>
            <a href="{{ route('admin.contracts') }}" class="btn btn-primary btn-sm">
                <i class="ri-file-list-3-line me-1"></i> Gestión de Contratos
            </a>
        </div>
    </div>

    <!-- 6 KPI Cards Resumen Ejecutivo -->
    <div class="row g-3 mb-4">
        <!-- 1. Contratos Totales -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-report-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Contratos Activos</span>
                    <div class="kpi-report-icon bg-primary-subtle text-primary">
                        <i class="ri-file-text-line"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-dark">{{ $kpis['active_contracts'] }}</div>
                <small class="text-muted">{{ $kpis['total_contracts'] }} contratos totales</small>
            </div>
        </div>

        <!-- 2. Cuotas Vencidas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-report-card border-danger-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-danger small fw-semibold">Cuotas Vencidas</span>
                    <div class="kpi-report-icon bg-danger-subtle text-danger">
                        <i class="ri-alarm-warning-line"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-danger">{{ $signo }} {{ number_format($kpis['overdue_amount'], 2) }}</div>
                <small class="text-danger fw-semibold">{{ $kpis['overdue_count'] }} cuotas en mora</small>
            </div>
        </div>

        <!-- 3. Garantías Retenidas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-report-card border-success-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-success small fw-semibold">Garantías (20%)</span>
                    <div class="kpi-report-icon bg-success-subtle text-success">
                        <i class="ri-shield-check-line"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-success">{{ $signo }} {{ number_format($kpis['guarantees_total'], 2) }}</div>
                <small class="text-muted">Fondo contractual activo</small>
            </div>
        </div>

        <!-- 4. Ajustes / Roturas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-report-card border-warning-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-warning-emphasis small fw-semibold">Roturas / Faltantes</span>
                    <div class="kpi-report-icon bg-warning-subtle text-warning">
                        <i class="ri-goblet-line"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-warning-emphasis">{{ $signo }} {{ number_format($kpis['adjustments_total'], 2) }}</div>
                <small class="text-muted">Ajustes post-evento</small>
            </div>
        </div>

        <!-- 5. Transacciones Preliminares -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-report-card border-info-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-info small fw-semibold">Cotizaciones / Web</span>
                    <div class="kpi-report-icon bg-info-subtle text-info">
                        <i class="ri-send-plane-line"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-info">{{ $kpis['preliminaries_total'] }}</div>
                <small class="text-muted">Órdenes preliminares</small>
            </div>
        </div>

        <!-- 6. Tasa de Conversión -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-report-card bg-primary-subtle border-primary-subtle">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-primary small fw-semibold">Tasa Conversión</span>
                    <div class="kpi-report-icon bg-primary text-white">
                        <i class="ri-percent-line"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-primary">{{ $kpis['conversion_rate'] }}%</div>
                <small class="text-primary fw-medium">{{ $kpis['preliminaries_converted'] }} convertidas a contrato</small>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pb-0 pt-3">
            <ul class="nav nav-tabs nav-tabs-report" id="contractReportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-status-nav" data-bs-toggle="tab" data-bs-target="#tab-status" type="button" role="tab">
                        <i class="ri-file-list-3-line"></i> 1. Contratos por Estado
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-installments-nav" data-bs-toggle="tab" data-bs-target="#tab-installments" type="button" role="tab">
                        <i class="ri-time-line"></i> 2. Cuotas Vencidas / Por Vencer
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-guarantees-nav" data-bs-toggle="tab" data-bs-target="#tab-guarantees" type="button" role="tab">
                        <i class="ri-shield-keyhole-line"></i> 3. Garantías Recibidas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-adjustments-nav" data-bs-toggle="tab" data-bs-target="#tab-adjustments" type="button" role="tab">
                        <i class="ri-goblet-line"></i> 4. Roturas y Faltantes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-revenue-nav" data-bs-toggle="tab" data-bs-target="#tab-revenue" type="button" role="tab">
                        <i class="ri-money-dollar-circle-line"></i> 5. Recaudación por Cuota
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-preliminaries-nav" data-bs-toggle="tab" data-bs-target="#tab-preliminaries" type="button" role="tab">
                        <i class="ri-exchange-box-line"></i> 6. Preliminares (Conv. vs Perdidas)
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="contractReportTabsContent">
                
                <!-- TAB 1: CONTRATOS POR ESTADO -->
                <div class="tab-pane fade show active" id="tab-status" role="tabpanel">
                    <div class="filter-panel">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Estado de Contrato</label>
                                <select id="filter_contract_status" class="form-select form-select-sm">
                                    <option value="">Todos los Estados</option>
                                    <option value="1">Firmado / Vigente</option>
                                    <option value="0">Borrador</option>
                                    <option value="2">Completado</option>
                                    <option value="3">Anulado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Cliente</label>
                                <select id="filter_contract_client" class="form-select form-select-sm">
                                    <option value="">Todos los Clientes</option>
                                    @foreach($clients as $cl)
                                        <option value="{{ $cl->id }}">{{ $cl->nombres }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Fecha Emisión Desde</label>
                                <input type="date" id="filter_contract_date_from" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Fecha Emisión Hasta</label>
                                <input type="date" id="filter_contract_date_to" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="button" id="btn_filter_contracts" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-filter-3-line"></i> Filtrar
                                </button>
                                <button type="button" id="btn_reset_contracts" class="btn btn-outline-secondary btn-sm" title="Limpiar">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-report-contracts">
                            <thead class="table-light">
                                <tr>
                                    <th>Contrato</th>
                                    <th>Cliente</th>
                                    <th>Fechas (Emisión / Evento)</th>
                                    <th class="text-end">Total / Cobranza</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: CUOTAS VENCIDAS Y POR VENCER -->
                <div class="tab-pane fade" id="tab-installments" role="tabpanel">
                    <div class="filter-panel">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Vencimiento</label>
                                <select id="filter_installments_due" class="form-select form-select-sm">
                                    <option value="all">Todas las Cuotas Pendientes</option>
                                    <option value="overdue" selected>Solo Cuotas Vencidas (Mora)</option>
                                    <option value="today">Vencen Hoy</option>
                                    <option value="upcoming">Por Vencer (Futuras)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha Límite Desde</label>
                                <input type="date" id="filter_inst_date_from" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha Límite Hasta</label>
                                <input type="date" id="filter_inst_date_to" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btn_filter_installments" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-filter-3-line"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-report-installments">
                            <thead class="table-light">
                                <tr>
                                    <th>Contrato</th>
                                    <th>Cliente</th>
                                    <th>Cuota</th>
                                    <th>Fec. Vencimiento</th>
                                    <th class="text-center">Alerta Mora</th>
                                    <th class="text-end">Monto Pendiente</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: GARANTÍAS RECIBIDAS -->
                <div class="tab-pane fade" id="tab-guarantees" role="tabpanel">
                    <div class="filter-panel">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Cliente</label>
                                <select id="filter_guarantee_client" class="form-select form-select-sm">
                                    <option value="">Todos los Clientes</option>
                                    @foreach($clients as $cl)
                                        <option value="{{ $cl->id }}">{{ $cl->nombres }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha Evento Desde</label>
                                <input type="date" id="filter_guarantee_date_from" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha Evento Hasta</label>
                                <input type="date" id="filter_guarantee_date_to" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btn_filter_guarantees" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-filter-3-line"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-report-guarantees">
                            <thead class="table-light">
                                <tr>
                                    <th>Contrato</th>
                                    <th>Cliente</th>
                                    <th>Fecha Evento</th>
                                    <th class="text-end">Total Contrato</th>
                                    <th class="text-end">Garantía Pactada (20%)</th>
                                    <th class="text-end">Descuentos Roturas</th>
                                    <th class="text-end">Saldo Neto Devolución</th>
                                    <th class="text-center">Estado Garantía</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: AJUSTES POR ROTURAS Y FALTANTES -->
                <div class="tab-pane fade" id="tab-adjustments" role="tabpanel">
                    <div class="filter-panel">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Tipo de Ajuste</label>
                                <select id="filter_adj_type" class="form-select form-select-sm">
                                    <option value="">Roturas, Faltantes y Adicionales</option>
                                    <option value="breakage">Rotura de Cristalería / Menaje</option>
                                    <option value="missing_item">Ítem Faltante / No Devuelto</option>
                                    <option value="additional_consumption">Consumo Adicional</option>
                                    <option value="discount">Descuento</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha Desde</label>
                                <input type="date" id="filter_adj_date_from" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha Hasta</label>
                                <input type="date" id="filter_adj_date_to" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="btn_filter_adjustments" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-filter-3-line"></i> Filtrar Ajustes
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-report-adjustments">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Contrato</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Detalle / Producto</th>
                                    <th>Registrado Por</th>
                                    <th class="text-end">Monto Ajuste</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 5: RECAUDACIÓN POR CUOTA Y MEDIO DE PAGO -->
                <div class="tab-pane fade" id="tab-revenue" role="tabpanel">
                    <div class="filter-panel">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Medio de Pago</label>
                                <select id="filter_rev_pay_mode" class="form-select form-select-sm">
                                    <option value="">Todos los Medios</option>
                                    @foreach($payModes as $pm)
                                        <option value="{{ $pm->id }}">{{ $pm->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Estado de Facturación</label>
                                <select id="filter_rev_invoiced" class="form-select form-select-sm">
                                    <option value="all">Todas (Facturadas y Recibo Caja)</option>
                                    <option value="invoiced">Solo Facturadas Electrónicamente (B5/F5)</option>
                                    <option value="unbilled">Solo Recibo Simple de Caja</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Fecha Pago Desde</label>
                                <input type="date" id="filter_rev_date_from" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Fecha Pago Hasta</label>
                                <input type="date" id="filter_rev_date_to" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btn_filter_revenue" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-filter-3-line"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-report-revenue">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha Cobro</th>
                                    <th>Contrato</th>
                                    <th>Cliente</th>
                                    <th>Cuota</th>
                                    <th>Medio Pago</th>
                                    <th>Caja / Arqueo</th>
                                    <th>Comprobante</th>
                                    <th>Clave Conciliación</th>
                                    <th class="text-end">Monto Recaudado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 6: PRELIMINARES (CONVERTIDAS VS PERDIDAS) -->
                <div class="tab-pane fade" id="tab-preliminaries" role="tabpanel">
                    <div class="filter-panel">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Fecha Registro Desde</label>
                                <input type="date" id="filter_prelim_date_from" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Fecha Registro Hasta</label>
                                <input type="date" id="filter_prelim_date_to" class="form-control form-select-sm">
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="btn_filter_preliminaries" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-filter-3-line"></i> Filtrar Preliminares
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="table-report-preliminaries">
                            <thead class="table-light">
                                <tr>
                                    <th>Código Preliminar</th>
                                    <th>Contrato Oficial</th>
                                    <th>Cliente</th>
                                    <th>Fecha Evento</th>
                                    <th>Origen</th>
                                    <th class="text-end">Monto Estimado</th>
                                    <th class="text-center">Estado Conversión</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. Table Contratos por Estado
    let tableContracts = $('#table-report-contracts').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.contracts.status_data') }}",
            data: function(d) {
                d.status = $('#filter_contract_status').val();
                d.client_id = $('#filter_contract_client').val();
                d.date_from = $('#filter_contract_date_from').val();
                d.date_to = $('#filter_contract_date_to').val();
            }
        },
        columns: [
            { data: 'codigo_contrato', name: 'contract_number' },
            { data: 'cliente_info', name: 'client.nombres' },
            { data: 'fechas_info', name: 'fecha_evento' },
            { data: 'finanzas_info', name: 'total' },
            { data: 'estado_badge', name: 'estado', className: 'text-center' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[0, 'desc']]
    });

    $('#btn_filter_contracts').on('click', function() { tableContracts.ajax.reload(); });
    $('#btn_reset_contracts').on('click', function() {
        $('#filter_contract_status').val('');
        $('#filter_contract_client').val('');
        $('#filter_contract_date_from').val('');
        $('#filter_contract_date_to').val('');
        tableContracts.ajax.reload();
    });

    // 2. Table Cuotas Vencidas / Por Vencer
    let tableInstallments = $('#table-report-installments').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.contracts.installments_due_data') }}",
            data: function(d) {
                d.due_filter = $('#filter_installments_due').val();
                d.date_from = $('#filter_inst_date_from').val();
                d.date_to = $('#filter_inst_date_to').val();
            }
        },
        columns: [
            { data: 'contrato_info', name: 'contract.contract_number' },
            { data: 'cliente_info', name: 'contract.client.nombres' },
            { data: 'cuota_detalle', name: 'numero_cuota' },
            { data: 'fecha_vencimiento', name: 'fecha_vencimiento' },
            { data: 'dias_alerta', name: 'dias_alerta', className: 'text-center' },
            { data: 'monto', name: 'monto' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[3, 'asc']]
    });

    $('#btn_filter_installments').on('click', function() { tableInstallments.ajax.reload(); });
    $('#filter_installments_due').on('change', function() { tableInstallments.ajax.reload(); });

    // 3. Table Garantías Recibidas
    let tableGuarantees = $('#table-report-guarantees').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.contracts.guarantees_data') }}",
            data: function(d) {
                d.client_id = $('#filter_guarantee_client').val();
                d.date_from = $('#filter_guarantee_date_from').val();
                d.date_to = $('#filter_guarantee_date_to').val();
            }
        },
        columns: [
            { data: 'codigo_contrato', name: 'contract_number' },
            { data: 'cliente_info', name: 'client.nombres' },
            { data: 'fecha_evento_fmt', name: 'fecha_evento' },
            { data: 'total_contrato', name: 'total' },
            { data: 'garantia_20', name: 'garantia_20' },
            { data: 'ajustes_descontados', name: 'ajustes_descontados' },
            { data: 'garantia_neta_devolver', name: 'garantia_neta_devolver' },
            { data: 'estado_garantia', name: 'estado', className: 'text-center' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[2, 'desc']]
    });

    $('#btn_filter_guarantees').on('click', function() { tableGuarantees.ajax.reload(); });

    // 4. Table Ajustes por Roturas
    let tableAdjustments = $('#table-report-adjustments').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.contracts.adjustments_data') }}",
            data: function(d) {
                d.type = $('#filter_adj_type').val();
                d.date_from = $('#filter_adj_date_from').val();
                d.date_to = $('#filter_adj_date_to').val();
            }
        },
        columns: [
            { data: 'fecha_fmt', name: 'created_at' },
            { data: 'contrato_info', name: 'contract.contract_number' },
            { data: 'cliente_info', name: 'contract.client.nombres' },
            { data: 'tipo_badge', name: 'type' },
            { data: 'producto_descripcion', name: 'description' },
            { data: 'registrado_por', name: 'user.nombres' },
            { data: 'amount', name: 'amount' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[0, 'desc']]
    });

    $('#btn_filter_adjustments').on('click', function() { tableAdjustments.ajax.reload(); });
    $('#filter_adj_type').on('change', function() { tableAdjustments.ajax.reload(); });

    // 5. Table Recaudación por Cuota y Medio
    let tableRevenue = $('#table-report-revenue').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.contracts.revenue_payment_data') }}",
            data: function(d) {
                d.pay_mode_id = $('#filter_rev_pay_mode').val();
                d.invoiced_filter = $('#filter_rev_invoiced').val();
                d.date_from = $('#filter_rev_date_from').val();
                d.date_to = $('#filter_rev_date_to').val();
            }
        },
        columns: [
            { data: 'fecha_pago_fmt', name: 'paid_at' },
            { data: 'contrato_info', name: 'contract.contract_number' },
            { data: 'cliente_info', name: 'contract.client.nombres' },
            { data: 'cuota_info', name: 'numero_cuota' },
            { data: 'medio_pago_badge', name: 'payMode.descripcion' },
            { data: 'caja_arqueo_info', name: 'idarqueocaja' },
            { data: 'facturacion_info', name: 'billing_id' },
            { data: 'reconciliation_badge', name: 'reconciliation_key' },
            { data: 'amount', name: 'amount' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[0, 'desc']]
    });

    $('#btn_filter_revenue').on('click', function() { tableRevenue.ajax.reload(); });
    $('#filter_rev_pay_mode, #filter_rev_invoiced').on('change', function() { tableRevenue.ajax.reload(); });

    // 6. Table Preliminares
    let tablePreliminaries = $('#table-report-preliminaries').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.contracts.preliminaries_data') }}",
            data: function(d) {
                d.date_from = $('#filter_prelim_date_from').val();
                d.date_to = $('#filter_prelim_date_to').val();
            }
        },
        columns: [
            { data: 'codigo_preliminar', name: 'preliminary_number' },
            { data: 'contrato_oficial', name: 'contract_number' },
            { data: 'cliente_info', name: 'client.nombres' },
            { data: 'fecha_evento_fmt', name: 'fecha_evento' },
            { data: 'origen_badge', name: 'source' },
            { data: 'monto_estimado', name: 'total' },
            { data: 'estado_conversion', name: 'status', className: 'text-center' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[0, 'desc']]
    });

    $('#btn_filter_preliminaries').on('click', function() { tablePreliminaries.ajax.reload(); });

    // Re-adjust columns when tabs switch
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});
</script>
@endsection
