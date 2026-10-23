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
    });
</script>
