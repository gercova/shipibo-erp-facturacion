<script>
    $(document).ready(function() {
        let table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            order: [[0, 'desc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            ajax: {
                url: "{{ route('contracts.get') }}",
                type: "GET"
            },
            autoWidth: false,
            columns: [
                { data: 'contract_number', name: 'contracts.contract_number', className: 'text-center fw-bold' },
                { data: 'fecha_evento', name: 'contracts.fecha_evento', className: 'text-center' },
                { data: 'dni_ruc', name: 'clients.nro_documento', className: 'text-center' },
                { data: 'cliente', name: 'clients.nombres' },
                { data: 'total', name: 'contracts.total', className: 'text-end fw-bold' },
                { data: 'estado_badge', name: 'contracts.estado', className: 'text-center', orderable: false, searchable: false },
                { data: 'acciones', name: 'acciones', className: 'text-center align-middle', orderable: false, searchable: false }
            ]
        });

        // Column filters
        let delayTimer;
        $('#filter-contract').on('keyup', function() {
            clearTimeout(delayTimer);
            let val = this.value;
            delayTimer = setTimeout(function() {
                table.column(0).search(val).draw();
            }, 400);
        });

        $('#filter-event-date').on('change', function() {
            table.column(1).search(this.value).draw();
        });

        $('#filter-document').on('keyup', function() {
            clearTimeout(delayTimer);
            let val = this.value;
            delayTimer = setTimeout(function() {
                table.column(2).search(val).draw();
            }, 400);
        });

        $('#filter-client').on('keyup', function() {
            clearTimeout(delayTimer);
            let val = this.value;
            delayTimer = setTimeout(function() {
                table.column(3).search(val).draw();
            }, 400);
        });

        $('#filter-total').on('keyup', function() {
            clearTimeout(delayTimer);
            let val = this.value;
            delayTimer = setTimeout(function() {
                table.column(4).search(val).draw();
            }, 400);
        });

        $('#filter-status').on('change', function() {
            table.column(5).search(this.value).draw();
        });

        // Print A4 action
        $(document).on('click', '.btn-print-contract', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            Swal.fire({
                title: 'Generando PDF',
                text: 'Por favor espere un momento...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('admin.print_contract_a4') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                dataType: "json",
                success: function(response) {
                    Swal.close();
                    if (!response.status) {
                        toastr.error(response.msg || 'No se pudo generar el documento.');
                        return;
                    }

                    let pdfUrl = "{{ asset('files/contracts') }}/" + response.pdf;

                    if (/Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)) {
                        window.open(pdfUrl, '_blank');
                    } else {
                        let iframe = document.createElement('iframe');
                        iframe.style.display = "none";
                        iframe.src = pdfUrl;
                        document.body.appendChild(iframe);
                        iframe.onload = function() {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        };
                    }
                },
                error: function() {
                    Swal.close();
                    toastr.error('Error al comunicarse con el servidor.');
                }
            });
        });

        // Detail Modal Action
        $(document).on('click', '.btn-detail-contract', function(e) {
            e.preventDefault();
            let id = $(this).data('id');

            $.ajax({
                url: "{{ route('admin.detail_contract') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                dataType: "json",
                success: function(r) {
                    if (!r.status) {
                        toastr.error(r.msg || 'No se pudo cargar el detalle.');
                        return;
                    }

                    let c = r.contract;
                    let client = r.client || {};
                    let items = r.items || [];
                    let clauses = r.clauses || [];

                    $('#modal-contract-number').text(c.contract_number);
                    $('#modal-contract-title').text(c.title);
                    $('#modal-client-name').text(client.nombres || '-');
                    $('#modal-client-doc').text((client.tipo_documento ? client.tipo_documento.descripcion + ': ' : 'Doc: ') + (client.nro_documento || '-'));
                    $('#modal-client-address').text(client.direccion || '-');
                    $('#modal-client-phone').text(client.telefono || '-');
                    $('#modal-client-email').text(client.email || '-');

                    $('#modal-provider-name').text(c.provider_name || '-');
                    $('#modal-provider-doc').text(c.provider_document || '-');

                    $('#modal-event-date').text(c.fecha_evento + (c.hora_evento ? ' ' + c.hora_evento : ''));
                    $('#modal-event-location').text(c.lugar_evento || 'No especificado');
                    $('#modal-issue-date').text(c.fecha_emision);
                    $('#modal-status-badge').html(r.status_label);

                    // Render items table
                    let itemsHtml = '';
                    let signo = r.signo || 'S/';
                    items.forEach((item, index) => {
                        itemsHtml += `<tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.descripcion}</td>
                            <td class="text-center">${parseFloat(item.cantidad).toFixed(2)}</td>
                            <td class="text-end">${signo} ${parseFloat(item.precio_unitario).toFixed(2)}</td>
                            <td class="text-end fw-bold">${signo} ${parseFloat(item.subtotal).toFixed(2)}</td>
                        </tr>`;
                    });
                    $('#modal-items-body').html(itemsHtml);

                    $('#modal-subtotal').text(signo + ' ' + parseFloat(c.subtotal).toFixed(2));
                    $('#modal-igv').text(signo + ' ' + parseFloat(c.igv).toFixed(2));
                    $('#modal-total').text(signo + ' ' + parseFloat(c.total).toFixed(2));

                    // Render installments table
                    let installments = r.installments || [];
                    let installmentsHtml = '';
                    installments.forEach(inst => {
                        let rowClass = inst.is_overdue ? 'table-danger' : (inst.is_paid ? '' : '');
                        let dueHtml = inst.fecha_vencimiento;

                        if (inst.is_overdue) {
                            dueHtml = `<span class="text-danger fw-bold"><i class="ri-alarm-warning-fill me-1"></i>${inst.fecha_vencimiento}</span>`;
                        } else if (inst.is_due_today) {
                            dueHtml = `<span class="text-warning-emphasis fw-bold"><i class="ri-time-fill me-1"></i>${inst.fecha_vencimiento} (Hoy)</span>`;
                        }

                        let actionHtml = '<div class="d-flex flex-column gap-1 align-items-center">';
                        if (inst.is_paid) {
                            actionHtml += `<span class="badge bg-success-subtle text-success py-1 px-2"><i class="ri-check-line me-1"></i>Pagado</span>`;
                        } else {
                            actionHtml += `<button type="button" class="btn btn-sm btn-outline-success py-0 px-2 btn-open-pay-modal" 
                                data-id="${inst.id}" 
                                data-contract-id="${c.id}" 
                                data-num="${inst.numero_cuota}" 
                                data-desc="${inst.descripcion}" 
                                data-amount="${parseFloat(inst.monto).toFixed(2)}" 
                                data-due="${inst.fecha_vencimiento}">
                                <i class="ri-money-dollar-circle-line me-1"></i>Pagar
                            </button>`;
                        }

                        if (inst.is_billed) {
                            actionHtml += `<span class="badge bg-primary-subtle text-primary py-1 px-2" title="Facturado el ${inst.facturado_at || ''}"><i class="ri-bill-line me-1"></i>${inst.factura_numero || 'Facturado'}</span>`;
                        } else {
                            actionHtml += `<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-open-invoice-modal"
                                data-id="${inst.id}"
                                data-contract-id="${c.id}"
                                data-num="${inst.numero_cuota}"
                                data-desc="${inst.descripcion}"
                                data-amount="${parseFloat(inst.monto).toFixed(2)}"
                                data-client-doc-type="${client ? (client.tipo_documento?.codigo || '') : ''}"
                                data-client-doc="${client ? (client.nro_documento || '') : ''}">
                                <i class="ri-file-text-line me-1"></i>Facturar
                            </button>`;
                        }
                        actionHtml += '</div>';

                        installmentsHtml += `<tr class="${rowClass}">
                            <td class="text-center fw-bold">${inst.numero_cuota}</td>
                            <td>
                                <strong>${inst.descripcion}</strong>
                                ${inst.metodo_pago ? `<br><small class="text-muted">Método: ${inst.metodo_pago}</small>` : ''}
                            </td>
                            <td class="text-center">${parseFloat(inst.porcentaje).toFixed(1)}%</td>
                            <td class="text-center">${dueHtml}</td>
                            <td class="text-end fw-bold">${signo} ${parseFloat(inst.monto).toFixed(2)}</td>
                            <td class="text-center">${inst.badge}</td>
                            <td class="text-center">${actionHtml}</td>
                        </tr>`;
                    });

                    if (installments.length === 0) {
                        installmentsHtml = `<tr><td colspan="7" class="text-center text-muted py-2">Sin cronograma registrado.</td></tr>`;
                    }
                    $('#modal-installments-body').html(installmentsHtml);

                    $('#modal-installments-paid').text(signo + ' ' + parseFloat(r.paid_amount || 0).toFixed(2));
                    $('#modal-installments-pending').text(signo + ' ' + parseFloat(r.pending_amount || 0).toFixed(2));

                    if (r.has_overdue) {
                        $('#modal-overdue-alert-banner').show();
                    } else {
                        $('#modal-overdue-alert-banner').hide();
                    }

                    // Render Billings List
                    let billingsHtml = '';
                    let parentBillingOptions = '';
                    if (r.billings && r.billings.length > 0) {
                        billingsHtml = '<div class="list-group list-group-flush">';
                        r.billings.forEach(b => {
                            billingsHtml += `<div class="list-group-item d-flex justify-content-between align-items-center py-2 px-1">
                                <div>
                                    <strong class="text-dark">${b.tipo}: ${b.numero}</strong>
                                    <div class="text-muted small">${b.fecha_emision} | Estado: <span class="badge bg-success-subtle text-success">${b.estado_cpe || 'REGISTRADO'}</span></div>
                                </div>
                                <span class="badge bg-primary fs-7">${signo} ${parseFloat(b.total).toFixed(2)}</span>
                            </div>`;
                            parentBillingOptions += `<option value="${b.id}">${b.tipo} ${b.numero} - Total: ${signo} ${parseFloat(b.total).toFixed(2)}</option>`;
                        });
                        billingsHtml += '</div>';
                        $('#btn-open-debit-note-modal').show().data('contract-id', c.id);
                    } else {
                        billingsHtml = '<span class="text-muted small">Sin comprobantes electrónicos emitidos aún.</span>';
                        $('#btn-open-debit-note-modal').hide();
                    }
                    $('#modal-billings-list').html(billingsHtml);
                    $('#debit-note-parent-id').html(parentBillingOptions);

                    // Render GRE List
                    let greHtml = '';
                    if (r.shipment_guides && r.shipment_guides.length > 0) {
                        greHtml = '<div class="list-group list-group-flush">';
                        r.shipment_guides.forEach(g => {
                            greHtml += `<div class="list-group-item d-flex justify-content-between align-items-center py-2 px-1">
                                <div>
                                    <strong class="text-dark">GRE: ${g.numero}</strong>
                                    <div class="text-muted small">${g.fecha_emision} | ${g.estado_cpe || 'REGISTRADO'}</div>
                                </div>
                                <a href="{{ asset('files/shipment_guides/a4') }}/${g.numero}.pdf" target="_blank" class="btn btn-xs btn-outline-info py-0 px-2">PDF</a>
                            </div>`;
                        });
                        greHtml += '</div>';
                    } else {
                        greHtml = '<span class="text-muted small">Sin guías de remisión generadas.</span>';
                    }
                    $('#modal-gre-list').html(greHtml);

                    // 20% Guarantee numerical display
                    let guaranteeVal = (parseFloat(c.total) * 0.20).toFixed(2);
                    $('#modal-guarantee-total').text(signo + ' ' + guaranteeVal);

                    // Render clauses
                    let clausesHtml = '';
                    if (clauses.length > 0) {
                        clauses.forEach(clause => {
                            clausesHtml += `<div class="mb-3 pb-2 border-bottom">
                                <h6 class="fw-bold text-primary mb-1">${clause.titulo}</h6>
                                <p class="small text-muted mb-0" style="white-space: pre-wrap;">${clause.contenido}</p>
                            </div>`;
                        });
                    } else {
                        clausesHtml = '<p class="text-muted small">Sin cláusulas específicas adicionales.</p>';
                    }
                    $('#modal-clauses-container').html(clausesHtml);

                    // Render signature
                    if (c.firma_cliente) {
                        $('#modal-signature-img').attr('src', "{{ asset('') }}" + c.firma_cliente);
                        $('#modal-signature-wrapper').show();
                    } else {
                        $('#modal-signature-wrapper').hide();
                    }

                    // Download and checklist buttons in modal
                    $('#modal-btn-download-pdf').attr('href', "{{ url('contracts') }}/" + c.id + "/download");
                    $('#modal-btn-checklist').attr('href', "{{ url('event-checklists/contract') }}/" + c.id + "/generate");
                    $('#modal-btn-print-pdf').data('id', c.id);

                    $('#modalDetailContract').modal('show');
                },
                error: function() {
                    toastr.error('Error al obtener los detalles del contrato.');
                }
            });
        });

        // Delete action
        $(document).on('click', '.btn-delete-contract', function(e) {
            e.preventDefault();
            let id = $(this).data('id');

            Swal.fire({
                title: '¿Está seguro de eliminar este contrato?',
                text: 'Esta acción eliminará el contrato, sus cláusulas, ítems y archivos asociados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('admin.delete_contract') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status) {
                                toastr.success(response.msg);
                                table.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'No se pudo eliminar el contrato.');
                            }
                        },
                        error: function() {
                            toastr.error('Ocurrió un error en el servidor al eliminar.');
                        }
                    });
                }
            });
        });

        // Open Pay Installment Modal
        $(document).on('click', '.btn-open-pay-modal', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let contractId = $(this).data('contract-id');
            let num = $(this).data('num');
            let desc = $(this).data('desc');
            let amount = $(this).data('amount');
            let due = $(this).data('due');

            $('#pay-contract-id').val(contractId);
            $('#pay-installment-id').val(id);
            $('#pay-installment-desc').text(`Cuota #${num}: ${desc}`);
            $('#pay-installment-due').text(due);
            $('#pay-installment-amount').text(`S/ ${amount}`);
            $('#pay-installment-date').val(new Date().toISOString().slice(0, 10));
            $('#pay-installment-reference').val('');
            $('#pay-installment-notes').val('');

            $('#modalPayContractInstallment').modal('show');
        });

        // Submit Pay Installment
        $('#form-pay-installment').on('submit', function(e) {
            e.preventDefault();
            let installmentId = $('#pay-installment-id').val();
            let contractId = $('#pay-contract-id').val();
            let btn = $('#btn-submit-pay-installment');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Registrando...');

            let postUrl = "{{ url('contracts/installments') }}/" + installmentId + "/pay";

            $.ajax({
                url: postUrl,
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Confirmar Pago');
                    if (response.status) {
                        $('#modalPayContractInstallment').modal('hide');
                        toastr.success(response.msg);
                        table.ajax.reload(null, false);
                        // Refresh contract detail modal
                        if (contractId) {
                            $(`.btn-detail-contract[data-id="${contractId}"]`).first().trigger('click');
                        }
                    } else {
                        toastr.error(response.msg || 'No se pudo registrar el pago.');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Confirmar Pago');
                    let msg = xhr.responseJSON?.msg || 'Error al procesar el pago.';
                    toastr.error(msg);
                }
            });
        });

        // Open Invoice Modal
        $(document).on('click', '.btn-open-invoice-modal', function() {
            let id = $(this).data('id');
            let contractId = $(this).data('contract-id');
            let num = $(this).data('num');
            let desc = $(this).data('desc');
            let amount = $(this).data('amount');
            let clientDocType = String($(this).data('client-doc-type') || '');
            let clientDoc = String($(this).data('client-doc') || '');

            $('#invoice-contract-id').val(contractId);
            $('#invoice-installment-id').val(id);
            $('#invoice-installment-num').text(`#${num}`);
            $('#invoice-installment-desc').text(desc);
            $('#invoice-installment-amount').text(amount);

            // Auto-select Factura (01) if client has 11-digit RUC (type 6), else Boleta (03)
            let isRuc = (clientDocType === '6' && clientDoc.length === 11);
            if (isRuc) {
                $('#invoice-doc-type').val('01');
                $('#invoice-doc-type-help').html('<span class="text-success"><i class="ri-checkbox-circle-line me-1"></i>Cliente con RUC válido (11 dígitos). Procede emisión de Factura.</span>');
            } else {
                $('#invoice-doc-type').val('03');
                $('#invoice-doc-type-help').html('<span class="text-muted"><i class="ri-information-line me-1"></i>Cliente sin RUC. Se emitirá Boleta de Venta Electrónica.</span>');
            }

            // Show breakages toggle if cuota > 1
            if (parseInt(num) >= 2) {
                $('#invoice-breakages-wrapper').show();
            } else {
                $('#invoice-breakages-wrapper').hide();
                $('#invoice-include-breakages').prop('checked', false);
            }

            $('#modalInvoiceInstallment').modal('show');
        });

        // Submit Invoice Form
        $('#form-invoice-installment').on('submit', function(e) {
            e.preventDefault();
            let contractId = $('#invoice-contract-id').val();
            let installmentId = $('#invoice-installment-id').val();
            let btn = $('#btn-submit-invoice');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Emitiendo a SUNAT...');

            let postUrl = "{{ url('contracts') }}/" + contractId + "/installments/" + installmentId + "/invoice";

            $.ajax({
                url: postUrl,
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Emitir Comprobante SUNAT');
                    if (response.status) {
                        $('#modalInvoiceInstallment').modal('hide');
                        Swal.fire({
                            title: '¡Comprobante Emitido!',
                            html: `<strong>${response.msg}</strong><br><small class="text-muted">Total: S/ ${parseFloat(response.total).toFixed(2)}</small>`,
                            icon: 'success'
                        });
                        table.ajax.reload(null, false);
                        if (contractId) {
                            $(`.btn-detail-contract[data-id="${contractId}"]`).first().trigger('click');
                        }
                    } else {
                        toastr.error(response.msg || 'No se pudo emitir el comprobante.');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Emitir Comprobante SUNAT');
                    let msg = xhr.responseJSON?.msg || 'Error al emitir el comprobante.';
                    Swal.fire('Validación Tributaria', msg, 'warning');
                }
            });
        });

        // Open Debit Note Modal
        $('#btn-open-debit-note-modal').on('click', function() {
            let contractId = $(this).data('contract-id');
            $('#debit-note-contract-id').val(contractId);
            $('#modalDebitNote').modal('show');
        });

        // Submit Debit Note Form
        $('#form-debit-note').on('submit', function(e) {
            e.preventDefault();
            let contractId = $('#debit-note-contract-id').val();
            let btn = $('#btn-submit-debit-note');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Emitiendo Nota de Débito...');

            let postUrl = "{{ url('contracts') }}/" + contractId + "/issue-debit-note";

            $.ajax({
                url: postUrl,
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Emitir Nota de Débito');
                    if (response.status) {
                        $('#modalDebitNote').modal('hide');
                        Swal.fire({
                            title: '¡Nota de Débito Emitida!',
                            html: `<strong>${response.msg}</strong><br><small class="text-muted">Total Imputado: S/ ${parseFloat(response.total).toFixed(2)}</small>`,
                            icon: 'success'
                        });
                        table.ajax.reload(null, false);
                        if (contractId) {
                            $(`.btn-detail-contract[data-id="${contractId}"]`).first().trigger('click');
                        }
                    } else {
                        toastr.error(response.msg || 'No se pudo emitir la nota de débito.');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Emitir Nota de Débito');
                    let msg = xhr.responseJSON?.msg || 'Error al emitir la nota de débito.';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // ==========================================
        // Inventory Reconciliation Workflow
        // ==========================================
        let reconcileSigno = 'S/';

        function recalculateReconciliationTotals() {
            let totalSent = 0;
            let totalOk = 0;
            let totalIncidents = 0;
            let totalPenalties = 0;

            $('.reconcile-item-row').each(function() {
                let sent = parseFloat($(this).find('.item-qty-enviada').val()) || 0;
                let ok = parseFloat($(this).find('.item-qty-ok').val()) || 0;
                let quebrada = parseFloat($(this).find('.item-qty-quebrada').val()) || 0;
                let faltante = parseFloat($(this).find('.item-qty-faltante').val()) || 0;
                let danada = parseFloat($(this).find('.item-qty-danada').val()) || 0;
                let unitPenalty = parseFloat($(this).find('.item-unit-penalty').val()) || 0;

                let rowIncidents = quebrada + faltante + danada;
                let rowPenalty = rowIncidents * unitPenalty;

                totalSent += sent;
                totalOk += ok;
                totalIncidents += rowIncidents;
                totalPenalties += rowPenalty;

                let penaltyCell = $(this).find('.item-subtotal-penalty');
                penaltyCell.text(reconcileSigno + ' ' + rowPenalty.toFixed(2));
                if (rowPenalty > 0) {
                    penaltyCell.removeClass('text-muted').addClass('text-danger fs-6');
                } else {
                    penaltyCell.removeClass('text-danger fs-6').addClass('text-muted');
                }
            });

            $('#reconcile-kpi-sent').text(totalSent.toFixed(2));
            $('#reconcile-kpi-ok').text(totalOk.toFixed(2));
            $('#reconcile-kpi-incidents').text(totalIncidents.toFixed(2));
            $('#reconcile-kpi-total-penalty').text(reconcileSigno + ' ' + totalPenalties.toFixed(2));
        }

        // Open Inventory Reconciliation Modal
        $(document).on('click', '.btn-reconcile-inventory', function(e) {
            e.preventDefault();
            let contractId = $(this).data('id');

            Swal.fire({
                title: 'Cargando Inventario',
                text: 'Obteniendo ítems llevados al evento...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ url('contracts') }}/" + contractId + "/reconciliation",
                type: "GET",
                dataType: "json",
                success: function(r) {
                    Swal.close();
                    if (!r.status) {
                        toastr.error(r.msg || 'No se pudo cargar la información de reconciliación.');
                        return;
                    }

                    reconcileSigno = r.signo || 'S/';
                    let c = r.contract || {};
                    let cl = r.client || {};
                    let chk = r.checklist || {};
                    let items = r.items || [];

                    $('#reconcile-contract-id').val(c.id);
                    $('#reconcile-contract-number').text(c.contract_number);
                    $('#reconcile-checklist-badge').text(chk.codigo);

                    if (chk.is_closed) {
                        $('#reconcile-status-badge').html('<i class="ri-lock-2-line me-1"></i> Retorno Cerrado').removeClass('bg-info-subtle text-info border-info-subtle').addClass('bg-success text-white');
                        $('#reconcile-closed-info').show().html('<i class="ri-checkbox-circle-fill me-1"></i> Cerrado el ' + chk.retorno_cerrado_at);
                        $('#btn-submit-reconciliation').html('<i class="ri-restart-line me-1"></i> Actualizar Conciliación');
                    } else {
                        $('#reconcile-status-badge').html('<i class="ri-time-line me-1"></i> Pendiente de Cierre').removeClass('bg-success text-white').addClass('bg-info-subtle text-info border-info-subtle');
                        $('#reconcile-closed-info').hide();
                        $('#btn-submit-reconciliation').html('<i class="ri-check-double-line me-1"></i> Guardar Conciliación y Aplicar Penalidades');
                    }

                    $('#reconcile-client-name').text(cl.nombres);
                    $('#reconcile-client-doc').text(cl.documento ? 'Doc: ' + cl.documento : '');
                    $('#reconcile-event-info').text(c.fecha_evento + (c.hora_evento ? ' ' + c.hora_evento : ''));
                    $('#reconcile-event-location').text(c.lugar_evento);
                    $('#reconcile-checklist-code').text(chk.codigo);

                    $('#reconcile-btn-tablet').attr('href', "{{ url('event-checklists') }}/" + chk.id + "/tablet");
                    $('#reconcile-btn-report').attr('href', "{{ url('event-checklists') }}/" + chk.id + "/report");

                    // Render table rows
                    let tbodyHtml = '';
                    if (items.length === 0) {
                        tbodyHtml = '<tr><td colspan="9" class="text-center py-4 text-muted">No hay ítems registrados en el evento para este contrato.</td></tr>';
                    } else {
                        items.forEach((item, idx) => {
                            let penaltyClass = item.total_penalty > 0 ? 'text-danger fs-6' : 'text-muted';
                            tbodyHtml += `
                            <tr class="reconcile-item-row" data-item-id="${item.id}">
                                <td class="text-center fw-bold text-muted">${idx + 1}</td>
                                <td>
                                    <strong class="text-dark d-block item-name">${item.descripcion}</strong>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="badge bg-secondary-subtle text-dark border py-0 px-2" style="font-size: 0.72rem;">${item.categoria}</span>
                                        <span class="badge bg-light text-muted border py-0 px-2" style="font-size: 0.72rem;">${item.unidad}</span>
                                        ${item.is_rentable ? '<span class="badge bg-info-subtle text-info border py-0 px-2" style="font-size: 0.72rem;"><i class="ri-tools-line me-1"></i>Alquiler</span>' : ''}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark fs-7 px-2 py-1">${parseFloat(item.qty_enviada).toFixed(2)}</span>
                                    <input type="hidden" name="items[${item.id}][qty_enviada]" class="item-qty-enviada" value="${item.qty_enviada}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="${item.qty_enviada}" class="form-control form-control-sm text-center fw-bold text-success border-success item-qty-ok" name="items[${item.id}][qty_ok]" value="${parseFloat(item.qty_ok).toFixed(2)}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="${item.qty_enviada}" class="form-control form-control-sm text-center fw-bold text-danger border-danger item-qty-quebrada" name="items[${item.id}][qty_quebrada]" value="${parseFloat(item.qty_quebrada).toFixed(2)}" placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="${item.qty_enviada}" class="form-control form-control-sm text-center fw-bold text-warning-emphasis border-warning item-qty-faltante" name="items[${item.id}][qty_faltante]" value="${parseFloat(item.qty_faltante).toFixed(2)}" placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="${item.qty_enviada}" class="form-control form-control-sm text-center fw-bold text-secondary border-secondary item-qty-danada" name="items[${item.id}][qty_danada]" value="${parseFloat(item.qty_danada).toFixed(2)}" placeholder="0.00">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text py-0 px-1 text-muted" style="font-size: 0.72rem;">${reconcileSigno}</span>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end fw-bold item-unit-penalty" name="items[${item.id}][unit_penalty]" value="${parseFloat(item.unit_penalty).toFixed(2)}">
                                    </div>
                                </td>
                                <td class="text-end">
                                    <strong class="item-subtotal-penalty ${penaltyClass}">${reconcileSigno} ${parseFloat(item.total_penalty).toFixed(2)}</strong>
                                </td>
                            </tr>`;
                        });
                    }
                    $('#reconcile-items-tbody').html(tbodyHtml);

                    // Pending installments dropdown
                    let instOpts = '';
                    let installments = r.pending_installments || [];
                    if (installments.length > 0) {
                        installments.forEach((inst, index) => {
                            let selected = (index === installments.length - 1) ? 'selected' : '';
                            instOpts += `<option value="${inst.id}" ${selected}>Cuota #${inst.numero_cuota} - ${inst.descripcion} (Saldo: ${reconcileSigno} ${parseFloat(inst.monto).toFixed(2)})</option>`;
                        });
                        $('#reconcile-installment-id').html(instOpts).closest('div').show();
                    } else {
                        $('#reconcile-installment-id').html('<option value="">Sin cuotas pendientes (Se creará ajuste contable directo)</option>');
                    }

                    recalculateReconciliationTotals();
                    $('#reconcile-search-filter').val('');
                    $('#modalInventoryReconciliation').modal('show');
                },
                error: function() {
                    Swal.close();
                    toastr.error('Error al conectar con el servidor.');
                }
            });
        });

        // Realtime calculation on inputs
        $(document).on('input change', '.item-qty-quebrada, .item-qty-faltante, .item-qty-danada, .item-unit-penalty', function() {
            let row = $(this).closest('tr');
            let sent = parseFloat(row.find('.item-qty-enviada').val()) || 0;
            let quebrada = parseFloat(row.find('.item-qty-quebrada').val()) || 0;
            let faltante = parseFloat(row.find('.item-qty-faltante').val()) || 0;
            let danada = parseFloat(row.find('.item-qty-danada').val()) || 0;

            // Auto-balance OK quantity
            let incidents = quebrada + faltante + danada;
            let newOk = Math.max(0, sent - incidents);
            row.find('.item-qty-ok').val(newOk.toFixed(2));

            recalculateReconciliationTotals();
        });

        $(document).on('input change', '.item-qty-ok', function() {
            recalculateReconciliationTotals();
        });

        // Quick Bulk: Todo Conforme (OK)
        $('#btn-reconcile-all-ok').on('click', function() {
            $('.reconcile-item-row').each(function() {
                let sent = parseFloat($(this).find('.item-qty-enviada').val()) || 0;
                $(this).find('.item-qty-ok').val(sent.toFixed(2));
                $(this).find('.item-qty-quebrada').val('0.00');
                $(this).find('.item-qty-faltante').val('0.00');
                $(this).find('.item-qty-danada').val('0.00');
            });
            recalculateReconciliationTotals();
            toastr.info('Todos los ítems asignados como conformes (OK).');
        });

        // Quick Bulk: Limpiar Incidencias
        $('#btn-reconcile-clear-incidents').on('click', function() {
            $('.reconcile-item-row').each(function() {
                let sent = parseFloat($(this).find('.item-qty-enviada').val()) || 0;
                $(this).find('.item-qty-quebrada').val('0.00');
                $(this).find('.item-qty-faltante').val('0.00');
                $(this).find('.item-qty-danada').val('0.00');
                $(this).find('.item-qty-ok').val(sent.toFixed(2));
            });
            recalculateReconciliationTotals();
            toastr.info('Incidencias y penalidades restablecidas a cero.');
        });

        // Live Search Filter for Items
        $('#reconcile-search-filter').on('keyup', function() {
            let term = $(this).val().toLowerCase().trim();
            $('.reconcile-item-row').each(function() {
                let text = $(this).text().toLowerCase();
                if (text.indexOf(term) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Submit Inventory Reconciliation
        $('#form-inventory-reconciliation').on('submit', function(e) {
            e.preventDefault();
            let contractId = $('#reconcile-contract-id').val();
            let btn = $('#btn-submit-reconciliation');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Procesando...');

            $.ajax({
                url: "{{ url('contracts') }}/" + contractId + "/reconciliation",
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Guardar Conciliación y Aplicar Penalidades');
                    if (response.status) {
                        $('#modalInventoryReconciliation').modal('hide');
                        Swal.fire({
                            title: '¡Conciliación Procesada!',
                            html: `<strong>${response.msg}</strong>`,
                            icon: 'success'
                        });
                        table.ajax.reload(null, false);
                    } else {
                        toastr.error(response.msg || 'No se pudo procesar la conciliación.');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Guardar Conciliación y Aplicar Penalidades');
                    let msg = xhr.responseJSON?.msg || 'Error al procesar la conciliación de inventario.';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });
</script>
