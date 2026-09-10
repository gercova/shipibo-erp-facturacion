@extends('admin.layout')

@section('styles')
    <style>
        .ui-autocomplete {
            z-index: 1100 !important;
            background: #fff;
            border: 1px solid rgba(33, 40, 50, 0.14);
            border-radius: 12px;
            box-shadow: 0 22px 50px rgba(18, 38, 63, 0.18);
            overflow: hidden;
            padding: 6px;
        }

        .autocomplete-wrapper {
            position: relative;
            width: 100%;
        }

        .input-barcode {
            width: 100% !important;
        }

        #modalConfirmSale .modal-dialog {
            max-width: 940px;
        }

        #modalConfirmSale .modal-content {
            border: 1px solid rgba(33, 40, 50, 0.10);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.20);
        }

        #modalConfirmSale .modal-header {
            background: linear-gradient(135deg, rgba(0, 172, 105, 1), rgba(0, 140, 86, 1));
            border-bottom: 0;
            padding: 1rem 1.15rem;
        }

        #modalConfirmSale .modal-body {
            padding: .95rem 1rem 1rem;
            background: linear-gradient(180deg, #fafcff 0%, #ffffff 100%);
        }

        #modalConfirmSale .modal-footer {
            padding: .9rem 1rem 1rem;
            border-top: 1px solid rgba(33, 40, 50, 0.08);
            background: rgba(255, 255, 255, 0.96);
            position: sticky;
            bottom: 0;
            z-index: 4;
            box-shadow: 0 -10px 24px rgba(15, 23, 42, .05);
        }

        .checkout-stack {
            display: grid;
            gap: .75rem;
        }

        .checkout-section {
            border: 1px solid rgba(33, 40, 50, 0.08);
            border-radius: 18px;
            padding: .9rem;
            background: #fff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .03);
        }

        .checkout-section-title {
            font-size: .74rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: .7rem;
        }

        .checkout-compact-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .65rem;
        }

        .checkout-choice-grid {
            display: grid;
            gap: .55rem;
        }

        .checkout-choice-grid.docs {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .checkout-choice-grid.payment {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .checkout-choice {
            border: 1.5px solid rgba(33, 40, 50, 0.10);
            border-radius: 14px;
            min-height: 54px;
            padding: .78rem .85rem;
            background: #fff;
            transition: all .15s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .checkout-choice.is-active {
            border-color: rgba(0, 97, 242, .28);
            background: rgba(0, 97, 242, .06);
            box-shadow: 0 10px 24px rgba(0, 97, 242, .08);
        }

        .checkout-choice-label {
            font-weight: 700;
            font-size: .92rem;
            color: #1f2937;
        }

        .checkout-summary {
            border: 1px solid rgba(33, 40, 50, 0.08);
            border-radius: 18px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .10), transparent 26%),
                linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            padding: 1rem;
            position: sticky;
            top: 0;
            box-shadow: 0 20px 40px rgba(15, 23, 42, .06);
        }

        .checkout-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .5rem;
            color: #334155;
            background: rgba(255, 255, 255, .82);
            border: 1px solid rgba(148, 163, 184, .12);
            border-radius: 13px;
            padding: .58rem .72rem;
        }

        .checkout-summary-row.total {
            margin-top: .85rem;
            padding: .8rem .8rem;
            border: 0;
            background: linear-gradient(135deg, rgba(0, 172, 105, .08), rgba(0, 140, 86, .14));
            font-size: 1.15rem;
            font-weight: 800;
        }

        .checkout-resume-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .36rem .6rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, .05);
            font-size: .73rem;
            color: #475569;
        }

        .payment-amount,
        .installment-amount {
            text-align: right;
        }

        .helper-note {
            font-size: .79rem;
        }

        .client-rule-badge {
            display: none;
        }

        .checkout-inline-action {
            width: 38px;
            min-width: 38px;
            height: 38px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .checkout-select-wrap .select2-container {
            width: 100% !important;
        }

        .checkout-select-wrap .select2-selection,
        .checkout-input,
        .checkout-section .form-select,
        .checkout-section .form-control {
            min-height: 44px;
            border-radius: 12px !important;
        }

        .checkout-input-group .input-group-text {
            border-radius: 12px 0 0 12px !important;
            min-width: 48px;
            justify-content: center;
            padding-inline: .65rem;
            border-right: 0;
            background: #fff;
        }

        .checkout-input-group .form-control {
            border-left: 0;
            padding-inline: .8rem;
            text-align: right;
            box-shadow: none !important;
        }

        .checkout-input-group .form-control:focus,
        .checkout-input-group .input-group-text {
            border-color: #cbd5e1;
        }

        .credit-box,
        .cash-box {
            display: none;
        }

        .credit-box.is-active,
        .cash-box.is-active {
            display: block;
        }

        .checkout-scroll-panel {
            max-height: 210px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: .2rem;
            scrollbar-width: thin;
        }

        .checkout-scroll-panel::-webkit-scrollbar {
            width: 6px;
        }

        .checkout-scroll-panel::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, .55);
            border-radius: 999px;
        }

        .credit-guide {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .68rem .8rem;
            border: 1px dashed rgba(37, 99, 235, .25);
            border-radius: 12px;
            background: rgba(37, 99, 235, .04);
            color: #3b4a67;
            font-size: .78rem;
            margin-bottom: .75rem;
        }

        .credit-guide strong {
            color: #1f2937;
        }

        .payment-row-grid,
        .installment-row {
            display: grid;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .payment-row-grid {
            grid-template-columns: minmax(0, 1.05fr) minmax(110px, .85fr) 52px;
            margin-bottom: 10px;
        }

        .installment-row {
            grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr) 52px;
            margin-bottom: 10px;
        }

        .payment-row-grid > div,
        .installment-row > div {
            min-width: 0;
        }

        .payment-row-grid .form-select,
        .payment-row-grid .form-control,
        .installment-row .form-control {
            padding-inline: .75rem;
        }

        .payment-row-grid .remove-payment,
        .installment-row .remove-installment {
            width: 100%;
            min-width: 0;
            border-radius: 12px;
            padding-inline: 0;
        }

        .modal-footer .btn {
            min-width: 146px;
            border-radius: 14px;
            min-height: 50px;
            font-weight: 700;
        }

        @media (max-width: 992px) {
            #modalConfirmSale .modal-dialog {
                max-width: calc(100vw - 1rem);
                margin: .5rem;
            }

            .checkout-choice-grid.docs,
            .checkout-choice-grid.payment {
                grid-template-columns: 1fr;
            }

            .checkout-summary {
                position: static;
            }

            .payment-row-grid,
            .installment-row {
                grid-template-columns: 1fr;
                row-gap: .55rem;
            }
        }

        /* Select2 inside #modalCustomItem */
        #modalCustomItem .select2-container {
            width: 100% !important;
        }

        #modalCustomItem .select2-selection--single {
            height: 42px !important;
            border-radius: 12px !important;
            border: 1px solid #ced4da;
            display: flex;
            align-items: center;
        }

        #modalCustomItem .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: .75rem;
            font-size: .9rem;
            color: #212529;
        }

        #modalCustomItem .select2-selection__arrow {
            height: 42px !important;
            right: 6px;
        }

        #modalCustomItem .select2-dropdown {
            border-radius: 12px;
            border: 1px solid rgba(33,40,50,.14);
            box-shadow: 0 12px 32px rgba(0,0,0,.12);
            overflow: hidden;
        }

        #modalCustomItem .select2-search--dropdown .select2-search__field {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: .35rem .6rem;
            font-size: .88rem;
        }

        #modalCustomItem .select2-results__option--highlighted {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }
    </style>
