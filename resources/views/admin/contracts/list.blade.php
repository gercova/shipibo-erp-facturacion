@extends('admin.layout')

@section('styles')
    <style>
        .contracts-list-card,
        .contracts-list-card .card-body,
        .contracts-list-card .table-responsive {
            overflow: visible;
        }

        .contracts-list-card .dropdown,
        .contracts-list-card td,
        .contracts-list-card th {
            position: relative;
        }

        .contracts-list-card .dropdown-menu {
            z-index: 1055;
        }

        #table_wrapper .form-control,
        #table_wrapper .form-select,
        #table thead .form-control {
            border-radius: 999px !important;
            border-color: #cbd5e1;
            background: #fff;
            box-shadow: none;
        }

        #table_wrapper .form-control:focus,
        #table_wrapper .form-select:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 .12rem rgba(59, 130, 246, .12);
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
    </style>
@endsection

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="file-text"></i></div>
                        Gestión de Contratos de Servicios
                    </h1>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.create_contract') }}"
                        class="dt-button create-new btn btn-primary waves-effect waves-light mb-2 btn-create" tabindex="0">
                        <span>
                            <i class="ri-add-circle-line align-middle"></i>
                            <span class="d-none d-sm-inline-block"> Nuevo Contrato</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl px-4 mt-4">
    <!-- KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">Total Contratos</div>
                            <div class="h3 mb-0">{{ $kpi_total_count ?? 0 }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(0,97,242,.10); color: var(--bs-primary);">
                            <i class="fas fa-file-contract"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">Firmados / Activos</div>
                            <div class="h3 mb-0 text-success">{{ $kpi_signed_count ?? 0 }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(0,172,105,.10); color: rgba(0,172,105,1);">
                            <i class="fas fa-signature"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">Eventos de este Mes</div>
                            <div class="h3 mb-0 text-info">{{ $kpi_events_month ?? 0 }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(14,165,233,.12); color: #0284c7;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">Total Contratado</div>
                            <div class="h3 mb-0">{{ $signo ?? 'S/' }} {{ number_format(($kpi_total_amount ?? 0), 2) }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(232,136,0,.12); color: rgba(232,136,0,1);">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="card custom-card pro-card contracts-list-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table" class="table table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th scope="col" width="13%" class="text-center">N° Contrato</th>
                                    <th scope="col" width="12%" class="text-center">Fecha Evento</th>
                                    <th scope="col" width="12%" class="text-center">Doc. Cliente</th>
                                    <th scope="col" class="text-center">Cliente Contratante</th>
                                    <th scope="col" width="11%" class="text-center">Total</th>
                                    <th scope="col" width="10%" class="text-center">Estado</th>
                                    <th scope="col" width="10%" class="text-center">Acciones</th>
                                </tr>
                                <tr>
                                    <th>
                                        <input type="text" id="filter-contract" class="form-control form-control-sm" placeholder="Buscar contrato" />
                                    </th>
                                    <th>
                                        <input type="date" id="filter-event-date" class="form-control form-control-sm text-center" />
                                    </th>
                                    <th>
                                        <input type="text" id="filter-document" class="form-control form-control-sm" placeholder="Buscar documento" />
                                    </th>
                                    <th>
                                        <input type="text" id="filter-client" class="form-control form-control-sm" placeholder="Buscar cliente" />
                                    </th>
                                    <th>
                                        <input type="text" id="filter-total" class="form-control form-control-sm" placeholder="Buscar total" />
                                    </th>
                                    <th>
                                        <select id="filter-status" class="form-select form-select-sm">
                                            <option value="">Todos</option>
                                            <option value="1">Firmado</option>
                                            <option value="0">Borrador</option>
                                            <option value="2">Completado</option>
                                            <option value="3">Anulado</option>
                                        </select>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.contracts.modals')
</div>
@endsection

@section('scripts')
    @include('admin.contracts.js-datatable')
@endsection
