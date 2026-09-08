@extends('admin.layout')

@section('styles')
    <style>
        .checklist-list-card,
        .checklist-list-card .card-body,
        .checklist-list-card .table-responsive {
            overflow: visible;
        }

        .checklist-list-card .dropdown,
        .checklist-list-card td,
        .checklist-list-card th {
            position: relative;
        }

        .checklist-list-card .dropdown-menu {
            z-index: 1055;
        }

        #table_checklists_wrapper .form-control,
        #table_checklists_wrapper .form-select,
        #table_checklists thead .form-control {
            border-radius: 999px !important;
            border-color: #cbd5e1;
            background: #fff;
            box-shadow: none;
        }

        #table_checklists_wrapper .form-control:focus,
        #table_checklists_wrapper .form-select:focus {
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

        .progress-slim {
            height: 6px;
            border-radius: 4px;
            background-color: #e2e8f0;
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
                        <div class="page-header-icon"><i class="ri-checkbox-multiple-line text-primary"></i></div>
                        Checklists de Eventos (Insumos, Cristalería y Equipamiento)
                    </h1>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('admin.contracts') }}" class="btn btn-outline-primary waves-effect">
                        <i class="ri-file-text-line me-1"></i> Ir a Contratos de Eventos
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl px-4 mt-2">
    <!-- KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">Total Checklists</div>
                            <div class="h3 mb-0">{{ $kpi_total ?? 0 }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(0,97,242,.10); color: var(--bs-primary);">
                            <i class="ri-list-check-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">En Tránsito / Despachados</div>
                            <div class="h3 mb-0 text-primary">{{ $kpi_dispatched ?? 0 }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(13,110,253,.10); color: #0d6efd;">
                            <i class="ri-truck-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">Retornados Conformes</div>
                            <div class="h3 mb-0 text-success">{{ $kpi_returned_ok ?? 0 }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(25,135,84,.10); color: #198754;">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card custom-card pro-card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted fw-bold">Con Incidencias / Daños</div>
                            <div class="h3 mb-0 text-danger">{{ $kpi_with_incidents ?? 0 }}</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(220,53,69,.10); color: #dc3545;">
                            <i class="ri-alert-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="card custom-card pro-card checklist-list-card shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="table_checklists" class="table table-hover table-sm w-100 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" width="12%" class="text-center">Código</th>
                                    <th scope="col" width="13%" class="text-center">N° Contrato</th>
                                    <th scope="col" width="12%" class="text-center">Fecha Evento</th>
                                    <th scope="col" class="text-center">Cliente Contratante</th>
                                    <th scope="col" width="14%" class="text-center">Control Salida</th>
                                    <th scope="col" width="14%" class="text-center">Control Retorno</th>
                                    <th scope="col" width="12%" class="text-center">Estado</th>
                                    <th scope="col" width="10%" class="text-center">Acciones</th>
                                </tr>
                                <tr>
                                    <th>
                                        <input type="text" id="filter-code" class="form-control form-control-sm" placeholder="Buscar código" />
                                    </th>
                                    <th>
                                        <input type="text" id="filter-contract" class="form-control form-control-sm" placeholder="Buscar contrato" />
                                    </th>
                                    <th>
                                        <input type="date" id="filter-event-date" class="form-control form-control-sm text-center" />
                                    </th>
                                    <th>
                                        <input type="text" id="filter-client" class="form-control form-control-sm" placeholder="Buscar cliente" />
                                    </th>
                                    <th></th>
                                    <th></th>
                                    <th>
                                        <select id="filter-status" class="form-select form-select-sm">
                                            <option value="">Todos</option>
                                            <option value="0">Planificado</option>
                                            <option value="1">Despachado</option>
                                            <option value="2">Retornado OK</option>
                                            <option value="3">Con Incidencias</option>
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
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let table = $('#table_checklists').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('event_checklists.get') }}",
            data: function(d) {
                // Keep default datatable params
            }
        },
        columns: [
            { data: 'code', name: 'event_checklists.code', className: 'text-center fw-bold text-primary' },
            { data: 'contract_number', name: 'contracts.contract_number', className: 'text-center' },
            { data: 'fecha_evento', name: 'contracts.fecha_evento', className: 'text-center' },
            { data: 'cliente', name: 'clients.nombres' },
            { data: 'progreso_llevado', name: 'progreso_llevado', orderable: false, searchable: false },
            { data: 'progreso_devuelto', name: 'progreso_devuelto', orderable: false, searchable: false },
            { data: 'estado_badge', name: 'estado_badge', className: 'text-center', orderable: false, searchable: false },
            { data: 'acciones', name: 'acciones', className: 'text-center', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        }
    });

    // Column Filters
    $('#filter-code').on('keyup change', function() {
        table.column(0).search(this.value).draw();
    });
    $('#filter-contract').on('keyup change', function() {
        table.column(1).search(this.value).draw();
    });
    $('#filter-event-date').on('change', function() {
        table.column(2).search(this.value).draw();
    });
    $('#filter-client').on('keyup change', function() {
        table.column(3).search(this.value).draw();
    });
    $('#filter-status').on('change', function() {
        table.column(6).search(this.value).draw();
    });

    // Delete Checklist Handler
    $(document).on('click', '.btn-delete-checklist', function(e) {
        e.preventDefault();
        let id = $(this).data('id');

        Swal.fire({
            title: '¿Está seguro de eliminar este checklist?',
            text: 'Se eliminarán los registros de verificación de salida y retorno de este evento.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.event_checklists.delete') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id
                    },
                    success: function(res) {
                        if (res.status) {
                            toastr.success(res.message);
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error(res.message);
                        }
                    },
                    error: function() {
                        toastr.error('Error al intentar eliminar el checklist.');
                    }
                });
            }
        });
    });
});
</script>
@endsection
