@extends('admin.layout')

@section('content')
<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fs-12 px-2 py-1 rounded-pill">
                    <i class="ri-global-line me-1"></i> Inventario &bull; Catálogo Web (/pedido)
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0 mt-1">Catálogo Web (/pedido)</h3>
            <p class="text-muted small mb-0">Gestión de paquetes de barra móvil, cristalería, especificaciones, capacidad y colores para la experiencia interactiva del cliente.</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('public.orders.index') }}" target="_blank" class="btn btn-outline-success fw-semibold px-3 py-2 shadow-sm" title="Abrir la vista pública de pedidos">
                <i class="ri-external-link-line me-1"></i> Ver Catálogo Público (/pedido)
            </a>

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
                            <span class="text-muted small text-uppercase fw-semibold">Activos en Web</span>
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
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="ri-list-check me-2 text-primary"></i> Ítems del Catálogo Web (/pedido)
            </h5>
            <div class="small text-muted">
                Los cambios se reflejan dinámicamente en la vista de pedidos de los clientes.
            </div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="tablePackages" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;" class="text-center"># Web</th>
                            <th style="width: 60px;" class="text-center">Foto</th>
                            <th style="min-width: 200px;">Nombre & Modalidad</th>
                            <th style="width: 170px;">Capacidad (Personas)</th>
                            <th style="width: 140px;">Colores Flyer</th>
                            <th style="width: 170px;">Esquema Precios</th>
                            <th style="min-width: 200px;">Especificaciones</th>
                            <th style="width: 70px;" class="text-center">Activo</th>
                            <th style="width: 140px;" class="text-center">Acciones</th>
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
    let featuresList = [];

    $(document).ready(function() {
        // Init DataTable
        tablePackages = $('#tablePackages').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.packages.get') }}",
            columns: [
                { data: 'order_num', name: 'order_num', className: 'text-center' },
                { data: 'imagen_thumb', name: 'imagen_thumb', orderable: false, searchable: false, className: 'text-center' },
                { data: 'name', name: 'name' },
                { data: 'capacity', name: 'capacity', orderable: false },
                { data: 'colors', name: 'colors', orderable: false },
                { data: 'pricing_summary', name: 'pricing_summary', orderable: false },
                { data: 'specifications', name: 'specifications', orderable: false },
                { data: 'active', name: 'active', orderable: false, className: 'text-center' },
                { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: "Procesando paquetes...",
                search: "Buscar en catálogo:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ paquetes",
                infoEmpty: "Mostrando 0 a 0 de 0 paquetes",
                infoFiltered: "(filtrado de _MAX_ paquetes)",
                zeroRecords: "No se encontraron paquetes registrados.",
                emptyTable: "No se encontraron paquetes registrados en el catálogo web.",
                paginate: {
                    first: "Primero",
                    previous: "Anterior",
                    next: "Siguiente",
                    last: "Último"
                }
            },
            order: [[0, 'asc']]
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

        // Color Picker sync
        $('#color_picker').on('input change', function() {
            $('#color').val($(this).val());
            updateLivePreview();
        });
        $('#color').on('input change', function() {
            $('#color_picker').val($(this).val());
            updateLivePreview();
        });

        $('#light_bg_picker').on('input change', function() {
            $('#light_bg').val($(this).val());
            updateLivePreview();
        });
        $('#light_bg').on('input change', function() {
            $('#light_bg_picker').val($(this).val());
            updateLivePreview();
        });

        // Palette Preset Buttons click
        $('.palette-preset-btn').on('click', function() {
            const color = $(this).data('color');
            const bg = $(this).data('bg');
            $('#color').val(color);
            $('#color_picker').val(color);
            $('#light_bg').val(bg);
            $('#light_bg_picker').val(bg);
            updateLivePreview();
        });

        // Cutout preset selection
        $(document).on('click', '.preset-cutout-item', function() {
            $('.preset-cutout-item').removeClass('border-primary bg-primary-subtle');
            $(this).addClass('border-primary bg-primary-subtle');
            const presetPath = $(this).data('preset');
            $('#image_preset').val(presetPath);
            $('#image_current_preview').attr('src', "{{ asset('') }}" + presetPath);
        });

        // Custom image upload preview
        $('#image').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    $('#image_current_preview').attr('src', evt.target.result);
                    $('.preset-cutout-item').removeClass('border-primary bg-primary-subtle');
                    $('#image_preset').val('');
                };
                reader.readAsDataURL(file);
            }
        });

        // Live preview inputs binding
        $('#name, #order_num, #badge_glasses, #tagline, #ideal_capacity').on('input keyup change', function() {
            updateLivePreview();
        });

        // Features Tag Management
        $('#btnAddFeature').on('click', function() {
            addFeatureFromInput();
        });
        $('#new_feature_input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addFeatureFromInput();
            }
        });
        $('.quick-feature-preset').on('click', function() {
            const text = $(this).data('text');
            if (text && !featuresList.includes(text)) {
                featuresList.push(text);
                renderFeaturesBadges();
            }
        });
        $(document).on('click', '.btn-remove-feature', function() {
            const idx = $(this).data('idx');
            featuresList.splice(idx, 1);
            renderFeaturesBadges();
        });

        // Open New Package Modal
        $('#btnOpenNewPackageModal').on('click', function() {
            resetPackageForm();
            $('#modalPackageTitle').text('Nuevo Paquete en Catálogo Web');
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

        // Add Item Row (Services/Cocktails)
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
                name = '[Cóctel] ' + opt.text().trim();
                unitPrice = parseFloat(opt.data('precio')) || 0.00;
            } else {
                const opt = $('#picker_product_id option:selected');
                productId = $('#picker_product_id').val();
                if (!productId) {
                    Swal.fire('Atención', 'Seleccione un servicio o equipo del inventario.', 'warning');
                    return;
                }
                name = '[Equipo] ' + opt.text().trim();
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

            // Add serialized JSON for tiers, items and features
            formData.append('tiers', JSON.stringify(tiersList));
            formData.append('items', JSON.stringify(itemsList));
            formData.append('features', JSON.stringify(featuresList));

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
                $('#order_num').val(pkg.order_num || '');
                $('#badge_glasses').val(pkg.badge_glasses || '');
                $('#tagline').val(pkg.tagline || '');
                $('#color').val(pkg.color || '#275c34');
                $('#color_picker').val(pkg.color || '#275c34');
                $('#light_bg').val(pkg.light_bg || '#e9edea');
                $('#light_bg_picker').val(pkg.light_bg || '#e9edea');
                $('#ideal_capacity').val(pkg.ideal_capacity || '');
                $('#default_guests').val(pkg.default_guests || '');
                $('#price_mode').val(pkg.price_mode).trigger('change');
                $('#min_guests').val(pkg.min_guests);
                $('#max_guests').val(pkg.max_guests || '');
                $('#base_price').val(pkg.base_price || 0.00);
                $('#description').val(pkg.description || '');
                $('#active').prop('checked', Boolean(pkg.active));
                $('#is_barman').prop('checked', Boolean(pkg.is_barman));
                $('#image_preset').val('');
                $('#image').val('');

                // Image preview
                if (pkg.image_url) {
                    $('#image_current_preview').attr('src', pkg.image_url);
                }

                // Features
                featuresList = Array.isArray(pkg.features) ? pkg.features : [];
                renderFeaturesBadges();

                // Tiers
                tiersList = (pkg.price_tiers || []).map(t => ({
                    from_guests: t.from_guests,
                    to_guests: t.to_guests,
                    price: t.price,
                    price_type: t.price_type || 'total'
                }));
                renderTiersTable();

                // Items
                itemsList = (pkg.items || []).map(i => ({
                    product_id: i.product_id,
                    cocktail_id: i.cocktail_id,
                    name: i.cocktail ? ('[Cóctel] ' + i.cocktail.nombre) : (i.product ? ('[Equipo] ' + i.product.descripcion) : (i.notes || 'Ítem')),
                    quantity: i.quantity,
                    is_per_guest: Boolean(i.is_per_guest),
                    is_mandatory: Boolean(i.is_mandatory),
                    unit_price: i.unit_price,
                    notes: i.notes || ''
                }));
                renderItemsTable();

                updateLivePreview();

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
                title: '¿Eliminar paquete del catálogo?',
                text: 'Esta acción eliminará permanentemente "' + name + '" y sus tramos de precios.',
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

    function addFeatureFromInput() {
        const val = $('#new_feature_input').val().trim();
        if (val) {
            if (!featuresList.includes(val)) {
                featuresList.push(val);
                renderFeaturesBadges();
            }
            $('#new_feature_input').val('').focus();
        }
    }

    function renderFeaturesBadges() {
        const container = $('#featuresBadgesList');
        container.empty();

        if (featuresList.length === 0) {
            $('#featuresEmptyMessage').show();
            $('#features_json').val('[]');
            return;
        }

        $('#featuresEmptyMessage').hide();
        $('#features_json').val(JSON.stringify(featuresList));

        featuresList.forEach((feat, idx) => {
            const badge = `
                <div class="d-flex align-items-center justify-content-between bg-white border p-2 rounded-2 shadow-sm">
                    <span class="small fw-semibold text-dark text-truncate me-2" title="${feat}">
                        <i class="ri-check-line text-success me-1"></i> ${feat}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-feature" data-idx="${idx}" title="Quitar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            `;
            container.append(badge);
        });
    }

    function updateLivePreview() {
        const orderNum = $('#order_num').val() || '01';
        const title = $('#name').val() || 'NOMBRE PAQUETE';
        const badgeGlasses = $('#badge_glasses').val() || '120 Vasos';
        const tagline = $('#tagline').val() || 'Vasos en acrílico, cristalería, barra móvil...';
        const ideal = $('#ideal_capacity').val() || '100 a 180 personas';
        const color = $('#color').val() || '#275c34';
        const lightBg = $('#light_bg').val() || '#e9edea';

        $('#previewOrderNum').text(orderNum).css('background-color', color);
        $('#previewTitle').text(title).css('color', color);
        $('#previewBadgeGlasses').text(badgeGlasses).css('background-color', color);
        $('#previewTagline').text(tagline);
        $('#previewIdeal').text(ideal);
        $('#previewIconGroup').css('color', color);
        $('#previewCostBadge').css('background-color', color);
        $('#previewFooterBox').css('background-color', lightBg);
        $('#livePreviewCard').css('border-top', '5px solid ' + color);
    }

    function resetPackageForm() {
        document.getElementById('formPackage').reset();
        $('#package_id').val('');
        $('#image_preset').val('');
        $('#order_num').val('01');
        $('#badge_glasses').val('120 Vasos');
        $('#color').val('#275c34');
        $('#color_picker').val('#275c34');
        $('#light_bg').val('#e9edea');
        $('#light_bg_picker').val('#e9edea');
        $('#price_mode').val('tiered').trigger('change');
        $('#min_guests').val(100);
        $('#max_guests').val(180);
        $('#default_guests').val(100);
        $('#ideal_capacity').val('100 a 180 personas');
        $('#base_price').val(0.00);
        $('#active').prop('checked', true);
        $('#is_barman').prop('checked', false);
        $('#image_current_preview').attr('src', "{{ asset('img/packages/pkg-01.png') }}");
        $('.preset-cutout-item').removeClass('border-primary bg-primary-subtle');

        featuresList = [
            '1 Bartender + 1 asistente',
            'Barra móvil',
            'Cristalería',
            'Hielo Gourmet',
            'Insumos',
            'Utensilios de bar'
        ];
        renderFeaturesBadges();

        tiersList = [];
        itemsList = [];
        renderTiersTable();
        renderItemsTable();
        updateLivePreview();
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
            tbody.append('<tr><td colspan="3" class="text-center text-muted py-2 small">No hay componentes vinculados.</td></tr>');
            return;
        }

        itemsList.forEach((item, idx) => {
            const row = `
                <tr>
                    <td class="small fw-semibold text-dark text-truncate" style="max-width: 180px;" title="${item.name}">${item.name}</td>
                    <td style="width: 80px;">
                        <input type="number" step="0.5" class="form-control form-control-sm item-qty" data-idx="${idx}" value="${item.quantity}" min="0.1">
                    </td>
                    <td class="text-center" style="width: 50px;">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-item" data-idx="${idx}">
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
