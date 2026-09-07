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
            columns: [
                { data: 'contract_number', name: 'contracts.contract_number', className: 'text-center fw-bold' },
                { data: 'fecha_evento', name: 'contracts.fecha_evento', className: 'text-center' },
                { data: 'dni_ruc', name: 'clients.nro_documento', className: 'text-center' },
                { data: 'cliente', name: 'clients.nombres' },
                { data: 'total', name: 'contracts.total', className: 'text-end fw-bold' },
                { data: 'estado_badge', name: 'contracts.estado', className: 'text-center', orderable: false, searchable: false },
                { data: 'acciones', name: 'acciones', className: 'text-center', orderable: false, searchable: false }
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

                    // Download button in modal
                    $('#modal-btn-download-pdf').attr('href', "{{ url('contracts') }}/" + c.id + "/download");
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
    });
</script>
