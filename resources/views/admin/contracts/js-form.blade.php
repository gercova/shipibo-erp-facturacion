<script>
$(document).ready(function() {
    // Initialize Select2
    if ($('.select2').length > 0) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    /* -------------------------------------------------------------
       1. DIGITAL SIGNATURE CANVAS LOGIC
    ------------------------------------------------------------- */
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas ? canvas.getContext('2d') : null;
    let isDrawing = false;
    let hasDrawn = false;
    let lastX = 0;
    let lastY = 0;

    if (canvas && ctx) {
        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            // Preserve drawn content if resizing
            if (canvas.width !== rect.width || canvas.height !== rect.height) {
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.drawImage(canvas, 0, 0);

                canvas.width = rect.width;
                canvas.height = 200; // Fixed height

                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                ctx.drawImage(tempCanvas, 0, 0);
            }
        }

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function getCoordinates(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDrawing(e) {
            e.preventDefault();
            isDrawing = true;
            const coords = getCoordinates(e);
            lastX = coords.x;
            lastY = coords.y;
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const coords = getCoordinates(e);

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();

            lastX = coords.x;
            lastY = coords.y;
            hasDrawn = true;
            $('#signature-status-text').text('Firma trazada en el lienzo');
        }

        function stopDrawing(e) {
            if (isDrawing) {
                isDrawing = false;
                if (hasDrawn) {
                    $('#signature_client_data').val(canvas.toDataURL('image/png'));
                }
            }
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
        canvas.addEventListener('touchcancel', stopDrawing);

        $('#btn-clear-signature').on('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawn = false;
            $('#signature_client_data').val('');
            $('#signature-status-text').text('Lienzo vacío. Firme con el mouse o pantalla táctil.');
        });
    }

    // Signature file upload preview
    $('#signature_file_input').on('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#signature-preview-img').attr('src', event.target.result);
                $('#signature-preview-container').show();
                if (canvas && ctx) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    hasDrawn = false;
                    $('#signature_client_data').val('');
                }
                $('#signature-status-text').text('Archivo de firma seleccionado: ' + file.name);
            };
            reader.readAsDataURL(file);
        }
    });

    $('#btn-remove-file-signature').on('click', function() {
        $('#signature_file_input').val('');
        $('#signature-preview-container').hide();
        $('#signature-preview-img').attr('src', '');
        $('#signature-status-text').text('Lienzo vacío. Firme con el mouse o pantalla táctil.');
    });

    /* -------------------------------------------------------------
       2. ITEMS / SERVICES REPEATER LOGIC
    ------------------------------------------------------------- */
    let itemIndex = $('#table-contract-items tbody tr').length;

    function recalculateTotals() {
        let subtotal = 0;
        $('#table-contract-items tbody tr').each(function() {
            let row = $(this);
            let qty = parseFloat(row.find('.item-qty').val()) || 0;
            let price = parseFloat(row.find('.item-price').val()) || 0;
            let lineTotal = qty * price;
            row.find('.item-subtotal').text(lineTotal.toFixed(2));
            row.find('.item-subtotal-input').val(lineTotal.toFixed(2));
            subtotal += lineTotal;
        });

        let applyIgv = $('#apply_igv').is(':checked');
        let igv = applyIgv ? (subtotal * 0.18) : 0;
        let total = subtotal + igv;

        $('#display-subtotal').text(subtotal.toFixed(2));
        $('#display-igv').text(igv.toFixed(2));
        $('#display-total').text(total.toFixed(2));

        $('#input-subtotal').val(subtotal.toFixed(2));
        $('#input-igv').val(igv.toFixed(2));
        $('#input-total').val(total.toFixed(2));
    }

    $('#btn-add-item').on('click', function() {
        addItemRow();
    });

    function addItemRow(prodId = '', desc = '', qty = 1, price = 0) {
        let optionsHtml = '<option value="">-- Servicio o Producto Personalizado --</option>';
        if (window.productCatalog && window.productCatalog.length > 0) {
            window.productCatalog.forEach(p => {
                let selected = (prodId && prodId == p.id) ? 'selected' : '';
                optionsHtml += `<option value="${p.id}" data-price="${p.precio_venta}" data-name="${p.descripcion}" ${selected}>${p.descripcion} (S/ ${parseFloat(p.precio_venta).toFixed(2)})</option>`;
            });
        }

        let lineSubtotal = (qty * price).toFixed(2);

        let rowHtml = `
            <tr class="item-row" data-index="${itemIndex}">
                <td class="text-center row-number">${$('#table-contract-items tbody tr').length + 1}</td>
                <td>
                    <select class="form-select form-select-sm mb-1 select-product-item">
                        ${optionsHtml}
                    </select>
                    <input type="text" name="items[${itemIndex}][descripcion]" class="form-control form-control-sm item-desc" placeholder="Descripción detallada del servicio o producto" value="${desc}" required />
                    <input type="hidden" name="items[${itemIndex}][idproducto]" class="item-product-id" value="${prodId}" />
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${itemIndex}][cantidad]" class="form-control form-control-sm text-center item-qty" value="${qty}" required />
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${itemIndex}][precio_unitario]" class="form-control form-control-sm text-end item-price" value="${price}" required />
                </td>
                <td class="text-end fw-bold">
                    <span class="item-subtotal">${lineSubtotal}</span>
                    <input type="hidden" name="items[${itemIndex}][subtotal]" class="item-subtotal-input" value="${lineSubtotal}" />
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" title="Quitar ítem">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#table-contract-items tbody').append(rowHtml);
        itemIndex++;
        recalculateTotals();
    }

    $(document).on('change', '.select-product-item', function() {
        let row = $(this).closest('tr');
        let selected = $(this).find('option:selected');
        let prodId = $(this).val();

        if (prodId) {
            let name = selected.data('name');
            let price = selected.data('price') || 0;
            row.find('.item-desc').val(name);
            row.find('.item-product-id').val(prodId);
            row.find('.item-price').val(parseFloat(price).toFixed(2));
        } else {
            row.find('.item-product-id').val('');
        }
        recalculateTotals();
    });

    $(document).on('input', '.item-qty, .item-price', function() {
        recalculateTotals();
    });

    $('#apply_igv').on('change', function() {
        recalculateTotals();
    });

    $(document).on('click', '.btn-remove-item', function() {
        if ($('#table-contract-items tbody tr').length <= 1) {
            toastr.warning('El contrato debe contener al menos un servicio o producto.');
            return;
        }
        $(this).closest('tr').remove();
        // Re-number rows
        $('#table-contract-items tbody tr').each(function(index) {
            $(this).find('.row-number').text(index + 1);
        });
        recalculateTotals();
    });

    /* -------------------------------------------------------------
       3. CLAUSES REPEATER LOGIC
    ------------------------------------------------------------- */
    let clauseIndex = $('#clauses-container .clause-card').length;

    $('#btn-add-clause').on('click', function() {
        addClauseCard();
    });

    function addClauseCard(title = '', content = '') {
        let num = $('#clauses-container .clause-card').length + 1;
        if (!title) {
            title = `CLÁUSULA ${num}: `;
        }

        let html = `
            <div class="card mb-3 clause-card border" data-index="${clauseIndex}">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary clause-header-title">
                        <i class="ri-article-line me-1"></i> Cláusula #${num}
                    </span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-clause-up" title="Subir">
                            <i class="ri-arrow-up-line"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-clause-down" title="Bajar">
                            <i class="ri-arrow-down-line"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-clause" title="Eliminar Cláusula">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Título de la Cláusula</label>
                        <input type="text" name="clauses[${clauseIndex}][titulo]" class="form-control form-control-sm clause-title-input" value="${title}" placeholder="Ej: PRIMERA: OBJETO DEL CONTRATO" required />
                    </div>
                    <div>
                        <label class="form-label small fw-bold">Contenido de la Cláusula</label>
                        <textarea name="clauses[${clauseIndex}][contenido]" class="form-control form-control-sm clause-content-input" rows="3" placeholder="Redacte el texto legal de la cláusula..." required>${content}</textarea>
                    </div>
                </div>
            </div>
        `;

        $('#clauses-container').append(html);
        clauseIndex++;
        updateClauseHeaders();
    }

    function updateClauseHeaders() {
        $('#clauses-container .clause-card').each(function(index) {
            $(this).find('.clause-header-title').html(`<i class="ri-article-line me-1"></i> Cláusula #${index + 1}`);
        });
    }

    $(document).on('click', '.btn-remove-clause', function() {
        $(this).closest('.clause-card').remove();
        updateClauseHeaders();
    });

    $(document).on('click', '.btn-clause-up', function() {
        let card = $(this).closest('.clause-card');
        let prev = card.prev('.clause-card');
        if (prev.length) {
            card.insertBefore(prev);
            updateClauseHeaders();
        }
    });

    $(document).on('click', '.btn-clause-down', function() {
        let card = $(this).closest('.clause-card');
        let next = card.next('.clause-card');
        if (next.length) {
            card.insertAfter(next);
            updateClauseHeaders();
        }
    });

    $('#btn-load-default-clauses').on('click', function() {
        Swal.fire({
            title: '¿Cargar cláusulas estándar?',
            text: 'Se reemplazarán o agregarán las cláusulas legales recomendadas.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cargar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed && window.defaultContractClauses) {
                $('#clauses-container').empty();
                window.defaultContractClauses.forEach(c => {
                    addClauseCard(c.titulo, c.contenido);
                });
                toastr.success('Cláusulas estándar cargadas exitosamente.');
            }
        });
    });

    /* -------------------------------------------------------------
       4. QUICK CLIENT MODAL & DNI/RUC SEARCH
    ------------------------------------------------------------- */
    $('#btn-quick-search-dni-ruc').on('click', function() {
        let doc = $('#quick-client-documento').val().trim();
        let iddoc = $('#quick-client-iddoc').val();

        if (!doc) {
            toastr.warning('Ingrese el número de documento.');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Buscando...');

        $.ajax({
            url: "{{ route('admin.search_client_document_record') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                type_document: iddoc,
                dni_ruc: doc,
                iddoc: iddoc,
                nro_documento: doc
            },
            dataType: "json",
            success: function(r) {
                btn.prop('disabled', false).html('<i class="ri-search-line"></i> Buscar');
                if (r.status) {
                    $('#quick-client-nombres').val(r.nombres);
                    if (r.direccion && r.direccion !== '-') {
                        $('#quick-client-direccion').val(r.direccion);
                    } else {
                        $('#quick-client-direccion').val('-');
                    }
                    toastr.success('Datos encontrados correctamente.');
                } else {
                    toastr.warning(r.msg || 'No se encontraron resultados.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="ri-search-line"></i> Buscar');
                let msg = xhr.responseJSON?.msg || 'Error al consultar el documento.';
                toastr.warning(msg);
            }
        });
    });

    $('#form-quick-client').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let clientName = $('#quick-client-nombres').val().trim();
        let clientDoc = $('#quick-client-documento').val().trim();

        $.ajax({
            url: "{{ route('clients.save') }}",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function(r) {
                if (r.status) {
                    toastr.success('Cliente registrado exitosamente.');
                    let clientId = r.last_id || (r.data ? r.data.id : null);
                    let label = clientName + (clientDoc ? ' (' + clientDoc + ')' : '');
                    let newOption = new Option(label, clientId, true, true);
                    $('#idcliente').append(newOption).trigger('change');
                    $('#modalQuickCreateClient').modal('hide');
                    $('#form-quick-client')[0].reset();
                    $('#quick-client-direccion').val('-');
                } else {
                    toastr.error(r.msg || 'Error al guardar el cliente.');
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.msg || 'Error al procesar la solicitud.';
                toastr.error(msg);
            }
        });
    });

    /* -------------------------------------------------------------
       5. FORM SUBMISSION (CREATE & EDIT)
    ------------------------------------------------------------- */
    $('#form-contract').on('submit', function(e) {
        e.preventDefault();

        // Check items
        if ($('#table-contract-items tbody tr').length === 0) {
            toastr.warning('Debe agregar al menos un servicio o producto al contrato.');
            return;
        }

        // Extract signature from canvas if drawn
        if (canvas && hasDrawn) {
            $('#signature_client_data').val(canvas.toDataURL('image/png'));
        }

        let form = this;
        let formData = new FormData(form);
        let submitUrl = $(form).attr('action');

        Swal.fire({
            title: 'Guardando Contrato',
            text: 'Procesando firma y generando documento PDF...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: submitUrl,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                Swal.close();
                if (response.status) {
                    Swal.fire({
                        title: '¡Contrato Guardado!',
                        text: response.msg,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '<i class="ri-printer-line me-1"></i> Imprimir / Ver PDF',
                        cancelButtonText: '<i class="ri-list-check me-1"></i> Ir a la Lista',
                        confirmButtonColor: '#0061f2',
                        cancelButtonColor: '#6c757d',
                    }).then((result) => {
                        if (result.isConfirmed && response.pdf) {
                            let pdfUrl = "{{ asset('files/contracts') }}/" + response.pdf;
                            window.open(pdfUrl, '_blank');
                            window.location.href = "{{ route('admin.contracts') }}";
                        } else {
                            window.location.href = "{{ route('admin.contracts') }}";
                        }
                    });
                } else {
                    toastr.error(response.msg || 'Ocurrió un error al guardar.');
                }
            },
            error: function(xhr) {
                Swal.close();
                let errors = xhr.responseJSON?.errors;
                if (errors) {
                    let firstErr = Object.values(errors)[0][0];
                    toastr.error(firstErr);
                } else {
                    let msg = xhr.responseJSON?.msg || 'Error inesperado al guardar el contrato.';
                    toastr.error(msg);
                }
            }
        });
    });

    // Initial calculation
    recalculateTotals();
});
</script>
