<script>
    const SAVE_PRESENTATIONS_URL = "{{ route('products.save_presentations') }}";
    const CSRF_TOKEN             = "{{ csrf_token() }}";

    // ═══════════════════════════════════════════════════
    // PRESENTATIONS HELPERS (shared, used by both modals)
    // ═══════════════════════════════════════════════════

    /**
     * Build a presentations <tr> row from an optional data object.
     * Units list is rendered server-side in the <template>, but edit modal
     * doesn't have the template, so we build the row directly.
     */
    function getUnitsOptions() {
        const template = document.getElementById('presentation-row-template');
        if (template && template.content) {
            const select = template.content.querySelector('.pres-idunidad');
            if (select) {
                return select.innerHTML;
            }
        }
        return '';
    }

    function buildEditPresentationRow(data = {}) {
        const unitsOptions = getUnitsOptions();
        const $row = $(`
            <tr class="presentation-row">
                <td><input type="text" class="form-control form-control-sm text-uppercase pres-descripcion" placeholder="Ej: DOCENA" style="min-width:120px;"></td>
                <td><select class="form-select form-select-sm pres-idunidad">${unitsOptions}</select></td>
                <td><input type="number" class="form-control form-control-sm text-center pres-factor" value="1" min="0.0001" step="0.0001"></td>
                <td><input type="number" class="form-control form-control-sm text-end pres-precio-compra" value="0.00" min="0" step="0.01"></td>
                <td><input type="number" class="form-control form-control-sm text-end pres-precio-venta" value="0.00" min="0" step="0.01"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-presentation-edit" title="Eliminar fila">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `);

        if (data.descripcion)       $row.find('.pres-descripcion').val(data.descripcion);
        if (data.idunidad)          $row.find('.pres-idunidad').val(String(data.idunidad));
        if (data.factor_conversion) $row.find('.pres-factor').val(data.factor_conversion);
        if (data.precio_compra !== undefined) $row.find('.pres-precio-compra').val(parseFloat(data.precio_compra).toFixed(2));
        if (data.precio_venta  !== undefined) $row.find('.pres-precio-venta').val(parseFloat(data.precio_venta).toFixed(2));

        return $row;
    }

    function loadEditPresentations(presentations) {
        const $tbody = $('#presentations-tbody-edit');
        $tbody.find('tr.presentation-row').remove();

        if (!presentations || presentations.length === 0) {
            $('#presentations-empty-row-edit').show();
            return;
        }

        $('#presentations-empty-row-edit').hide();
        presentations.forEach(function(p) {
            $tbody.append(buildEditPresentationRow(p));
        });
    }

    function collectPresentationsEdit() {
        const rows = [];
        $('#presentations-tbody-edit tr.presentation-row').each(function() {
            rows.push({
                descripcion:       $(this).find('.pres-descripcion').val(),
                idunidad:          $(this).find('.pres-idunidad').val(),
                factor_conversion: $(this).find('.pres-factor').val(),
                precio_compra:     $(this).find('.pres-precio-compra').val(),
                precio_venta:      $(this).find('.pres-precio-venta').val(),
            });
        });
        return rows;
    }

    /**
     * Validate presentation rows and mark invalid fields.
     * Returns true when all rows pass, false otherwise.
     */
    function validatePresentationsEdit() {
        let isValid = true;

        $('#presentations-tbody-edit tr.presentation-row').each(function() {
            const $row         = $(this);
            const $descripcion = $row.find('.pres-descripcion');
            const $unidad      = $row.find('.pres-idunidad');
            const $factor      = $row.find('.pres-factor');
            const $pCompra     = $row.find('.pres-precio-compra');
            const $pVenta      = $row.find('.pres-precio-venta');

            const descInvalid   = $descripcion.val().trim() === '';
            const unidadInvalid = !$unidad.val();
            const factorInvalid = $factor.val() === '' || parseFloat($factor.val()) <= 0;
            const compraInvalid = $pCompra.val() === '' || isNaN(parseFloat($pCompra.val()));
            const ventaInvalid  = $pVenta.val()  === '' || isNaN(parseFloat($pVenta.val()));

            $descripcion.toggleClass('is-invalid', descInvalid);
            $unidad.toggleClass('is-invalid', unidadInvalid);
            $factor.toggleClass('is-invalid', factorInvalid);
            $pCompra.toggleClass('is-invalid', compraInvalid);
            $pVenta.toggleClass('is-invalid', ventaInvalid);

            if (descInvalid || unidadInvalid || factorInvalid || compraInvalid || ventaInvalid) {
                isValid = false;
            }
        });

        return isValid;
    }

    // ─── Auto-clear is-invalid on user interaction ─────────────────────────────
    $('body').on('input change', '#presentations-tbody-create input, #presentations-tbody-edit input', function() {
        $(this).removeClass('is-invalid');
    });

    $('body').on('change', '#presentations-tbody-create select, #presentations-tbody-edit select', function() {
        $(this).removeClass('is-invalid');
    });
    // ───────────────────────────────────────────────────────────────────────────

    // Add row — EDIT modal
    $('body').on('click', '#btn-add-presentation-edit', function() {
        $('#presentations-empty-row-edit').hide();
        $('#presentations-tbody-edit').append(buildEditPresentationRow());
    });

    // Remove row — EDIT modal
    $('body').on('click', '#presentations-tbody-edit .btn-remove-presentation-edit', function() {
        $(this).closest('tr').remove();
        const hasRows = $('#presentations-tbody-edit tr.presentation-row').length > 0;
        $('#presentations-empty-row-edit').toggle(!hasRows);
    });

    // ═══════════════════════════════════════════════════
    // PRODUCT: success callback
    // ═══════════════════════════════════════════════════

    function success_save_product(msg = null, type = null) {
        toast_msg(msg, type);
        reload_table();
    }

    // ═══════════════════════════════════════════════════
    // EDIT MODAL: select2 init
    // ═══════════════════════════════════════════════════

    function initEditProductSelects() {
        $('#modalEditProduct .edit-product-select').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }

            $(this).select2({
                dropdownParent: $('#modalEditProduct'),
                width: '100%',
                placeholder: '[SELECCIONE]'
            });
        });
    }

    function syncEditProductType(type) {
        $('#edit_original_opcion').val(String(type || ''));
        $(`#form_edit_product input[name="opcion"][value="${type}"]`).prop('checked', true);
        if (String(type) === '2') {
            $('#container-edit-rentable').addClass('d-none');
            $('#edit_rentable').prop('checked', false);
        } else {
            $('#container-edit-rentable').removeClass('d-none');
        }
    }

    // ═══════════════════════════════════════════════════
    // VIEW DETAIL (offcanvas)
    // ═══════════════════════════════════════════════════

    $('body').on('click', '.btn-view', function(e) {
        e.preventDefault();

        const id = $(this).data('id');

        $.ajax({
            url: "{{ route('products.view_detail') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id: id
            },
            beforeSend: function() {
                block_content('#layout-content');
            },
            success: function(r) {
                close_block('#layout-content');

                if (!r.status) {
                    toast_msg(r.msg, r.type);
                    return;
                }

                $('.detail-code').html(r.data.codigo);
                $('.detail-barcode').html(r.data.codigo_barras);
                $('.detail-description').html(r.data.descripcion);
                $('.detail-category').html(r.data.categoria);
                $('.detail-buy').html(r.data.precio_compra);
                $('.detail-sale').html(r.data.precio_venta);
                $('.detail-type').html(r.data.tipo || '-');
                $('.detail-stock').html(r.data.stock || '-');

                $('#offCanvasDetail').offcanvas('show');
            },
            dataType: 'json'
        });
    });

    $('#modalEditProduct').on('shown.bs.modal', function() {
        initEditProductSelects();
    });

    // ═══════════════════════════════════════════════════
    // EDIT MODAL: open + load data
    // ═══════════════════════════════════════════════════

    $('body').on('click', '.btn-detail', function(e) {
        e.preventDefault();

        const id = $(this).data('id');

        $.ajax({
            url: "{{ route('products.detail') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id: id
            },
            beforeSend: function() {
                block_content('#layout-content');
            },
            success: function(r) {
                close_block('#layout-content');

                if (!r.status) {
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                const form = $('#form_edit_product');
                form.find('input[name="id"]').val(r.product.id);
                form.find('input[name="codigo_interno"]').val(r.product.codigo_interno || '');
                form.find('input[name="codigo_barras"]').val(r.product.codigo_barras || '');
                form.find('input[name="codigo_sunat"]').val(r.product.codigo_sunat || '');
                form.find('input[name="descripcion"]').val(r.product.descripcion || '');
                form.find('#edit_rentable').prop('checked', !!(r.product.rentable == 1 || r.product.rentable === true));
                syncEditProductType(r.product.opcion);

                // Load presentations
                loadEditPresentations(r.presentations || []);

                $('#modalEditProduct').modal('show');

                setTimeout(function() {
                    initEditProductSelects();
                    form.find('select[name="idunidad"]').val(String(r.product.idunidad || '')).trigger('change');
                    form.find('select[name="idcategoria"]').val(String(r.product.idcategoria || '')).trigger('change');
                    form.find('select[name="idcodigo_igv"]').val(String(r.product.idcodigo_igv || $('#edit_idcodigo_igv').data('default') || '')).trigger('change');
                    syncEditProductType(r.product.opcion);
                }, 150);
            },
            error: function(xhr) {
                close_block('#layout-content');
                toast_msg(xhr.responseJSON?.msg || 'No se pudo obtener el producto.', xhr.responseJSON?.type || 'error');
            },
            dataType: 'json'
        });
    });

    $('#form_edit_product input[name="opcion"]').on('change', function() {
        const originalType = String($('#edit_original_opcion').val() || '');
        const attemptedType = String($(this).val() || '');

        if (!originalType || originalType === attemptedType) {
            return;
        }

        syncEditProductType(originalType);
        toast_msg('No se puede cambiar el tipo de item al actualizar un producto o servicio.', 'warning');
    });

    // ═══════════════════════════════════════════════════
    // EDIT MODAL: save product + presentations
    // ═══════════════════════════════════════════════════

    $('body').on('click', '.btn-store-product', function(e) {
        e.preventDefault();

        const form = $('#form_edit_product');
        const descripcion = form.find('input[name="descripcion"]');
        const unidad = form.find('select[name="idunidad"]');
        const categoria = form.find('select[name="idcategoria"]');
        const afectacion = form.find('select[name="idcodigo_igv"]');

        descripcion.toggleClass('is-invalid', descripcion.val().trim() === '');
        unidad.toggleClass('is-invalid', !unidad.val());
        categoria.toggleClass('is-invalid', !categoria.val());
        afectacion.toggleClass('is-invalid', !afectacion.val());

        if (descripcion.val().trim() === '' || !unidad.val() || !categoria.val() || !afectacion.val()) {
            toast_msg('Completa los campos obligatorios para actualizar el producto.', 'warning');
            return;
        }

        const productId    = form.find('input[name="id"]').val();
        const presentations = collectPresentationsEdit();

        // Validate presentations before continuing
        if (!validatePresentationsEdit()) {
            toast_msg('Revisa las presentaciones: descripción y unidad son obligatorios, factor debe ser > 0 y los precios no pueden estar vacíos.', 'warning');
            return;
        }
        $.ajax({
            url: "{{ route('products.store') }}",
            method: 'POST',
            data: form.serialize(),
            beforeSend: function() {
                $('.btn-store-product').prop('disabled', true);
                $('.text-store-product').addClass('d-none');
                $('.text-storing-product').removeClass('d-none');
            },
            success: function(r) {
                if (!r.status) {
                    $('.btn-store-product').prop('disabled', false);
                    $('.text-store-product').removeClass('d-none');
                    $('.text-storing-product').addClass('d-none');
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                // Save presentations (fire-and-forget style, but we wait for it)
                $.ajax({
                    url: SAVE_PRESENTATIONS_URL,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        _token: CSRF_TOKEN,
                        product_id: productId,
                        presentations: presentations,
                    }),
                    complete: function() {
                        $('.btn-store-product').prop('disabled', false);
                        $('.text-store-product').removeClass('d-none');
                        $('.text-storing-product').addClass('d-none');
                        $('#modalEditProduct').modal('hide');
                        toast_msg(r.msg, r.type);
                        reload_table();
                    }
                });
            },
            error: function(xhr) {
                $('.btn-store-product').prop('disabled', false);
                $('.text-store-product').removeClass('d-none');
                $('.text-storing-product').addClass('d-none');
                toast_msg(xhr.responseJSON?.msg || 'No se pudo actualizar el producto.', xhr.responseJSON?.type || 'error');
            },
            dataType: 'json'
        });
    });

    // ═══════════════════════════════════════════════════
    // DELETE
    // ═══════════════════════════════════════════════════

    $('body').on('click', '.btn-confirm', function(e) {
        e.preventDefault();

        const id = $(this).data('id');

        Swal.fire({
            title: '&iquest;Est&aacute; seguro?',
            text: 'Esta acci&oacute;n no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'S&iacute;, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ route('products.delete') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor, espere',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function(r) {
                    Swal.close();
                    toast_msg(r.msg, r.type);

                    if (r.status) {
                        reload_table();
                    }
                },
                error: function() {
                    Swal.close();
                    toastr.error('Hubo un error en la solicitud', 'Error');
                }
            });
        });
    });

    // ═══════════════════════════════════════════════════
    // UPLOAD EXCEL
    // ═══════════════════════════════════════════════════

    function renderImportSummary(summary) {
        if (!summary) return;

        $('#import-summary-container').removeClass('d-none');
        $('#badge-imported-count').text(`Importados: ${summary.imported_count || 0}`);
        $('#badge-error-count').text(`Observaciones: ${summary.error_count || 0}`);

        const $tbody = $('#tbody-import-errors');
        $tbody.empty();

        if (summary.error_count > 0 && Array.isArray(summary.errors) && summary.errors.length > 0) {
            $('#import-error-table-wrapper').removeClass('d-none');
            summary.errors.forEach(function(err) {
                const fila = err.fila || '-';
                const desc = $('<div>').text(err.descripcion || '').html();
                const motivo = $('<div>').text(err.motivo || '').html();
                $tbody.append(`
                    <tr>
                        <td class="text-center fw-bold text-secondary">${fila}</td>
                        <td class="fw-semibold">${desc}</td>
                        <td class="text-danger">${motivo}</td>
                    </tr>
                `);
            });
        } else {
            $('#import-error-table-wrapper').addClass('d-none');
        }
    }

    $('body').on('click', '.btn-upload', function(e) {
        e.preventDefault();
        $('#import-summary-container').addClass('d-none');
        $('#import-error-table-wrapper').addClass('d-none');
        $('#tbody-import-errors').empty();
        $('#form_excel').trigger('reset');
        $('#modalUpload').modal('show');
    });

    $('body').on('click', '.btn-upload-product', function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('excel');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            toast_msg('Por favor seleccione un archivo Excel (.xlsx)', 'warning');
            $('#excel').addClass('is-invalid');
            return;
        }
        $('#excel').removeClass('is-invalid');

        const form = new FormData($('#form_excel')[0]);

        $.ajax({
            url: "{{ route('products.upload_excel') }}",
            method: 'POST',
            data: form,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('.btn-upload-product').prop('disabled', true);
                $('.text-upload-product').addClass('d-none');
                $('.text-uploads-product').removeClass('d-none');
            },
            success: function(r) {
                $('.btn-upload-product').prop('disabled', false);
                $('.text-upload-product').removeClass('d-none');
                $('.text-uploads-product').addClass('d-none');
                toast_msg(r.msg, r.type || 'success');

                if (r.summary) {
                    renderImportSummary(r.summary);
                }

                if (r.summary && r.summary.imported_count > 0) {
                    reload_table();
                }

                if (r.status && (!r.summary || r.summary.error_count === 0)) {
                    setTimeout(function() {
                        $('#modalUpload').modal('hide');
                        $('#form_excel').trigger('reset');
                    }, 1200);
                }
            },
            error: function(xhr) {
                $('.btn-upload-product').prop('disabled', false);
                $('.text-upload-product').removeClass('d-none');
                $('.text-uploads-product').addClass('d-none');

                const res = xhr.responseJSON;
                toast_msg(res?.msg || 'Se encontraron observaciones en el archivo.', res?.type || 'warning');

                if (res?.summary) {
                    renderImportSummary(res.summary);
                }
                if (res?.summary?.imported_count > 0) {
                    reload_table();
                }
            },
            dataType: 'json'
        });
    });
</script>
