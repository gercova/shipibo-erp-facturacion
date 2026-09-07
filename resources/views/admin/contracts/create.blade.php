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
                        <div class="page-header-icon"><i data-feather="file-plus"></i></div>
                        Nuevo Contrato de Prestación de Servicios
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
    <form id="form-contract" action="{{ route('admin.store_contract') }}" method="POST" enctype="multipart/form-data">
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
                        <input type="text" name="contract_number" class="form-control form-control-sm fw-bold text-primary" value="{{ $nextNumber }}" readonly />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fecha de Emisión <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_emision" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Título del Contrato</label>
                        <input type="text" name="title" class="form-control form-control-sm" value="CONTRATO DE PRESTACIÓN DE SERVICIOS" required />
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
                                    <option value="{{ $c->id }}">
                                        {{ $c->nombres }} ({{ $c->tipoDocumento?->descripcion ?? 'Doc' }}: {{ $c->nro_documento }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Seleccione el cliente que contrata el servicio o registre uno nuevo.</small>
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
                                    <input type="text" name="provider_name" class="form-control form-control-sm" placeholder="Razón Social / Nombre" value="{{ $business?->razon_social ?: ($business?->nombre_comercial ?: '') }}" />
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="provider_document" class="form-control form-control-sm" placeholder="RUC / DNI" value="{{ $business?->ruc ?: '' }}" />
                                </div>
                                <div class="col-12">
                                    <input type="text" name="provider_representative" class="form-control form-control-sm" placeholder="Representante Legal / Gerente" value="{{ $business?->representante ?: '' }}" />
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
                        <input type="date" name="fecha_evento" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Hora del Evento</label>
                        <input type="time" name="hora_evento" class="form-control form-control-sm" value="19:00" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Lugar / Dirección del Evento</label>
                        <input type="text" name="lugar_evento" class="form-control form-control-sm" placeholder="Ej: Av. Las Palmeras 123, Salón Los Álamos" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fecha de Finalización (Opcional)</label>
                        <input type="date" name="fecha_vencimiento" class="form-control form-control-sm" />
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
                            <!-- Initial Item Row -->
                            <tr class="item-row" data-index="0">
                                <td class="text-center row-number">1</td>
                                <td>
                                    <select class="form-select form-select-sm mb-1 select-product-item">
                                        <option value="">-- Servicio o Producto Personalizado --</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" data-name="{{ $p->descripcion }}" data-price="{{ $p->precio_venta }}">
                                                {{ $p->descripcion }} (S/ {{ number_format($p->precio_venta, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="items[0][descripcion]" class="form-control form-control-sm item-desc" placeholder="Descripción detallada del servicio o producto" value="Servicio Integral de Eventos" required />
                                    <input type="hidden" name="items[0][idproducto]" class="item-product-id" value="" />
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="items[0][cantidad]" class="form-control form-control-sm text-center item-qty" value="1" required />
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="items[0][precio_unitario]" class="form-control form-control-sm text-end item-price" value="500.00" required />
                                </td>
                                <td class="text-end fw-bold">
                                    <span class="item-subtotal">500.00</span>
                                    <input type="hidden" name="items[0][subtotal]" class="item-subtotal-input" value="500.00" />
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" title="Quitar">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals & IGV Box -->
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="p-3 totals-card">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="apply_igv" name="apply_igv" value="1">
                                <label class="form-check-label text-white small" for="apply_igv">Calcular I.G.V. (18%)</label>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Subtotal:</span>
                                <span class="fw-bold">{{ $signo }} <span id="display-subtotal">0.00</span></span>
                                <input type="hidden" name="subtotal" id="input-subtotal" value="0.00" />
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">I.G.V. (18%):</span>
                                <span class="fw-bold">{{ $signo }} <span id="display-igv">0.00</span></span>
                                <input type="hidden" name="igv" id="input-igv" value="0.00" />
                            </div>
                            <hr class="my-2 border-secondary">
                            <div class="d-flex justify-content-between fs-5">
                                <span class="fw-bold">TOTAL:</span>
                                <span class="fw-bold text-success">{{ $signo }} <span id="display-total">0.00</span></span>
                                <input type="hidden" name="total" id="input-total" value="0.00" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. CONTRACT CLAUSES -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0 border-0 p-0">
                        <i class="ri-article-line"></i> 4. Cláusulas del Contrato (Personalizables y Múltiples)
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="btn-load-default-clauses">
                            <i class="ri-refresh-line me-1"></i> Cargar Cláusulas Estándar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-clause">
                            <i class="ri-add-line me-1"></i> Agregar Cláusula
                        </button>
                    </div>
                </div>

                <div id="clauses-container">
                    @foreach ($defaultClauses as $idx => $clause)
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
                                    <input type="text" name="clauses[{{ $idx }}][titulo]" class="form-control form-control-sm clause-title-input" value="{{ $clause['titulo'] }}" required />
                                </div>
                                <div>
                                    <label class="form-label small fw-bold">Contenido de la Cláusula</label>
                                    <textarea name="clauses[{{ $idx }}][contenido]" class="form-control form-control-sm clause-content-input" rows="3" required>{{ $clause['contenido'] }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 5. DIGITAL SIGNATURE -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="section-title">
                    <i class="ri-quill-pen-line"></i> 5. Firma Digital del Cliente (Captura en Pantalla o Subida)
                </div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">
                            Trazar Firma Digital en el Recuadro (Mouse, Lápiz o Pantalla Táctil)
                        </label>
                        <div class="signature-container mb-2">
                            <canvas id="signature-pad"></canvas>
                        </div>
                        <input type="hidden" name="signature_client_data" id="signature_client_data" />
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted" id="signature-status-text">
                                Lienzo listo para firmar.
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-signature">
                                <i class="ri-eraser-line me-1"></i> Limpiar Firma
                            </button>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <label class="form-label small fw-bold text-dark">
                                <i class="ri-upload-cloud-line me-1"></i> Opción alternativa: Subir Archivo de Firma
                            </label>
                            <p class="small text-muted mb-2">
                                Si el cliente ya dispone de una firma digitalizada en formato imagen (PNG o JPG), puede adjuntarla directamente.
                            </p>
                            <input type="file" name="signature_client_file" id="signature_file_input" class="form-control form-control-sm mb-3" accept="image/png, image/jpeg" />

                            <div id="signature-preview-container" style="display: none;" class="text-center p-2 border bg-white rounded-3">
                                <span class="small text-muted d-block mb-1">Vista Previa:</span>
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

        <!-- 6. OBSERVATIONS & STATUS & SUBMIT -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="section-title">
                    <i class="ri-chat-check-line"></i> 6. Observaciones, Condiciones de Pago y Estado
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Condiciones de Pago / Notas Adicionales</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="3" placeholder="Ej: Anticipo del 50% al momento de la firma y 50% restante 24 horas antes del evento."></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Estado del Contrato</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="1" selected>Firmado / Activo</option>
                            <option value="0">Borrador / Pendiente de Firma</option>
                        </select>
                        <small class="text-muted d-block mt-1">El estado "Firmado" habilita la validez inmediata del contrato y la descarga del PDF final.</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.contracts') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-contract">
                        <i class="ri-save-3-line me-1"></i> Guardar y Generar Contrato (A4 PDF)
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
    window.defaultContractClauses = @json($defaultClauses ?? []);
</script>
@include('admin.contracts.js-form')
@endsection
