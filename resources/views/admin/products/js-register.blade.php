<script>
    $(document).ready(function() {
        let productSelectsInitialized = false;

        // ───────── PRESENTATIONS HELPERS (CREATE) ─────────
        const SAVE_PRESENTATIONS_URL = "{{ route('products.save_presentations') }}";
        const CSRF_TOKEN             = "{{ csrf_token() }}";

        function buildPresentationRowHtml(data = {}) {
            const template  = document.getElementById('presentation-row-template');
            const clone     = template.content.cloneNode(true);
            const $row      = $(clone.querySelector('tr'));

            if (data.descripcion)      $row.find('.pres-descripcion').val(data.descripcion);
            if (data.idunidad)         $row.find('.pres-idunidad').val(data.idunidad);
            if (data.factor_conversion)$row.find('.pres-factor').val(data.factor_conversion);
            if (data.precio_compra)    $row.find('.pres-precio-compra').val(data.precio_compra);
            if (data.precio_venta)     $row.find('.pres-precio-venta').val(data.precio_venta);

            return $row;
        }

        function collectPresentations(tbodyId) {
            const rows = [];
            $(`#${tbodyId} tr.presentation-row`).each(function() {
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
         * Validate every presentation row in the given tbody.
         * Marks invalid inputs with 'is-invalid' and returns true if all rows are valid.
         */
        function validatePresentations(tbodyId) {
            let isValid = true;

            $(`#${tbodyId} tr.presentation-row`).each(function() {
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

        function refreshEmptyRow(tbodyId, emptyRowId) {
            const hasRows = $(`#${tbodyId} tr.presentation-row`).length > 0;
            $(`#${emptyRowId}`).toggle(!hasRows);
        }

        // Add row button — CREATE modal
        $('body').on('click', '#btn-add-presentation', function() {
            const $row = buildPresentationRowHtml();
            $('#presentations-empty-row-create').hide();
            $('#presentations-tbody-create').append($row);
        });

        // Remove row — CREATE modal
        $('body').on('click', '#presentations-tbody-create .btn-remove-presentation', function() {
            $(this).closest('tr').remove();
            refreshEmptyRow('presentations-tbody-create', 'presentations-empty-row-create');
        });

        function resetPresentationsCreate() {
            $('#presentations-tbody-create tr.presentation-row').remove();
            $('#presentations-empty-row-create').show();
        }
        // ───────── END PRESENTATIONS HELPERS ─────────

        function initProductSelects() {
            if (productSelectsInitialized) {
                return;
            }

            $('#form_save_product .product-select').select2({
                placeholder: '[SELECCIONE]',
                dropdownParent: $('#modalAddProduct'),
                width: '100%'
            });

            productSelectsInitialized = true;
        }

        function resetProductForm() {
            const form = $('#form_save_product');
            form[0].reset();

            $('#idunidad').val($('#idunidad').data('default') || '').trigger('change');
            $('#idcategoria').val($('#idcategoria').data('default') || '').trigger('change');
            $('#idcodigo_igv').val($('#idcodigo_igv').data('default') || '').trigger('change');
            $('#producto').prop('checked', true).trigger('change');
            $('#rentable').prop('checked', false);
            $('#stock_actual').val(0);
            $('#precio_compra').val('0.00');
            $('#precio_venta').val('0.00');
            $('#codigo_sunat').val('');
            resetPresentationsCreate();
        }

        function toggleProductStockSection() {
            const isService = $('input[name="opcion"]:checked').val() === '2';

            if (isService) {
                $('#container-stock').addClass('d-none');
                $('#container-rentable').addClass('d-none');
                $('#rentable').prop('checked', false);
                $('#stock_actual').val(0);
                $('.finance-col').removeClass('col-md-4').addClass('col-md-6');
                return;
            }

            $('.finance-col').removeClass('col-md-6').addClass('col-md-4');
            $('#container-stock').removeClass('d-none');
            $('#container-rentable').removeClass('d-none');
        }

        function toggleButtonState(button, loading, loadingText = 'Guardando...') {
            const $button = $(button);
            const originalHtml = $button.data('original-html') || $button.html();

            if (! $button.data('original-html')) {
                $button.data('original-html', originalHtml);
            }

            if (loading) {
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>' + loadingText);
                return;
            }

            $button.prop('disabled', false).html($button.data('original-html'));
        }

        $('body').on('click', '.btn-create-product', function(event) {
            event.preventDefault();
            resetProductForm();
            toggleProductStockSection();
            $('#modalAddProduct').modal('show');
        });

        $('#modalAddProduct').on('shown.bs.modal', function() {
            initProductSelects();
        });

        $('#modalAddProduct').on('hidden.bs.modal', function() {
            resetProductForm();
        });

        $('input[name="opcion"]').on('change', function() {
            toggleProductStockSection();
        });

        $('body').on('click', '.btn-save-product', function(event) {
            event.preventDefault();

            const form = $('#form_save_product');
            const descripcion = form.find('input[name="descripcion"]');
            const precioCompra = form.find('input[name="precio_compra"]');
            const precioVenta = form.find('input[name="precio_venta"]');
            const unidad = form.find('select[name="idunidad"]');
            const categoria = form.find('select[name="idcategoria"]');
            const afectacion = form.find('select[name="idcodigo_igv"]');
            const isService = form.find('input[name="opcion"]:checked').val() === '2';
            const stockActual = form.find('input[name="stock_actual"]');

            descripcion.toggleClass('is-invalid', descripcion.val().trim() === '');
            precioCompra.toggleClass('is-invalid', precioCompra.val().trim() === '');
            precioVenta.toggleClass('is-invalid', precioVenta.val().trim() === '');
            unidad.toggleClass('is-invalid', !unidad.val());
            categoria.toggleClass('is-invalid', !categoria.val());
            afectacion.toggleClass('is-invalid', !afectacion.val());
            stockActual.toggleClass('is-invalid', !isService && stockActual.val().trim() === '');

            if (
                descripcion.val().trim() === '' ||
                precioCompra.val().trim() === '' ||
                precioVenta.val().trim() === '' ||
                !unidad.val() ||
                !categoria.val() ||
                !afectacion.val() ||
                (!isService && stockActual.val().trim() === '')
            ) {
                return;
            }

            const button = this;
            const presentations = collectPresentations('presentations-tbody-create');

            // Validate presentations before continuing
            if (!validatePresentations('presentations-tbody-create')) {
                toast_msg('Revisa las presentaciones: descripción y unidad son obligatorios, factor debe ser > 0 y los precios no pueden estar vacíos.', 'warning');
                return;
            }
            $.ajax({
                url: "{{ route('products.save') }}",
                method: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    toggleButtonState(button, true);
                },
                success: function(r) {
                    if (!r.status) {
                        toggleButtonState(button, false);
                        toast_msg(r.msg, r.type || 'warning');
                        return;
                    }

                    // If there are presentations, persist them now
                    if (presentations.length > 0 && r.product_id) {
                        $.ajax({
                            url: SAVE_PRESENTATIONS_URL,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                _token: CSRF_TOKEN,
                                product_id: r.product_id,
                                presentations: presentations,
                            }),
                            complete: function() {
                                toggleButtonState(button, false);
                                form.trigger('reset');
                                $('#modalAddProduct').modal('hide');
                                success_save_product(r.msg, r.type);
                            }
                        });
                    } else {
                        toggleButtonState(button, false);
                        form.trigger('reset');
                        $('#modalAddProduct').modal('hide');
                        success_save_product(r.msg, r.type);
                    }
                },
                error: function(xhr) {
                    toggleButtonState(button, false);
                    toast_msg(xhr.responseJSON?.msg || 'No se pudo guardar el producto.', xhr.responseJSON?.type || 'error');
                },
                dataType: 'json'
            });
        });

        resetProductForm();
        toggleProductStockSection();
    });
</script>
