@extends('admin.layout')

@section('content')
<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning-subtle text-warning fs-12 px-2 py-1 rounded-pill">
                    <i class="ri-inbox-line me-1"></i> Ventas &bull; Flujo Digital B2
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0 mt-1">Cola de Pedidos Web Preliminares</h3>
            <p class="text-muted small mb-0">Gestión de cotizaciones preliminares generadas por clientes desde el menú digital web (Ley N° 29733).</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('public.orders.index') }}" target="_blank" class="btn btn-outline-primary fw-semibold px-3 py-2 shadow-sm d-flex align-items-center">
                <i class="ri-external-link-line me-2"></i> Ver Menú Público
            </a>
            <button type="button" class="btn btn-secondary px-3 py-2" id="btnRefreshTable">
                <i class="ri-refresh-line me-1"></i> Actualizar
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-time-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Preliminares Pendientes</span>
                            <h4 class="fw-bold mb-0 text-dark">{{ $kpis['total_preliminares'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-calendar-todo-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Recibidos Hoy</span>
                            <h4 class="fw-bold mb-0 text-primary">{{ $kpis['hoy'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-checkbox-circle-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Formalizados (CON)</span>
                            <h4 class="fw-bold mb-0 text-success">{{ $kpis['convertidos'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-money-dollar-circle-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Cotizado</span>
                            <h4 class="fw-bold mb-0 text-dark">{{ $signo }} {{ number_format((float) $kpis['monto_total'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Estado del Pedido</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="preliminar" selected>Solo Preliminares Pendientes</option>
                        <option value="borrador">Borradores CON</option>
                        <option value="vigente">Vigentes / Firmados</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Fecha Evento Desde</label>
                    <input type="date" id="filter_fecha_inicio" class="form-control form-control-sm">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Fecha Evento Hasta</label>
                    <input type="date" id="filter_fecha_fin" class="form-control form-control-sm">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary w-100 fw-bold" id="btnApplyFilter">
                        <i class="ri-filter-3-line me-1"></i> Filtrar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-50" id="btnResetFilter">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Orders DataTable --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="ri-file-list-3-line me-2 text-warning"></i> Solicitudes Web Recibidas
            </h5>
            <div class="small text-muted">
                Secuencia preliminar independiente <code>PRE-YYYY-XXXX</code>. No consume numeración <code>CON</code>.
            </div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="tablePreliminaryOrders" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 150px;">N° Preliminar & Tracking</th>
                            <th style="min-width: 200px;">Contacto / Cliente</th>
                            <th style="width: 120px;">Fecha Evento</th>
                            <th style="width: 100px;">Invitados</th>
                            <th style="width: 120px;" class="text-end">Total</th>
                            <th style="width: 130px;" class="text-center">Estado</th>
                            <th style="width: 110px;" class="text-center">WhatsApp</th>
                            <th style="width: 180px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal View Detail --}}
<div class="modal fade" id="modalOrderDetail" tabindex="-1" aria-labelledby="modalOrderDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold" id="modalOrderDetailTitle">
                    <i class="ri-file-info-line me-2 text-warning"></i> Detalle de Cotización Preliminar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4" id="modalOrderDetailBody">
                <div class="text-center py-4 text-muted">Cargando información...</div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const CSRF_TOKEN = "{{ csrf_token() }}";
    const CURRENCY_SIGN = "{{ $signo }}";
    let tableOrders = null;

    $(document).ready(function() {
        tableOrders = $('#tablePreliminaryOrders').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.contracts.preliminary_orders.get') }}",
                data: function(d) {
                    d.status_filter = $('#filter_status').val();
                    d.fecha_inicio = $('#filter_fecha_inicio').val();
                    d.fecha_fin = $('#filter_fecha_fin').val();
                }
            },
            columns: [
                { data: 'codigo_preliminar', name: 'contract_number' },
                { data: 'cliente_info', name: 'clients.nombres' },
                { data: 'fecha_evento', name: 'fecha_evento' },
                { data: 'guest_count', name: 'guest_count', className: 'text-center' },
                { data: 'total', name: 'total' },
                { data: 'status_badge', name: 'status', className: 'text-center' },
                { data: 'whatsapp_action', name: 'whatsapp_action', orderable: false, searchable: false, className: 'text-center' },
                { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                url: "{{ asset('assets/libs/datatables/spanish.json') }}",
                emptyTable: "No se encontraron pedidos preliminares con los filtros indicados."
            },
            order: [[0, 'desc']]
        });

        $('#btnApplyFilter, #btnRefreshTable').on('click', function() {
            tableOrders.ajax.reload();
        });

        $('#btnResetFilter').on('click', function() {
            $('#filter_status').val('preliminar');
            $('#filter_fecha_inicio').val('');
            $('#filter_fecha_fin').val('');
            tableOrders.ajax.reload();
        });

        // View Order Details
        $(document).on('click', '.btn-view-detail', function() {
            const id = $(this).data('id');
            $('#modalOrderDetailBody').html('<div class="text-center py-4"><i class="ri-loader-4-line ri-spin fs-24 text-primary"></i></div>');
            $('#modalOrderDetail').modal('show');

            $.post("{{ route('admin.contracts.preliminary_orders.detail') }}", {
                _token: CSRF_TOKEN,
                id: id
            }, function(res) {
                if (res.status) {
                    renderOrderDetailModal(res.contract);
                } else {
                    $('#modalOrderDetailBody').html('<div class="alert alert-danger">' + res.msg + '</div>');
                }
            }).fail(function() {
                $('#modalOrderDetailBody').html('<div class="alert alert-danger">Error al cargar datos del pedido.</div>');
            });
        });

        // Convert to Draft CON
        $(document).on('click', '.btn-convert-draft', function() {
            const id = $(this).data('id');
            const code = $(this).data('code');

            Swal.fire({
                title: '¿Convertir a Borrador de Contrato?',
                text: 'Esta acción asignará un número oficial CON-' + new Date().getFullYear() + '-XXXX al pedido ' + code + ' para formalizarlo en el módulo de contratos.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, formalizar como Borrador',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('admin.contracts.preliminary_orders.convert_draft') }}", {
                        _token: CSRF_TOKEN,
                        id: id
                    }, function(res) {
                        if (res.status) {
                            tableOrders.ajax.reload(null, false);
                            Swal.fire('¡Convertido!', res.msg, 'success');
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'No se pudo convertir el pedido.', 'error');
                    });
                }
            });
        });
    });

    function renderOrderDetailModal(c) {
        let itemsHtml = '';
        (c.items || []).forEach(item => {
            itemsHtml += `
                <tr>
                    <td class="fw-semibold text-dark">${item.descripcion}</td>
                    <td class="text-center">${item.cantidad}</td>
                    <td class="text-end">${CURRENCY_SIGN} ${parseFloat(item.precio_unitario).toFixed(2)}</td>
                    <td class="text-end fw-bold">${CURRENCY_SIGN} ${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
            `;
        });

        const html = `
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Cliente Web</span>
                        <h6 class="fw-bold mb-1 text-dark">${c.client ? c.client.nombres : 'Cliente Web'}</h6>
                        <div class="small text-muted"><i class="ri-phone-line me-1"></i> Teléfono: ${c.client ? c.client.telefono : '-'}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Datos de la Solicitud</span>
                        <div class="fw-bold text-dark mb-1">N°: ${c.contract_number} | Tracking: ${c.tracking_code || '-'}</div>
                        <div class="small text-muted"><i class="ri-calendar-line me-1"></i> Fecha Evento: ${c.fecha_evento || '-'} &bull; Invitados: ${c.guest_count || '-'}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Concepto / Ítem</th>
                            <th class="text-center" style="width: 80px;">Cant.</th>
                            <th class="text-end" style="width: 120px;">P. Unit</th>
                            <th class="text-end" style="width: 120px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">TOTAL CONSOLIDADO (Inc. IGV):</td>
                            <td class="text-end text-success fs-15">${CURRENCY_SIGN} ${parseFloat(c.total).toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="small text-muted">
                <i class="ri-information-line me-1"></i> Observaciones: ${c.observaciones || 'Sin observaciones adicionales.'}
            </div>
        `;

        $('#modalOrderDetailBody').html(html);
    }
</script>
@endsection
