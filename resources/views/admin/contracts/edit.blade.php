@extends('admin.layout')

@section('styles')
    <style>
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .section-title i {
            margin-right: 0.5rem;
            color: var(--bs-primary);
        }

        .signature-container {
            border: 2px dashed #94a3b8;
            border-radius: 12px;
            background-color: #f8fafc;
            position: relative;
            cursor: crosshair;
            touch-action: none;
        }

        .signature-container canvas {
            width: 100%;
            height: 200px;
            display: block;
            border-radius: 10px;
        }

        .totals-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            border-radius: 12px;
        }

        .totals-card .text-muted {
            color: #94a3b8 !important;
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
                        <div class="page-header-icon"><i data-feather="edit-3"></i></div>
                        Editar Contrato: <span class="text-primary ms-2">{{ $contract->contract_number }}</span>
                    </h1>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.contracts') }}" class="btn btn-outline-secondary btn-sm mb-2">
                        <i class="ri-arrow-left-line align-middle"></i> Volver a Contratos
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl px-4 mt-2">
    <form id="form-contract" action="{{ route('admin.update_contract', $contract->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- 1. GENERAL INFORMATION & PARTIES -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="section-title">
                    <i class="ri-information-line"></i> 1. Información General y Partes Contratantes
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">N° Contrato</label>
                        <input type="text" name="contract_number" class="form-control form-control-sm fw-bold text-primary" value="{{ $contract->contract_number }}" readonly />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fecha de Emisión <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_emision" class="form-control form-control-sm" value="{{ $contract->fecha_emision->format('Y-m-d') }}" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Título del Contrato</label>
                        <input type="text" name="title" class="form-control form-control-sm" value="{{ $contract->title }}" required />
                    </div>

                    <!-- Client Selection -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold text-success mb-0">
                                    <i class="ri-user-3-line"></i> Cliente Contratante <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-xs btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalQuickCreateClient">
                                    <i class="ri-add-line"></i> Nuevo Cliente
                                </button>
                            </div>
                            <select name="idcliente" id="idcliente" class="form-select form-select-sm select2" required>
                                <option value="">-- Seleccionar Cliente --</option>
                                @foreach ($clients as $c)
                                    <option value="{{ $c->id }}" {{ $contract->idcliente == $c->id ? 'selected' : '' }}>
                                        {{ $c->nombres }} ({{ $c->tipoDocumento?->descripcion ?? 'Doc' }}: {{ $c->nro_documento }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Provider Details -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label small fw-bold text-primary mb-2">
                                <i class="ri-store-2-line"></i> Prestador del Servicio (Empresa / Proveedor)
                            </label>
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <input type="text" name="provider_name" class="form-control form-control-sm" placeholder="Razón Social / Nombre" value="{{ $contract->provider_name }}" />
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="provider_document" class="form-control form-control-sm" placeholder="RUC / DNI" value="{{ $contract->provider_document }}" />
                                </div>
                                <div class="col-12">
                                    <input type="text" name="provider_representative" class="form-control form-control-sm" placeholder="Representante Legal / Gerente" value="{{ $contract->provider_representative }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. EVENT DETAILS -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="section-title">
                    <i class="ri-calendar-event-line"></i> 2. Datos del Evento o Prestación del Servicio
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fecha del Evento <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_evento" class="form-control form-control-sm" value="{{ $contract->fecha_evento->format('Y-m-d') }}" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Hora del Evento</label>
                        <input type="time" name="hora_evento" class="form-control form-control-sm" value="{{ $contract->hora_evento ? \Carbon\Carbon::parse($contract->hora_evento)->format('H:i') : '' }}" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Lugar / Dirección del Evento</label>
                        <input type="text" name="lugar_evento" class="form-control form-control-sm" value="{{ $contract->lugar_evento }}" placeholder="Ej: Av. Las Palmeras 123, Salón Los Álamos" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fecha de Finalización (Opcional)</label>
                        <input type="date" name="fecha_vencimiento" class="form-control form-control-sm" value="{{ $contract->fecha_vencimiento ? $contract->fecha_vencimiento->format('Y-m-d') : '' }}" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. PRODUCTS & SERVICES TABLE -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0 border-0 p-0">
                        <i class="ri-shopping-cart-2-line"></i> 3. Lista de Servicios y Productos Contratados
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-item">
                        <i class="ri-add-circle-line me-1"></i> Agregar Servicio / Ítem
                    </button>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle" id="table-contract-items">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="45%">Servicio / Producto</th>
                                <th width="15%" class="text-center">Cantidad</th>
                                <th width="18%" class="text-end">Precio Unit. ({{ $signo }})</th>
                                <th width="12%" class="text-end">Subtotal ({{ $signo }})</th>
                                <th width="5%" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contract->items as $idx => $item)
                                <tr class="item-row" data-index="{{ $idx }}">
                                    <td class="text-center row-number">{{ $idx + 1 }}</td>
                                    <td>
                                        <select class="form-select form-select-sm mb-1 select-product-item">
                                            <option value="">-- Servicio o Producto Personalizado --</option>
                                            @foreach ($products as $p)
                                                <option value="{{ $p->id }}" data-name="{{ $p->descripcion }}" data-price="{{ $p->precio_venta }}" {{ $item->idproducto == $p->id ? 'selected' : '' }}>
                                                    {{ $p->descripcion }} (S/ {{ number_format($p->precio_venta, 2) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="items[{{ $idx }}][descripcion]" class="form-control form-control-sm item-desc" placeholder="Descripción detallada" value="{{ $item->descripcion }}" required />
                                        <input type="hidden" name="items[{{ $idx }}][idproducto]" class="item-product-id" value="{{ $item->idproducto }}" />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][cantidad]" class="form-control form-control-sm text-center item-qty" value="{{ $item->cantidad }}" required />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][precio_unitario]" class="form-control form-control-sm text-end item-price" value="{{ $item->precio_unitario }}" required />
                                    </td>
                                    <td class="text-end fw-bold">
                                        <span class="item-subtotal">{{ number_format($item->subtotal, 2, '.', '') }}</span>
                                        <input type="hidden" name="items[{{ $idx }}][subtotal]" class="item-subtotal-input" value="{{ $item->subtotal }}" />
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" title="Quitar">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="item-row" data-index="0">
                                    <td class="text-center row-number">1</td>
                                    <td>
                                        <input type="text" name="items[0][descripcion]" class="form-control form-control-sm item-desc" placeholder="Descripción detallada" value="Servicio Principal" required />
                                        <input type="hidden" name="items[0][idproducto]" class="item-product-id" value="" />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[0][cantidad]" class="form-control form-control-sm text-center item-qty" value="1" required />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][precio_unitario]" class="form-control form-control-sm text-end item-price" value="0.00" required />
                                    </td>
                                    <td class="text-end fw-bold">
                                        <span class="item-subtotal">0.00</span>
                                        <input type="hidden" name="items[0][subtotal]" class="item-subtotal-input" value="0.00" />
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" title="Quitar">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Totals & IGV Box -->
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="p-3 totals-card">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="apply_igv" name="apply_igv" value="1" {{ $contract->igv > 0 ? 'checked' : '' }}>
                                <label class="form-check-label text-white small" for="apply_igv">Calcular I.G.V. (18%)</label>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Subtotal:</span>
                                <span class="fw-bold">{{ $signo }} <span id="display-subtotal">{{ number_format($contract->subtotal, 2) }}</span></span>
                                <input type="hidden" name="subtotal" id="input-subtotal" value="{{ $contract->subtotal }}" />
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">I.G.V. (18%):</span>
                                <span class="fw-bold">{{ $signo }} <span id="display-igv">{{ number_format($contract->igv, 2) }}</span></span>
                                <input type="hidden" name="igv" id="input-igv" value="{{ $contract->igv }}" />
                            </div>
                            <hr class="my-2 border-secondary">
                            <div class="d-flex justify-content-between fs-5">
                                <span class="fw-bold">TOTAL:</span>
                                <span class="fw-bold text-success">{{ $signo }} <span id="display-total">{{ number_format($contract->total, 2) }}</span></span>
                                <input type="hidden" name="total" id="input-total" value="{{ $contract->total }}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. PAYMENT SCHEDULE & CREDIT FINANCING -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0 border-0 p-0">
                        <i class="ri-bank-card-line"></i> 4. Cronograma de Pagos y Financiamiento a Crédito
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary btn-quick-split active" data-split="50_50">
                                50% Inicial + 50% Saldo
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-quick-split" data-split="50_25_25">
                                50% Inicial + 2 Cuotas (25%/25%)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-quick-split" data-split="50_3">
                                50% Inicial + 3 Cuotas
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success" id="btn-add-installment">
                            <i class="ri-add-line me-1"></i> Agregar Cuota
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between">
                    <div class="small">
                        <i class="ri-information-line me-1"></i>
                        <strong>Esquema Estándar para Eventos:</strong> Se establece un <strong>adelanto inicial del 50%</strong> para reserva de fecha y el saldo restante amortizado en cuotas con fecha límite hasta la ejecución del evento.
                    </div>
                    <div class="badge bg-white text-info border border-info fw-bold py-1 px-2">
                        Crédito Configurable
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle" id="table-contract-installments">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="35%">Concepto / Descripción</th>
                                <th width="15%" class="text-center">Porcentaje (%)</th>
                                <th width="20%" class="text-center">Fecha Vencimiento <span class="text-danger">*</span></th>
                                <th width="20%" class="text-end">Monto ({{ $signo }}) <span class="text-danger">*</span></th>
                                <th width="5%" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Generated dynamically by js-form.blade.php -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Suma de Cuotas Programadas:</td>
                                <td class="text-end fw-bold">
                                    {{ $signo }} <span id="display-installments-sum">0.00</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Validation and Guarantee info cards -->
                <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                        <div id="installment-balance-alert" class="p-2 rounded-3 border bg-success-subtle border-success text-success small d-flex align-items-center">
                            <i class="ri-checkbox-circle-line fs-5 me-2"></i>
                            <span id="installment-balance-text">El cronograma de pagos cuadra exactamente con el total del contrato.</span>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="p-2 rounded-3 border bg-warning-subtle border-warning text-dark small">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-warning-emphasis">
                                    <i class="ri-shield-check-line me-1"></i> Garantía 20% (Cláusula 8va):
                                </span>
                                <span class="fw-bold fs-6 text-dark">
                                    {{ $signo }} <span id="display-guarantee-val">0.00</span>
                                </span>
                            </div>
                            <span class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                Monto referencial por pérdidas/roturas de menaje y barras.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. CONTRACT CLAUSES -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0 border-0 p-0">
                        <i class="ri-article-line"></i> 5. Cláusulas del Contrato (Personalizables y Múltiples)
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-clause">
                            <i class="ri-add-line me-1"></i> Agregar Cláusula
                        </button>
                    </div>
                </div>

                <div id="clauses-container">
                    @foreach ($contract->clauses as $idx => $clause)
                        <div class="card mb-3 clause-card border" data-index="{{ $idx }}">
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary clause-header-title">
                                    <i class="ri-article-line me-1"></i> Cláusula #{{ $idx + 1 }}
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
                                    <input type="text" name="clauses[{{ $idx }}][titulo]" class="form-control form-control-sm clause-title-input" value="{{ $clause->titulo }}" required />
                                </div>
                                <div>
                                    <label class="form-label small fw-bold">Contenido de la Cláusula</label>
                                    <textarea name="clauses[{{ $idx }}][contenido]" class="form-control form-control-sm clause-content-input" rows="3" required>{{ $clause->contenido }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 6. DIGITAL SIGNATURE -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="section-title">
                    <i class="ri-quill-pen-line"></i> 6. Firma Digital del Cliente
                </div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">
                            Trazar Nueva Firma Digital (Sobrescribirá la firma actual si dibuja en el lienzo)
                        </label>
                        <div class="signature-container mb-2">
                            <canvas id="signature-pad"></canvas>
                        </div>
                        <input type="hidden" name="signature_client_data" id="signature_client_data" />
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted" id="signature-status-text">
                                Lienzo listo.
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-signature">
                                <i class="ri-eraser-line me-1"></i> Limpiar Lienzo
                            </button>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <label class="form-label small fw-bold text-dark">
                                <i class="ri-check-double-line me-1"></i> Firma Actual Registrada
                            </label>
                            @if ($contract->firma_cliente)
                                <div class="p-2 border bg-white rounded-3 text-center mb-3">
                                    <img src="{{ asset($contract->firma_cliente) }}" alt="Firma Actual" style="max-height: 90px; max-width: 100%;" />
                                    <span class="small text-success d-block mt-1"><i class="ri-checkbox-circle-fill"></i> Firma registrada</span>
                                </div>
                            @else
                                <div class="alert alert-secondary py-2 small mb-3">
                                    Este contrato aún no cuenta con firma digital registrada.
                                </div>
                            @endif

                            <label class="form-label small fw-bold text-dark">
                                <i class="ri-upload-cloud-line me-1"></i> O subir nuevo archivo de firma
                            </label>
                            <input type="file" name="signature_client_file" id="signature_file_input" class="form-control form-control-sm mb-2" accept="image/png, image/jpeg" />

                            <div id="signature-preview-container" style="display: none;" class="text-center p-2 border bg-white rounded-3">
                                <span class="small text-muted d-block mb-1">Nueva firma seleccionada:</span>
                                <img id="signature-preview-img" src="" alt="Firma previa" style="max-height: 80px; max-width: 100%;" />
                                <button type="button" class="btn btn-xs btn-outline-danger mt-1 d-block mx-auto" id="btn-remove-file-signature">
                                    Quitar archivo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. OBSERVATIONS & STATUS & SUBMIT -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="section-title">
                    <i class="ri-chat-check-line"></i> 7. Observaciones, Condiciones de Pago y Estado
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Condiciones de Pago / Notas Adicionales</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="3">{{ $contract->observaciones }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Estado del Contrato</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="1" {{ $contract->estado == 1 ? 'selected' : '' }}>Firmado / Activo</option>
                            <option value="0" {{ $contract->estado == 0 ? 'selected' : '' }}>Borrador / Pendiente de Firma</option>
                            <option value="2" {{ $contract->estado == 2 ? 'selected' : '' }}>Completado</option>
                            <option value="3" {{ $contract->estado == 3 ? 'selected' : '' }}>Anulado</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.contracts') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-contract">
                        <i class="ri-save-3-line me-1"></i> Actualizar y Regenerar Contrato (A4 PDF)
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@include('admin.contracts.modals')
@endsection

@section('scripts')
<script>
    window.productCatalog = @json($products ?? []);
    window.existingInstallments = @json($contract->installments ?? []);
</script>
@include('admin.contracts.js-form')
@endsection
