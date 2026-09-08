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

        // Update installments and 20% guarantee
        updateInstallmentsAfterTotalChange(total);
    }

    /* -------------------------------------------------------------
       2.1. PAYMENT SCHEDULE / CREDIT INSTALLMENTS LOGIC
    ------------------------------------------------------------- */
    let currentSplitMode = '50_50';
    let isUserEditingInstallmentsManually = false;

    function getContractTotal() {
        return parseFloat($('#input-total').val()) || 0;
    }

    function getEventDate() {
        return $('input[name="fecha_evento"]').val() || $('input[name="fecha_emision"]').val() || '{{ date("Y-m-d") }}';
    }

    function getIssueDate() {
        return $('input[name="fecha_emision"]').val() || '{{ date("Y-m-d") }}';
    }

    function buildDefaultSchedule(total, splitMode = '50_50') {
        let issueDate = getIssueDate();
        let eventDate = getEventDate();

        if (splitMode === '50_50') {
            let m1 = Math.round(total * 0.50 * 100) / 100;
            let m2 = Math.round((total - m1) * 100) / 100;
            return [
                {
                    numero_cuota: 1,
                    descripcion: 'Adelanto Inicial (50%)',
                    porcentaje: 50,
                    fecha_vencimiento: issueDate,
                    monto: m1.toFixed(2),
                },
                {
                    numero_cuota: 2,
                    descripcion: 'Saldo Final (50%)',
                    porcentaje: 50,
                    fecha_vencimiento: eventDate,
                    monto: m2.toFixed(2),
                }
            ];
        } else if (splitMode === '50_25_25') {
            let m1 = Math.round(total * 0.50 * 100) / 100;
            let m2 = Math.round(total * 0.25 * 100) / 100;
            let m3 = Math.round((total - m1 - m2) * 100) / 100;
            return [
                {
                    numero_cuota: 1,
                    descripcion: 'Adelanto Inicial (50%)',
                    porcentaje: 50,
                    fecha_vencimiento: issueDate,
                    monto: m1.toFixed(2),
                },
                {
                    numero_cuota: 2,
                    descripcion: 'Cuota 2 - Saldo Intermedio (25%)',
                    porcentaje: 25,
                    fecha_vencimiento: issueDate,
                    monto: m2.toFixed(2),
                },
                {
                    numero_cuota: 3,
                    descripcion: 'Cuota 3 - Saldo Final (25%)',
                    porcentaje: 25,
                    fecha_vencimiento: eventDate,
                    monto: m3.toFixed(2),
                }
            ];
        } else if (splitMode === '50_3') {
            let m1 = Math.round(total * 0.50 * 100) / 100;
            let remaining = total - m1;
            let part = Math.round((remaining / 3) * 100) / 100;
            let m2 = part;
            let m3 = part;
            let m4 = Math.round((remaining - m2 - m3) * 100) / 100;
            return [
                {
                    numero_cuota: 1,
                    descripcion: 'Adelanto Inicial (50%)',
                    porcentaje: 50,
                    fecha_vencimiento: issueDate,
                    monto: m1.toFixed(2),
                },
                {
                    numero_cuota: 2,
                    descripcion: 'Cuota 2 - Saldo 1/3',
                    porcentaje: 16.67,
                    fecha_vencimiento: issueDate,
                    monto: m2.toFixed(2),
                },
                {
                    numero_cuota: 3,
                    descripcion: 'Cuota 3 - Saldo 2/3',
                    porcentaje: 16.67,
                    fecha_vencimiento: issueDate,
                    monto: m3.toFixed(2),
                },
                {
                    numero_cuota: 4,
                    descripcion: 'Cuota 4 - Saldo Final',
                    porcentaje: 16.66,
                    fecha_vencimiento: eventDate,
                    monto: m4.toFixed(2),
                }
            ];
        }
        return [];
    }

    function renderInstallments(installments) {
        let tbody = $('#table-contract-installments tbody');
        tbody.empty();

        if (!installments || installments.length === 0) {
            installments = buildDefaultSchedule(getContractTotal(), currentSplitMode);
        }

        installments.forEach((inst, idx) => {
            let nro = idx + 1;
            let desc = inst.descripcion || (nro === 1 ? 'Adelanto Inicial (50%)' : `Cuota ${nro}`);
            let pct = parseFloat(inst.porcentaje) || 0;
            let fecha = inst.fecha_vencimiento ? (String(inst.fecha_vencimiento).substring(0, 10)) : getIssueDate();
            let monto = parseFloat(inst.monto) || 0;

            let rowHtml = `
                <tr class="installment-row" data-index="${idx}">
                    <td class="text-center installment-num fw-bold">${nro}</td>
                    <td>
                        <input type="text" name="installments[${idx}][descripcion]" class="form-control form-control-sm inst-desc" value="${desc}" required />
                        <input type="hidden" name="installments[${idx}][numero_cuota]" class="inst-num" value="${nro}" />
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" max="100" name="installments[${idx}][porcentaje]" class="form-control form-control-sm text-center inst-pct" value="${pct.toFixed(2)}" />
                            <span class="input-group-text">%</span>
                        </div>
                    </td>
                    <td>
                        <input type="date" name="installments[${idx}][fecha_vencimiento]" class="form-control form-control-sm text-center inst-due-date" value="${fecha}" required />
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" name="installments[${idx}][monto]" class="form-control form-control-sm text-end fw-bold inst-amount" value="${monto.toFixed(2)}" required />
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-installment" title="Eliminar Cuota" ${installments.length <= 1 ? 'disabled' : ''}>
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(rowHtml);
        });

        checkInstallmentBalance();
    }

    function checkInstallmentBalance() {
        let total = getContractTotal();
        let sum = 0;

        $('#table-contract-installments tbody tr').each(function() {
            let amt = parseFloat($(this).find('.inst-amount').val()) || 0;
            sum += amt;
        });

        sum = Math.round(sum * 100) / 100;
        $('#display-installments-sum').text(sum.toFixed(2));

        let diff = Math.round((total - sum) * 100) / 100;
        let alertBox = $('#installment-balance-alert');

        if (Math.abs(diff) <= 0.05) {
            alertBox.removeClass('bg-danger-subtle border-danger text-danger bg-warning-subtle border-warning text-warning')
                    .addClass('bg-success-subtle border-success text-success')
                    .html('<i class="ri-checkbox-circle-line fs-5 me-2"></i><span>El cronograma de cuotas cuadra exactamente con el total del contrato (' + total.toFixed(2) + ').</span>');
            return true;
        } else {
            alertBox.removeClass('bg-success-subtle border-success text-success')
                    .addClass('bg-danger-subtle border-danger text-danger')
                    .html('<i class="ri-error-warning-line fs-5 me-2"></i><span><strong>Descuadre en cuotas:</strong> La suma programada (S/ ' + sum.toFixed(2) + ') difiere del total contratado (S/ ' + total.toFixed(2) + ') por S/ ' + Math.abs(diff).toFixed(2) + '. Ajuste los montos.</span>');
            return false;
        }
    }

    function updateInstallmentsAfterTotalChange(total) {
        // Guarantee 20% (HS Coctelería clause)
        let guarantee = total * 0.20;
        $('#display-guarantee-val').text(guarantee.toFixed(2));

        if (!isUserEditingInstallmentsManually) {
            let schedule = buildDefaultSchedule(total, currentSplitMode);
            renderInstallments(schedule);
        } else {
            checkInstallmentBalance();
        }
    }

    // Quick split buttons
    $(document).on('click', '.btn-quick-split', function() {
        $('.btn-quick-split').removeClass('active');
        $(this).addClass('active');
        currentSplitMode = $(this).data('split');
        isUserEditingInstallmentsManually = false;
        let total = getContractTotal();
        let schedule = buildDefaultSchedule(total, currentSplitMode);
        renderInstallments(schedule);
    });

    // Manual amount editing in installment row
    $(document).on('input', '.inst-amount', function() {
        isUserEditingInstallmentsManually = true;
        let total = getContractTotal();
        let row = $(this).closest('tr');
        let amt = parseFloat($(this).val()) || 0;
        if (total > 0) {
            let pct = (amt / total) * 100;
            row.find('.inst-pct').val(pct.toFixed(2));
        }
        checkInstallmentBalance();
    });

    // Manual percentage editing in installment row
    $(document).on('input', '.inst-pct', function() {
        isUserEditingInstallmentsManually = true;
        let total = getContractTotal();
        let row = $(this).closest('tr');
        let pct = parseFloat($(this).val()) || 0;
        let amt = (total * pct) / 100;
        row.find('.inst-amount').val(amt.toFixed(2));
        checkInstallmentBalance();
    });

    // Add installment manually
    $('#btn-add-installment').on('click', function() {
        isUserEditingInstallmentsManually = true;
        let total = getContractTotal();
        let currentSum = 0;
        let existingRows = [];

        $('#table-contract-installments tbody tr').each(function(idx) {
            let amt = parseFloat($(this).find('.inst-amount').val()) || 0;
            currentSum += amt;
            existingRows.push({
                numero_cuota: idx + 1,
                descripcion: $(this).find('.inst-desc').val(),
                porcentaje: $(this).find('.inst-pct').val(),
                fecha_vencimiento: $(this).find('.inst-due-date').val(),
                monto: amt.toFixed(2),
            });
        });

        let diff = Math.max(0, Math.round((total - currentSum) * 100) / 100);
        let pct = total > 0 ? ((diff / total) * 100).toFixed(2) : '0.00';
        let count = existingRows.length + 1;

        existingRows.push({
            numero_cuota: count,
            descripcion: 'Cuota ' + count,
            porcentaje: pct,
            fecha_vencimiento: getEventDate(),
            monto: diff.toFixed(2),
        });

        renderInstallments(existingRows);
    });

    // Remove installment row
    $(document).on('click', '.btn-remove-installment', function() {
        isUserEditingInstallmentsManually = true;
        let rows = $('#table-contract-installments tbody tr');
        if (rows.length <= 1) {
            toastr.warning('Debe mantener al menos una cuota programada.');
            return;
        }

        $(this).closest('tr').remove();

        let existingRows = [];
        $('#table-contract-installments tbody tr').each(function(idx) {
            existingRows.push({
                numero_cuota: idx + 1,
                descripcion: $(this).find('.inst-desc').val(),
                porcentaje: $(this).find('.inst-pct').val(),
                fecha_vencimiento: $(this).find('.inst-due-date').val(),
                monto: $(this).find('.inst-amount').val(),
            });
        });

        renderInstallments(existingRows);
    });

    // Event date change updates due date of the final installment if untouched
    $('input[name="fecha_evento"]').on('change', function() {
        let newEventDate = $(this).val();
        if (!isUserEditingInstallmentsManually && newEventDate) {
            let lastRow = $('#table-contract-installments tbody tr:last');
            if (lastRow.length) {
                lastRow.find('.inst-due-date').val(newEventDate);
            }
        }
    });

    // Initialize installments (from window.existingInstallments if editing, or default 50_50)
    if (window.existingInstallments && window.existingInstallments.length > 0) {
        isUserEditingInstallmentsManually = true;
        renderInstallments(window.existingInstallments);
    } else {
        renderInstallments(buildDefaultSchedule(getContractTotal(), '50_50'));
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

        // Check installment balance
        if ($('#table-contract-installments tbody tr').length > 0 && !checkInstallmentBalance()) {
            toastr.error('La suma de las cuotas programadas debe coincidir exactamente con el total del contrato.');
            $('html, body').animate({
                scrollTop: $("#table-contract-installments").offset().top - 120
            }, 400);
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
