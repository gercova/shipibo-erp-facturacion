@extends('admin.layout')

@section('content')
<div class="container-fluid py-3">
    {{-- Encabezado principal --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning-subtle text-warning fs-12 px-2 py-1 rounded-pill">
                    <i class="ri-cocktail-line me-1"></i> HS Cocteler&iacute;a &bull; Eventos
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0 mt-1">Carta de C&oacute;cteles</h3>
            <p class="text-muted small mb-0">Administra los c&oacute;cteles de autor, cl&aacute;sicos y mocktails visualizables en tablets durante eventos.</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('admin.cocktail_menu.tablet') }}" target="_blank" class="btn btn-warning fw-bold px-3 py-2 shadow-sm d-flex align-items-center text-dark">
                <i class="ri-tablet-line me-2 fs-18"></i> Abrir Modo Tablet
            </a>

            <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalCategories">
                <i class="ri-folder-settings-line me-1"></i> Categor&iacute;as
            </button>

            <button type="button" class="btn btn-primary fw-bold px-3 py-2 shadow-sm" id="btnOpenNewCocktailModal">
                <i class="ri-add-line me-1"></i> Nuevo C&oacute;ctel
            </button>
        </div>
    </div>

    {{-- Tarjetas KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-goblet-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total en Carta</span>
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
                            <i class="ri-eye-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Visibles en Tablet</span>
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
                        <div class="avatar-sm bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="ri-magic-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">C&oacute;cteles de Autor</span>
                            <h4 class="fw-bold mb-0 text-warning" id="kpi_destacados">{{ $kpis['destacados'] }}</h4>
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
                            <i class="ri-folder-line fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Categor&iacute;as Men&uacute;</span>
                            <h4 class="fw-bold mb-0 text-dark" id="kpi_categorias">{{ $kpis['categorias'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Cócteles --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="ri-list-check me-2 text-primary"></i> Cat&aacute;logo de C&oacute;cteles para Tablet
            </h5>
            <div class="small text-muted">
                Los cambios se reflejan en tiempo real en las tablets del evento.
            </div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="tableCocktails" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">Foto</th>
                            <th style="min-width: 200px;">C&oacute;ctel / Categor&iacute;a</th>
                            <th style="min-width: 250px;">Descripci&oacute;n & Notas de Cata</th>
                            <th style="width: 140px;">Cristaler&iacute;a</th>
                            <th style="width: 100px;">Precio</th>
                            <th style="width: 80px;" class="text-center">Visible</th>
                            <th style="width: 90px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('admin.cocktail_menu.modals')

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const CSRF_TOKEN = "{{ csrf_token() }}";

    // 1. Inicializar DataTable
    const table = $('#tableCocktails').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.cocktail_menu.get') }}",
        columns: [
            { data: 'imagen_thumb', name: 'imagen_thumb', orderable: false, searchable: false, className: 'text-center' },
            { data: 'nombre', name: 'nombre' },
            { data: 'descripcion_corta', name: 'descripcion_corta' },
            { data: 'cristaleria', name: 'cristaleria' },
            { data: 'precio', name: 'precio', className: 'text-end' },
            { data: 'activo', name: 'activo', orderable: false, searchable: false, className: 'text-center' },
            { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        order: [[1, 'asc']]
    });

    // 2. Abrir Modal Nuevo Cóctel
    $('#btnOpenNewCocktailModal').on('click', function() {
        $('#formCocktail')[0].reset();
        $('#cocktail_id').val('');
        $('#modalCocktailActionTitle').text('Nuevo Cóctel en Carta');
        $('#containerCocktailImagePreview').hide();
        $('#activo').prop('checked', true);
        $('#modalCocktail').modal('show');
    });

    // 3. Autocompletar nombre y precio si selecciona un producto de catálogo
    $('#product_id').on('change', function() {
        const selected = $(this).find('option:selected');
        const prodNombre = selected.data('nombre');
        const prodPrecio = selected.data('precio');

        if (prodNombre && !$('#nombre').val()) {
            $('#nombre').val(prodNombre);
        }
        if (prodPrecio !== undefined && parseFloat(prodPrecio) > 0) {
            $('#precio').val(parseFloat(prodPrecio).toFixed(2));
        }
    });

    // 4. Guardar Cóctel (Crear o Editar vía AJAX con FormData para fotos)
    $('#formCocktail').on('submit', function(e) {
        e.preventDefault();

        const form = document.getElementById('formCocktail');
        const formData = new FormData(form);
        const btn = $('#btnSaveCocktail');

        btn.find('.btn-text').addClass('d-none');
        btn.find('.btn-loading').removeClass('d-none');
        btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.cocktail_menu.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                btn.find('.btn-text').removeClass('d-none');
                btn.find('.btn-loading').addClass('d-none');
                btn.prop('disabled', false);

                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: res.msg,
                        timer: 1800,
                        showConfirmButton: false
                    });
                    $('#modalCocktail').modal('hide');
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire({ icon: 'warning', title: 'Atención', text: res.msg });
                }
            },
            error: function(xhr) {
                btn.find('.btn-text').removeClass('d-none');
                btn.find('.btn-loading').addClass('d-none');
                btn.prop('disabled', false);

                const msg = xhr.responseJSON?.msg || (xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error al guardar el cóctel.');
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            }
        });
    });

    // 5. Cargar Cóctel para Edición
    $('body').on('click', '.btn-edit-cocktail', function() {
        const id = $(this).data('id');

        $.post("{{ route('admin.cocktail_menu.detail') }}", { _token: CSRF_TOKEN, id: id }, function(res) {
            if (!res.status) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.msg });
                return;
            }

            const c = res.item;
            $('#cocktail_id').val(c.id);
            $('#modalCocktailActionTitle').text('Editar: ' + c.nombre);
            $('#menu_category_id').val(c.menu_category_id);
            $('#product_id').val(c.product_id || '');
            $('#nombre').val(c.nombre);
            $('#descripcion_corta').val(c.descripcion_corta || '');
            $('#cristaleria').val(c.cristaleria || '');
            $('#garnish').val(c.garnish || '');
            $('#precio').val(parseFloat(c.precio || 0).toFixed(2));
            $('#orden').val(c.orden);
            $('#es_autor').prop('checked', !!c.es_autor);
            $('#destacado').prop('checked', !!c.destacado);
            $('#activo').prop('checked', !!c.activo);

            if (c.image_url) {
                $('#cocktailImagePreview').attr('src', c.image_url);
                $('#containerCocktailImagePreview').show();
            } else {
                $('#containerCocktailImagePreview').hide();
            }

            $('#modalCocktail').modal('show');
        }, 'json');
    });

    // 6. Alternar Estado Activo / Inactivo
    $('body').on('change', '.btn-toggle-status', function() {
        const id = $(this).data('id');
        const isChecked = $(this).is(':checked');

        $.post("{{ route('admin.cocktail_menu.toggle_status') }}", { _token: CSRF_TOKEN, id: id }, function(res) {
            if (!res.status) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.msg });
                table.ajax.reload(null, false);
            }
        }, 'json');
    });

    // 7. Eliminar Cóctel
    $('body').on('click', '.btn-delete-cocktail', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: '¿Eliminar cóctel?',
            text: 'Se retirará "' + nombre + '" de la carta de cócteles.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('admin.cocktail_menu.delete') }}", { _token: CSRF_TOKEN, id: id }, function(res) {
                    if (res.status) {
                        Swal.fire({ icon: 'success', title: 'Eliminado', text: res.msg, timer: 1500, showConfirmButton: false });
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.msg });
                    }
                }, 'json');
            }
        });
    });

    // 8. Gestión de Categorías
    $('#formCategory').on('submit', function(e) {
        e.preventDefault();

        const id = $('#category_id').val();
        const data = {
            _token: CSRF_TOKEN,
            id: id,
            nombre: $('#cat_nombre').val(),
            descripcion: $('#cat_descripcion').val(),
            orden: $('#cat_orden').val(),
            icono: $('#cat_icono').val(),
        };

        $.post("{{ route('admin.cocktail_menu.store_category') }}", data, function(res) {
            if (res.status) {
                Swal.fire({ icon: 'success', title: 'Éxito', text: res.msg, timer: 1500, showConfirmButton: false });
                setTimeout(function() { location.reload(); }, 1200);
            } else {
                Swal.fire({ icon: 'warning', title: 'Atención', text: res.msg });
            }
        }, 'json').fail(function(xhr) {
            const msg = xhr.responseJSON?.msg || 'Error al guardar categoría.';
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        });
    });

    $('body').on('click', '.btn-edit-cat', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const desc = $(this).data('descripcion');
        const orden = $(this).data('orden');
        const icono = $(this).data('icono');

        $('#category_id').val(id);
        $('#cat_nombre').val(nombre);
        $('#cat_descripcion').val(desc);
        $('#cat_orden').val(orden);
        $('#cat_icono').val(icono);

        $('#categoryFormTitle').text('Editar Categoría: ' + nombre);
        $('#btnSaveCategoryText').text('Actualizar');
        $('#btnCancelCategoryEdit').show();
    });

    $('#btnCancelCategoryEdit').on('click', function() {
        $('#category_id').val('');
        $('#formCategory')[0].reset();
        $('#categoryFormTitle').text('Agregar Nueva Categoría');
        $('#btnSaveCategoryText').text('Guardar Categoría');
        $(this).hide();
    });

    $('body').on('click', '.btn-delete-cat', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: '¿Eliminar categoría?',
            text: 'Se eliminará la categoría "' + nombre + '".',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('admin.cocktail_menu.delete_category') }}", { _token: CSRF_TOKEN, id: id }, function(res) {
                    if (res.status) {
                        Swal.fire({ icon: 'success', title: 'Eliminado', text: res.msg, timer: 1500, showConfirmButton: false });
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        Swal.fire({ icon: 'warning', title: 'Atención', text: res.msg });
                    }
                }, 'json').fail(function(xhr) {
                    const msg = xhr.responseJSON?.msg || 'Error al eliminar categoría.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                });
            }
        });
    });
});
</script>
@endsection
