@extends('admin.layout')

@section('content')
<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fs-12 px-2 py-1 rounded-pill">
                    <i class="ri-gift-line me-1"></i> Catálogo Dinámico &bull; Eventos & Catering
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0 mt-1">Paquetes y Eventos</h3>
            <p class="text-muted small mb-0">Gestión de paquetes de barra móvil, cristalería, cócteles y tramos de precios dinámicos por cantidad de invitados.</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-info fw-semibold px-3 py-2 shadow-sm" id="btnOpenGlobalCalcModal">
                <i class="ri-calculator-line me-1"></i> Simulador de Precios
            </button>

            <button type="button" class="btn btn-primary fw-bold px-3 py-2 shadow-sm" id="btnOpenNewPackageModal">
                <i class="ri-add-line me-1"></i> Nuevo Paquete
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-gift-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Paquetes</span>
                            <h4 class="fw-bold mb-0 text-dark" id="kpi_total">{{ $kpis['total'] }}</h4>
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
                            <span class="text-muted small text-uppercase fw-semibold">Activos</span>
                            <h4 class="fw-bold mb-0 text-success" id="kpi_activos">{{ $kpis['activos'] }}</h4>
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
                            <i class="ri-stack-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Escalonados (Tiers)</span>
                            <h4 class="fw-bold mb-0 text-info" id="kpi_escalonados">{{ $kpis['escalonados'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-user-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Por Persona / Fijo</span>
                            <h4 class="fw-bold mb-0 text-warning" id="kpi_por_persona">{{ $kpis['por_persona'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main DataTable Card --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="ri-list-check me-2 text-primary"></i> Catálogo de Paquetes Configurados
            </h5>
            <div class="small text-muted">
                Precios calculados 100% en el servidor garantizando consistencia tributaria e inventario.
            </div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="tablePackages" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">Foto</th>
                            <th style="min-width: 220px;">Nombre & Modalidad</th>
                            <th style="width: 170px;">Capacidad Invitados</th>
                            <th style="width: 190px;">Esquema de Precios</th>
                            <th style="width: 110px;" class="text-center">Ítems</th>
                            <th style="width: 80px;" class="text-center">Activo</th>
                            <th style="width: 110px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('admin.packages.modals')

@endsection

@section('scripts')
<script>
    const CSRF_TOKEN = "{{ csrf_token() }}";
    const CURRENCY_SIGN = "{{ $signo }}";

    let tablePackages = null;
    let tiersList = [];
    let itemsList = [];

    $(document).ready(function() {
        // Init DataTable
        tablePackages = $('#tablePackages').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.packages.get') }}",
            columns: [
                { data: 'imagen_thumb', name: 'imagen_thumb', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'guest_range', name: 'guest_range', orderable: false },
                { data: 'pricing_summary', name: 'pricing_summary', orderable: false },
                { data: 'items_count', name: 'items_count', orderable: false, className: 'text-center' },
                { data: 'active', name: 'active', orderable: false, className: 'text-center' },
                { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                url: "{{ asset('assets/libs/datatables/spanish.json') }}",
                emptyTable: "No se encontraron paquetes registrados en el sistema."
            },
            order: [[1, 'asc']]
        });

        // Price mode change toggle
        $('#price_mode').on('change', function() {
            const mode = $(this).val();
            if (mode === 'tiered') {
                $('#box_base_price').hide();
                $('#box_tiers_section').show();
            } else {
                $('#box_base_price').show();
                $('#box_tiers_section').hide();
                if (mode === 'per_guest') {
                    $('#label_base_price').html('Precio por Invitado (' + CURRENCY_SIGN + ') <span class="text-danger">*</span>');
                } else {
                    $('#label_base_price').html('Precio Fijo Total (' + CURRENCY_SIGN + ') <span class="text-danger">*</span>');
                }
            }
        });

        // Open New Package Modal
        $('#btnOpenNewPackageModal').on('click', function() {
            resetPackageForm();
            $('#modalPackageTitle').text('Nuevo Paquete de Evento');
            $('#modalPackage').modal('show');
        });

        // Add Tier Row
        $('#btnAddTierRow').on('click', function() {
            const minGuests = parseInt($('#min_guests').val()) || 1;
            const lastTier = tiersList[tiersList.length - 1];
            const nextFrom = lastTier ? (parseInt(lastTier.to_guests) + 1) : minGuests;
            const nextTo = nextFrom + 50;

            tiersList.push({
                from_guests: nextFrom,
                to_guests: nextTo,
                price: 0.00,
                price_type: 'total'
            });
            renderTiersTable();
        });

        // Add Item Row
        $('#btnAddItemRow').on('click', function() {
            const type = $('#item_picker_type').val();
            let productId = null;
            let cocktailId = null;
            let name = '';
            let unitPrice = 0.00;

            if (type === 'cocktail') {
                const opt = $('#picker_cocktail_id option:selected');
                cocktailId = $('#picker_cocktail_id').val();
                if (!cocktailId) {
                    Swal.fire('Atención', 'Seleccione un cóctel de la carta.', 'warning');
                    return;
                }
                name = opt.text();
                unitPrice = parseFloat(opt.data('precio')) || 0.00;
            } else {
                const opt = $('#picker_product_id option:selected');
                productId = $('#picker_product_id').val();
                if (!productId) {
                    Swal.fire('Atención', 'Seleccione un servicio o equipo del catálogo.', 'warning');
                    return;
                }
                name = opt.text();
                unitPrice = parseFloat(opt.data('precio')) || 0.00;
            }

            itemsList.push({
                product_id: productId,
                cocktail_id: cocktailId,
                name: name,
                quantity: 1,
                is_per_guest: false,
                is_mandatory: true,
                unit_price: unitPrice > 0 ? unitPrice : null,
                notes: ''
            });

            renderItemsTable();
        });

        // Save Package Form
        $('#btnSavePackage').on('click', function() {
            const form = document.getElementById('formPackage');
            const formData = new FormData(form);

            // Add serialized JSON for tiers and items
            formData.append('tiers', JSON.stringify(tiersList));
            formData.append('items', JSON.stringify(itemsList));

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> Guardando...');

            $.ajax({
                url: "{{ route('admin.packages.save') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Guardar Paquete');
                    if (res.status) {
                        $('#modalPackage').modal('hide');
                        tablePackages.ajax.reload(null, false);
                        Swal.fire('¡Éxito!', res.msg, 'success');
                    } else {
                        Swal.fire('Error', res.msg || 'No se pudo guardar el paquete.', 'error');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Guardar Paquete');
                    const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error interno al procesar.';
                    Swal.fire('Error de Validación', msg, 'error');
                }
            });
        });

        // Edit Package
        $(document).on('click', '.btn-edit-package', function() {
            const id = $(this).data('id');
            loadPackageDetails(id, function(pkg) {
                $('#package_id').val(pkg.id);
                $('#name').val(pkg.name);
                $('#price_mode').val(pkg.price_mode).trigger('change');
                $('#min_guests').val(pkg.min_guests);
                $('#max_guests').val(pkg.max_guests || '');
                $('#base_price').val(pkg.base_price || 0.00);
                $('#description').val(pkg.description || '');
                $('#active').prop('checked', pkg.active);

                tiersList = (pkg.price_tiers || []).map(t => ({
                    from_guests: t.from_guests,
                    to_guests: t.to_guests,
                    price: t.price,
                    price_type: t.price_type || 'total'
                }));
                renderTiersTable();

                itemsList = (pkg.items || []).map(i => ({
                    product_id: i.product_id,
                    cocktail_id: i.cocktail_id,
                    name: i.cocktail ? ('[Cóctel] ' + i.cocktail.nombre) : (i.product ? ('[Servicio] ' + i.product.descripcion) : (i.notes || 'Ítem')),
                    quantity: i.quantity,
                    is_per_guest: Boolean(i.is_per_guest),
                    is_mandatory: Boolean(i.is_mandatory),
                    unit_price: i.unit_price,
                    notes: i.notes || ''
                }));
                renderItemsTable();

                $('#modalPackageTitle').text('Editar Paquete #' + pkg.id + ': ' + pkg.name);
                $('#modalPackage').modal('show');
            });
        });

        // Toggle Status Switch
        $(document).on('change', '.btn-toggle-status', function() {
            const id = $(this).data('id');
            const checkbox = $(this);

            $.post("{{ route('admin.packages.toggle_status') }}", { _token: CSRF_TOKEN, id: id }, function(res) {
                if (res.status) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: res.msg,
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    Swal.fire('Error', res.msg, 'error');
                }
            }).fail(function() {
                checkbox.prop('checked', !checkbox.prop('checked'));
                Swal.fire('Error', 'No se pudo actualizar el estado.', 'error');
            });
        });

        // Delete Package
        $(document).on('click', '.btn-delete-package', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: '¿Eliminar paquete?',
                text: 'Esta acción eliminará permanentemente "' + name + '" y sus tramos de precio asociados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('admin.packages.delete') }}", { _token: CSRF_TOKEN, id: id }, function(res) {
                        if (res.status) {
                            tablePackages.ajax.reload(null, false);
                            Swal.fire('Eliminado', res.msg, 'success');
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'No se pudo eliminar el paquete.', 'error');
                    });
                }
            });
        });

        // Test Simulator from row
        $(document).on('click', '.btn-test-calc', function() {
            const id = $(this).data('id');
            $('#sim_package_id').val(id);
            $('#modalCalculatorSimulator').modal('show');
            runSimulation();
        });

        // Global Simulator button
        $('#btnOpenGlobalCalcModal').on('click', function() {
            $('#modalCalculatorSimulator').modal('show');
            runSimulation();
        });

        // Simulator run button
        $('#btnRunSimulation').on('click', function() {
            runSimulation();
        });
    });

    function resetPackageForm() {
        document.getElementById('formPackage').reset();
        $('#package_id').val('');
        $('#price_mode').val('tiered').trigger('change');
        $('#min_guests').val(100);
        $('#max_guests').val(180);
        $('#base_price').val(0.00);
        $('#active').prop('checked', true);
        tiersList = [];
        itemsList = [];
        renderTiersTable();
        renderItemsTable();
    }

    function renderTiersTable() {
        const tbody = $('#tableTiersBody');
        tbody.empty();

        if (tiersList.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">No hay tramos configurados. Haga clic en "+ Agregar Tramo".</td></tr>');
            return;
        }

        tiersList.forEach((tier, idx) => {
            const row = `
                <tr>
                    <td>
                        <input type="number" class="form-control form-control-sm tier-from" data-idx="${idx}" value="${tier.from_guests}" min="1">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm tier-to" data-idx="${idx}" value="${tier.to_guests}" min="1">
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">${CURRENCY_SIGN}</span>
                            <input type="number" step="0.50" class="form-control form-control-sm tier-price" data-idx="${idx}" value="${parseFloat(tier.price).toFixed(2)}" min="0">
                        </div>
                    </td>
                    <td>
                        <select class="form-select form-select-sm tier-type" data-idx="${idx}">
                            <option value="total" ${tier.price_type === 'total' ? 'selected' : ''}>Precio Total del Tramo</option>
                            <option value="per_guest" ${tier.price_type === 'per_guest' ? 'selected' : ''}>Precio Por Invitado</option>
                        </select>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-tier" data-idx="${idx}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Bind input events
        $('.tier-from').on('change', function() {
            const idx = $(this).data('idx');
            tiersList[idx].from_guests = parseInt($(this).val()) || 1;
        });
        $('.tier-to').on('change', function() {
            const idx = $(this).data('idx');
            tiersList[idx].to_guests = parseInt($(this).val()) || 1;
        });
        $('.tier-price').on('change', function() {
            const idx = $(this).data('idx');
            tiersList[idx].price = parseFloat($(this).val()) || 0.00;
        });
        $('.tier-type').on('change', function() {
            const idx = $(this).data('idx');
            tiersList[idx].price_type = $(this).val();
        });
        $('.btn-remove-tier').on('click', function() {
            const idx = $(this).data('idx');
            tiersList.splice(idx, 1);
            renderTiersTable();
        });
    }

    function renderItemsTable() {
        const tbody = $('#tableItemsBody');
        tbody.empty();

        if (itemsList.length === 0) {
            tbody.append('<tr><td colspan="7" class="text-center text-muted py-3">No hay ítems agregados al paquete.</td></tr>');
            return;
        }

        itemsList.forEach((item, idx) => {
            const row = `
                <tr>
                    <td class="fw-semibold text-dark">${item.name}</td>
                    <td style="width: 100px;">
                        <input type="number" step="0.5" class="form-control form-control-sm item-qty" data-idx="${idx}" value="${item.quantity}" min="0.1">
                    </td>
                    <td class="text-center" style="width: 90px;">
                        <input type="checkbox" class="form-check-input item-per-guest" data-idx="${idx}" ${item.is_per_guest ? 'checked' : ''}>
                    </td>
                    <td class="text-center" style="width: 90px;">
                        <input type="checkbox" class="form-check-input item-mandatory" data-idx="${idx}" ${item.is_mandatory ? 'checked' : ''}>
                    </td>
                    <td style="width: 130px;">
                        <input type="number" step="0.5" class="form-control form-control-sm item-price" data-idx="${idx}" value="${item.unit_price !== null ? item.unit_price : ''}" placeholder="Opcional">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-notes" data-idx="${idx}" value="${item.notes || ''}" placeholder="Notas...">
                    </td>
                    <td class="text-center" style="width: 50px;">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-idx="${idx}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        $('.item-qty').on('change', function() {
            const idx = $(this).data('idx');
            itemsList[idx].quantity = parseFloat($(this).val()) || 1;
        });
        $('.item-per-guest').on('change', function() {
            const idx = $(this).data('idx');
            itemsList[idx].is_per_guest = $(this).is(':checked');
        });
        $('.item-mandatory').on('change', function() {
            const idx = $(this).data('idx');
            itemsList[idx].is_mandatory = $(this).is(':checked');
        });
        $('.item-price').on('change', function() {
            const idx = $(this).data('idx');
            const val = $(this).val();
            itemsList[idx].unit_price = val !== '' ? parseFloat(val) : null;
        });
        $('.item-notes').on('change', function() {
            const idx = $(this).data('idx');
            itemsList[idx].notes = $(this).val();
        });
        $('.btn-remove-item').on('click', function() {
            const idx = $(this).data('idx');
            itemsList.splice(idx, 1);
            renderItemsTable();
        });
    }

    function loadPackageDetails(id, callback) {
        $.post("{{ route('admin.packages.detail') }}", { _token: CSRF_TOKEN, id: id }, function(res) {
            if (res.status) {
                callback(res.package);
            } else {
                Swal.fire('Error', res.msg || 'No se pudo cargar el paquete.', 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Error al comunicarse con el servidor.', 'error');
        });
    }

    function runSimulation() {
        const packageId = $('#sim_package_id').val();
        const guests = parseInt($('#sim_guests').val()) || 100;

        if (!packageId) {
            return;
        }

        $('#sim_results_box').html('<div class="text-center py-4"><i class="ri-loader-4-line ri-spin fs-24 text-primary"></i><div class="mt-2 text-muted">Calculando montos e IGV en el servidor...</div></div>');

        $.post("{{ route('admin.packages.calculate') }}", {
            _token: CSRF_TOKEN,
            package_id: packageId,
            guests: guests,
            extras: []
        }, function(res) {
            if (res.status) {
                renderSimulationResults(res.data);
            } else {
                $('#sim_results_box').html(`
                    <div class="alert alert-danger d-flex align-items-center mb-0">
                        <i class="ri-error-warning-line fs-20 me-2"></i>
                        <div>${res.msg}</div>
                    </div>
                `);
            }
        }).fail(function(xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error al procesar la cotización en el servidor.';
            $('#sim_results_box').html(`
                <div class="alert alert-danger d-flex align-items-center mb-0">
                    <i class="ri-error-warning-line fs-20 me-2"></i>
                    <div>${msg}</div>
                </div>
            `);
        });
    }

    function renderSimulationResults(data) {
        let rowsHtml = '';
        (data.breakdown || []).forEach(item => {
            const taxBadge = item.igv_percent === 18
                ? '<span class="badge bg-primary-subtle text-primary border">Gravado 18%</span>'
                : '<span class="badge bg-success-subtle text-success border">Exonerado 0%</span>';

            const typeBadge = item.is_service
                ? '<span class="badge bg-light text-dark border">Servicio (opcion=2)</span>'
                : '<span class="badge bg-warning-subtle text-warning border">Bien Físico</span>';

            rowsHtml += `
                <tr>
                    <td>
                        <div class="fw-bold text-dark">${item.name}</div>
                        <div class="small text-muted">${item.notes || ''}</div>
                    </td>
                    <td>${typeBadge}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-center">${taxBadge}</td>
                    <td class="text-end">${CURRENCY_SIGN} ${parseFloat(item.subtotal).toFixed(2)}</td>
                    <td class="text-end">${CURRENCY_SIGN} ${parseFloat(item.igv).toFixed(2)}</td>
                    <td class="text-end fw-bold text-dark">${CURRENCY_SIGN} ${parseFloat(item.total).toFixed(2)}</td>
                </tr>
            `;
        });

        const html = `
            <div class="card border border-primary-subtle bg-light shadow-none mb-3">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span class="badge bg-primary mb-1">Paquete: ${data.package_name}</span>
                            <h5 class="fw-bold mb-0 text-dark">Simulación para ${data.guests} Invitados</h5>
                            <small class="text-muted">Modalidad: ${data.price_mode} &bull; Cálculo efectuado estrictamente en servidor</small>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <div class="d-inline-block text-start bg-white p-2 px-3 rounded shadow-sm border">
                                <div class="small text-muted">Subtotal (Base Imponible): <span class="fw-bold text-dark">${CURRENCY_SIGN} ${parseFloat(data.subtotal).toFixed(2)}</span></div>
                                <div class="small text-muted">I.G.V. (Afectación Individual): <span class="fw-bold text-primary">${CURRENCY_SIGN} ${parseFloat(data.igv).toFixed(2)}</span></div>
                                <div class="fs-16 fw-bold text-success border-top pt-1 mt-1">Total a Contratar: ${CURRENCY_SIGN} ${parseFloat(data.total).toFixed(2)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Concepto / Ítem</th>
                            <th>Tipo</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Tratamiento Tributario</th>
                            <th class="text-end">Base Imponible</th>
                            <th class="text-end">IGV</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">TOTALES CONSOLIDADOS:</td>
                            <td class="text-end">${CURRENCY_SIGN} ${parseFloat(data.subtotal).toFixed(2)}</td>
                            <td class="text-end">${CURRENCY_SIGN} ${parseFloat(data.igv).toFixed(2)}</td>
                            <td class="text-end text-success fs-15">${CURRENCY_SIGN} ${parseFloat(data.total).toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;

        $('#sim_results_box').html(html);
    }
</script>
@endsection