@endsection

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="shopping-cart"></i></div>
                        Punto de Venta
                    </h1>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card custom-card pro-card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="fw-bold text-dark">Carrito</div>
                        <span class="badge bg-primary-soft text-primary">POS</span>
                    </div>
                </div>
                <div class="card-body">
                    <input type="hidden" name="idalmacenuser" value="{{ Auth::user()['idalmacen'] }}">
                    <div class="autocomplete-wrapper">
                        <input type="text" class="form-control input-barcode" id="search-product" placeholder="Escanea un codigo o busca por nombre">
                    </div>
                    <div class="mt-2 d-flex justify-content-end">
                        <button type="button" class="btn btn-sm" id="btn-open-custom-item"
                            style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border: none; border-radius: 10px; padding: 0.38rem 0.85rem; font-weight: 700; font-size: 0.82rem; box-shadow: 0 4px 14px rgba(245,158,11,.28); transition: all .15s ease;">
                            <i class="ri-add-circle-line me-1"></i> Ítem personalizado
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mt-3">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center" width="22%">U. Med.</th>
                                    <th class="text-center" width="14%">Precio</th>
                                    <th class="text-center" width="18%">Cantidad</th>
                                    <th class="text-center" width="14%">Subtotal</th>
                                    <th class="text-center" width="8%">Accion</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_pos"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card custom-card pro-card">
                <div class="card-header">
                    <div class="fw-bold text-dark">Totales</div>
                </div>
                <div class="card-body">
                    <div id="wrapper-totals"></div>
                    <button class="btn btn-success w-100 mt-3" id="process-sale">
                        Procesar venta
                        <i class="ri-arrow-right-line ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalConfirmSale" tabindex="-1" aria-labelledby="modalConfirmSaleLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title text-white" id="modalConfirmSaleLabel">
                        <i class="ri-shopping-cart-fill"></i> Confirmar venta
                    </h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="checkout-stack">
                                <div class="checkout-section">
                                    <div class="checkout-section-title">Comprobante</div>
                                    <div class="checkout-choice-grid docs">
                                        <button type="button" class="checkout-choice doc-choice" data-code="03">
                                            <div class="checkout-choice-label">Boleta</div>
                                        </button>
                                        <button type="button" class="checkout-choice doc-choice" data-code="01">
                                            <div class="checkout-choice-label">Factura</div>
                                        </button>
                                        <button type="button" class="checkout-choice doc-choice" data-code="02">
                                            <div class="checkout-choice-label">Nota de venta</div>
                                        </button>
                                    </div>
                                    <select id="select-document-type" class="form-select d-none"></select>
                                    <div id="document-type-helper" class="helper-note text-muted mt-2"></div>
                                </div>

                                <div class="checkout-section">
                                    <div class="checkout-compact-head">
                                        <div>
                                            <div class="checkout-section-title mb-1">Cliente</div>
                                            <div id="client-rule-message" class="client-rule-badge"></div>
                                        </div>
                                        <button type="button" class="btn btn-info checkout-inline-action btn-create-client-pos" title="Nuevo cliente">
                                            <i class="ri-user-add-line"></i>
                                        </button>
                                    </div>
                                    <div class="checkout-select-wrap">
                                        <select id="select-client" class="form-select" name="dni_ruc"></select>
                                    </div>
                                </div>

                                <div class="checkout-section">
                                    <div class="checkout-section-title">Condicion de pago</div>
                                    <div class="checkout-choice-grid payment">
                                        <button type="button" class="checkout-choice payment-condition-choice" data-condition="contado">
                                            <div class="checkout-choice-label">Contado</div>
                                        </button>
                                        <button type="button" class="checkout-choice payment-condition-choice" data-condition="credito">
                                            <div class="checkout-choice-label">Credito</div>
                                        </button>
                                    </div>
                                </div>

                                <div class="checkout-section">
                                    <div class="checkout-section-title">Descuento global</div>
                                    <div class="input-group checkout-input-group">
                                        <span class="input-group-text">{{ $signo ?? 'S/' }}</span>
                                        <input type="number" class="form-control checkout-input" id="global-discount" min="0" step="0.01" value="0.00" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="checkout-section cash-box is-active" id="cash-payment-box">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="checkout-section-title mb-0">Metodos de pago</div>
                                        <button class="btn btn-outline-primary btn-sm" id="add-payment-method">
                                            <i class="ri-add-line"></i> Agregar pago
                                        </button>
                                    </div>
                                    <div id="payment-methods" class="mt-3">
                                        <div class="input-group mb-2 payment-row">
                                            <select class="form-select payment-method">
                                                <option value="">- Seleccione -</option>
                                            </select>
                                            <input type="number" class="form-control payment-amount" placeholder="Monto" min="0" step="0.01">
                                            <button class="btn btn-outline-danger remove-payment d-none" type="button">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="checkout-section credit-box" id="credit-payment-box">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="checkout-section-title mb-0">Cuotas del credito</div>
                                        <button class="btn btn-outline-primary btn-sm" id="add-installment">
                                            <i class="ri-add-line"></i> Agregar cuota
                                        </button>
                                    </div>
                                    <div class="credit-guide">
                                        <span>Define fecha y monto por cuota.</span>
                                        <strong>Desliza para ver todo</strong>
                                    </div>
                                    <div id="installment-methods" class="checkout-scroll-panel"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="checkout-summary">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="checkout-section-title mb-0">Resumen final</div>
                                    <span class="checkout-resume-chip">Cierre rapido</span>
                                </div>

                                <div class="checkout-summary-row">
                                    <span>Subtotal</span>
                                    <strong id="summary-subtotal"></strong>
                                </div>
                                <div class="checkout-summary-row">
                                    <span>Descuento</span>
                                    <strong id="summary-discount"></strong>
                                </div>
                                <div class="checkout-summary-row">
                                    <span>Subtotal sin IGV</span>
                                    <strong id="summary-net-subtotal"></strong>
                                </div>
                                <div class="checkout-summary-row">
                                    <span>Impuesto</span>
                                    <strong id="summary-igv"></strong>
                                </div>
                                <div class="checkout-summary-row total">
                                    <span>Total</span>
                                    <strong id="summary-total"></strong>
                                </div>
                                <div class="checkout-summary-row">
                                    <span>Pagado / programado</span>
                                    <strong id="summary-paid"></strong>
                                </div>
                                <div class="checkout-summary-row">
                                    <span>Vuelto / pendiente</span>
                                    <strong id="summary-balance" class="text-danger">0.00</strong>
                                </div>
                                <div class="helper-note mt-3 text-muted" id="summary-status-text">
                                    Completa los datos del comprobante para continuar.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirm-sale">
                        <i class="ri-check-double-line"></i> Confirmar venta
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.clients.modal-register', ['typeDocuments' => $typeDocuments])

    {{-- Modal: Agregar ítem personalizado --}}
    <div class="modal fade" id="modalCustomItem" tabindex="-1" aria-labelledby="modalCustomItemLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: 1px solid rgba(33,40,50,.10); box-shadow: 0 30px 80px rgba(0,0,0,.18);">
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); border-bottom: 0; padding: 1rem 1.15rem;">
                    <h5 class="modal-title text-white fw-bold" id="modalCustomItemLabel" style="font-size:.95rem;">
                        <i class="ri-add-circle-line me-1"></i> Ítem personalizado
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" style="background: linear-gradient(180deg,#fafcff 0%,#fff 100%); padding: 1.2rem;">
                    <p class="text-muted mb-3" style="font-size:.82rem;">
                        Agrega un cargo que no está en el inventario (vaso roto, plato faltante, servicio adicional, etc.).
                    </p>

                    <div class="mb-3">
                        <label for="custom-item-descripcion" class="form-label fw-semibold" style="font-size:.82rem;">Descripción <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="custom-item-descripcion"
                            placeholder="Ej: Vaso roto, plato faltante..."
                            style="border-radius:12px; min-height:42px;">
                    </div>

                    <div class="row g-2">
                        <div class="col-4">
                            <label for="custom-item-unidad" class="form-label fw-semibold" style="font-size:.82rem;">U. Med.</label>
                            <select id="custom-item-unidad" class="form-select" style="border-radius:12px; min-height:42px; width:100%;"></select>
                        </div>
                        <div class="col-4">
                            <label for="custom-item-precio" class="form-label fw-semibold" style="font-size:.82rem;">Precio (c/IGV) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text-center" id="custom-item-precio"
                                placeholder="0.00" min="0.01" step="0.01" value=""
                                style="border-radius:12px; min-height:42px;">
                        </div>
                        <div class="col-4">
                            <label for="custom-item-cantidad" class="form-label fw-semibold" style="font-size:.82rem;">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text-center" id="custom-item-cantidad"
                                placeholder="1" min="0.01" step="0.01" value="1"
                                style="border-radius:12px; min-height:42px;">
                        </div>
                    </div>

                    <div class="mt-3 p-2 rounded-3" id="custom-item-preview" style="background:rgba(245,158,11,.08); border:1px dashed rgba(245,158,11,.35); display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:.8rem;">Subtotal estimado:</span>
                            <span class="fw-bold text-warning" id="custom-item-subtotal" style="font-size:1rem;">S/ 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(33,40,50,.08); padding:.9rem 1rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:14px; min-width:100px;">Cancelar</button>
                    <button type="button" class="btn text-white fw-bold" id="btn-save-custom-item"
                        style="background: linear-gradient(135deg,#f59e0b,#d97706); border:none; border-radius:14px; min-width:130px; box-shadow:0 6px 18px rgba(245,158,11,.3);">
                        <i class="ri-check-line me-1"></i> Agregar al carrito
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('admin.pos.js-home')
    @include('admin.clients.js-register')
@endsection
