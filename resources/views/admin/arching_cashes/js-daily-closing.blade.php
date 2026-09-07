<script>
$(document).ready(function() {
    let currentStatusFilter = 'all';
    const currencySign = "{{ $signo }}";

    // Formateador de moneda
    function formatMoney(amount) {
        return currencySign + ' ' + parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // 1. Inicialización de DataTable de Ventas del Día (Server-Side AJAX)
    const tableSales = $('#table-daily-sales').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('admin.daily_closing.sales_data') }}",
            type: "GET",
            data: function(d) {
                d.status_filter = currentStatusFilter;
            },
            error: function(xhr) {
                console.error("Error al cargar ventas del día:", xhr);
            }
        },
        columns: [
            { data: 'hora', name: 'hora', className: 'text-center align-middle', orderable: false, width: '70px' },
            { data: 'documento_info', name: 'documento', className: 'align-middle', orderable: false },
            { data: 'cliente_info', name: 'cliente', className: 'align-middle', orderable: false },
            { data: 'metodo_pago', name: 'metodo_pago', className: 'text-center align-middle', orderable: false },
            { data: 'status_badge', name: 'status_badge', className: 'text-center align-middle', orderable: false },
            { data: 'total', name: 'total', className: 'text-end align-middle', orderable: false, width: '120px' }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Cargando registros...',
            search: "_INPUT_",
            searchPlaceholder: "Buscar venta, cliente o doc...",
            lengthMenu: "Mostrar _MENU_",
            info: "Ventas _START_ al _END_ de _TOTAL_",
            infoEmpty: "Sin ventas para el filtro seleccionado",
            infoFiltered: "(filtrado de _MAX_ registros)",
            zeroRecords: "No se encontraron ventas con este criterio",
            paginate: {
                first: "«",
                previous: "‹",
                next: "›",
                last: "»"
            }
        },
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-2"lf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"ip>'
    });

    // 2. Filtros de píldoras de ventas (Sin recargar la página)
    $('.btn-filter-status').on('click', function() {
        $('.btn-filter-status').removeClass('active');
        $(this).addClass('active');
        currentStatusFilter = $(this).data('status');
        tableSales.ajax.reload();
    });

    // 3. Inicialización de DataTable de Gastos del Día (Server-Side AJAX)
    const tableExpenses = $('#table-daily-expenses').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        ajax: {
            url: "{{ route('admin.daily_closing.expenses_data') }}",
            type: "GET",
            error: function(xhr) {
                console.error("Error al cargar gastos del día:", xhr);
            }
        },
        columns: [
            { data: 'hora', name: 'hora', className: 'text-center align-middle', orderable: false, width: '70px' },
            { data: 'comprobante_info', name: 'tipo_comprobante', className: 'align-middle', orderable: false },
            { data: 'motivo', name: 'motivo', className: 'align-middle', orderable: false },
            { data: 'beneficiario', name: 'beneficiario', className: 'align-middle', orderable: false },
            { data: 'metodo_pago', name: 'metodo_pago', className: 'text-center align-middle', orderable: false },
            { data: 'monto', name: 'monto', className: 'text-end align-middle', orderable: false, width: '110px' },
            { data: 'acciones', name: 'acciones', className: 'text-center align-middle', orderable: false, width: '60px' }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-danger" role="status"></div> Cargando gastos...',
            search: "_INPUT_",
            searchPlaceholder: "Buscar gasto o motivo...",
            lengthMenu: "Mostrar _MENU_",
            info: "Gastos _START_ al _END_ de _TOTAL_",
            infoEmpty: "Sin gastos registrados hoy",
            infoFiltered: "(filtrado de _MAX_ registros)",
            zeroRecords: "No hay gastos registrados hoy",
            paginate: {
                first: "«",
                previous: "‹",
                next: "›",
                last: "»"
            }
        },
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-2"lf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"ip>'
    });

    // 4. Registrar Nuevo Gasto de Caja Menor (AJAX)
    $('#formRegisterExpense').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $('#btnSubmitExpense');

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Guardando...');

        $.ajax({
            url: "{{ route('admin.daily_closing.store_expense') }}",
            type: "POST",
            data: $form.serialize(),
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Registrar Gasto');
                if (response.status) {
                    $('#modalRegisterExpense').modal('hide');
                    $form[0].reset();

                    // Notificación Toast
                    Swal.fire({
                        icon: 'success',
                        title: '¡Gasto Registrado!',
                        text: response.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Recargar tabla de gastos y actualizar tarjetas KPI en vivo
                    tableExpenses.ajax.reload(null, false);
                    if (response.summary) {
                        updateKpiCards(response.summary);
                    }
                } else {
                    Swal.fire('Atención', response.msg || 'No se pudo registrar el gasto.', 'warning');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Registrar Gasto');
                let errorMsg = 'Error al registrar el gasto.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                    errorMsg = xhr.responseJSON.msg;
                }
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    });

    // 5. Anular Gasto (AJAX con confirmación)
    $(document).on('click', '.btn-delete-expense', function() {
        const expenseId = $(this).data('id');

        Swal.fire({
            title: '¿Anular este gasto?',
            text: 'El monto volverá a sumarse al balance de caja del día.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.daily_closing.delete_expense') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: expenseId
                    },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Anulado',
                                text: response.msg,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            tableExpenses.ajax.reload(null, false);
                            if (response.summary) {
                                updateKpiCards(response.summary);
                            }
                        } else {
                            Swal.fire('Atención', response.msg, 'warning');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo anular el gasto.', 'error');
                    }
                });
            }
        });
    });

    // 6. Cierre formal de caja (AJAX)
    $('#btnActionCloseCash').on('click', function() {
        Swal.fire({
            title: '¿Confirmar Cierre de Caja del Día?',
            text: 'Esta acción finalizará la jornada de caja activa guardando los balances registrados.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ri-lock-line me-1"></i> Sí, cerrar caja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const $btn = $('#btnActionCloseCash');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Procesando cierre...');

                $.ajax({
                    url: "{{ route('admin.daily_closing.close_cash') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Caja Cerrada!',
                                text: response.msg,
                                confirmButtonText: 'Imprimir Ticket de Cierre'
                            }).then(() => {
                                window.open("{{ route('admin.daily_closing.print_ticket') }}", '_blank');
                                window.location.reload();
                            });
                        } else {
                            $btn.prop('disabled', false).html('<i class="ri-lock-line me-1"></i> Realizar Cierre de Caja');
                            Swal.fire('Atención', response.msg, 'warning');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="ri-lock-line me-1"></i> Realizar Cierre de Caja');
                        let msg = xhr.responseJSON?.msg || 'Error al procesar el cierre de caja.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });

    // 7. Botón Imprimir Ticket de Cierre
    $('.btn-print-closing-ticket').on('click', function() {
        window.open("{{ route('admin.daily_closing.print_ticket') }}", '_blank');
    });

    // 8. Botón Refrescar
    $('#btnRefreshDailyClosing').on('click', function() {
        tableSales.ajax.reload();
        tableExpenses.ajax.reload();
        window.location.reload();
    });

    // 9. Actualización dinámica en vivo de tarjetas KPI
    function updateKpiCards(summary) {
        if (!summary) return;

        $('#kpi_sales_paid_total').text(formatMoney(summary.sales_paid_total));
        $('#kpi_sales_paid_count').text(summary.sales_paid_count + ' ventas');

        $('#kpi_sales_pending_total').text(formatMoney(summary.sales_pending_total));
        $('#kpi_sales_pending_count').text(summary.sales_pending_count + ' ventas');

        $('#kpi_sales_returned_total').text('-' + formatMoney(summary.sales_returned_total));
        $('#kpi_sales_returned_count').text(summary.sales_returned_count + ' devoluciones');

        $('#kpi_sales_cancelled_total').text(formatMoney(summary.sales_cancelled_total));
        $('#kpi_sales_cancelled_count').text(summary.sales_cancelled_count + ' anulaciones');

        $('#kpi_expenses_total').text('-' + formatMoney(summary.expenses_total));
        $('#kpi_expenses_count').text(summary.expenses_count + ' egresos');

        $('#kpi_expected_cash').text(formatMoney(summary.expected_cash_in_box));
        $('#kpi_net_flow').text(formatMoney(summary.net_day_flow));

        // Actualizar badges en píldoras
        $('#badge-pill-all').text(summary.sales_paid_count + summary.sales_pending_count + summary.sales_returned_count + summary.sales_cancelled_count);
        $('#badge-pill-paid').text(summary.sales_paid_count);
        $('#badge-pill-pending').text(summary.sales_pending_count);
        $('#badge-pill-returned').text(summary.sales_returned_count);
        $('#badge-pill-cancelled').text(summary.sales_cancelled_count);

        // Actualizar valores en tab de arqueo
        $('#close_expenses_cash').text('-' + formatMoney(summary.expenses_cash_total));
        $('#close_final_cash').text(formatMoney(summary.expected_cash_in_box));
    }
});
</script>
