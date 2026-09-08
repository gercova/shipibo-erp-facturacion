<div class="offcanvas offcanvas-end" tabindex="-1" id="offCanvasDetail" aria-labelledby="offCanvasDetailLabel1">
    <div class="offcanvas-header border-bottom border-block-end-dashed">
        <h5 class="offcanvas-title" id="offCanvasDetailLabel1">INFORMACI&Oacute;N DE PRODUCTO</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div>
            <ul class="list-group list-group-flush mb-0">
                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">C&oacute;digo interno</p>
                            <span class="fs-12 text-muted detail-code"></span>
                        </div>
                    </div>
                </li>

                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">C&oacute;digo de barras</p>
                            <span class="fs-12 text-muted detail-barcode"></span>
                        </div>
                    </div>
                </li>

                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">Descripci&oacute;n</p>
                            <span class="fs-12 text-muted detail-description"></span>
                        </div>
                    </div>
                </li>

                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">Categor&iacute;a</p>
                            <span class="fs-12 text-muted detail-category"></span>
                        </div>
                    </div>
                </li>

                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">Precio Compra</p>
                            <span class="fs-12 text-muted detail-buy"></span>
                        </div>
                    </div>
                </li>

                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">Precio Venta</p>
                            <span class="fs-12 text-muted detail-sale"></span>
                        </div>
                    </div>
                </li>
                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">Tipo / Clasificaci&oacute;n</p>
                            <span class="fs-12 text-muted detail-type"></span>
                        </div>
                    </div>
                </li>
                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">Stock Actual</p>
                            <span class="fs-12 text-muted detail-stock"></span>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditProduct" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="form_edit_product" class="modal-content border-0 shadow-lg" onsubmit="event.preventDefault()">
            @csrf
            <input type="hidden" name="id">
            <input type="hidden" name="original_opcion" id="edit_original_opcion">

            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="fas fa-edit text-primary me-2"></i> Actualizar Producto o Servicio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="alert bg-light border-0 shadow-sm mb-4" style="border-left: 4px solid #0d6efd;">
                    <div class="d-flex">
                        <div class="me-2 text-primary">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="small text-muted">
                            <strong class="text-dark">Flujo del sistema:</strong><br>
                            Los precios y el stock se definen al registrar y luego se gestionan desde
                            <strong>almacenes</strong>. Aqu&iacute; puedes actualizar la informaci&oacute;n fiscal y general
                            del cat&aacute;logo.
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre del Producto o Servicio</label>
                        <input type="text" name="descripcion" class="form-control form-control-lg text-uppercase border-2" placeholder="Ej: MANTENIMIENTO / SSD 500GB" required>
                        <div class="invalid-feedback">Campo obligatorio</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">C&oacute;d. Interno</label>
                        <input type="text" name="codigo_interno" class="form-control bg-light" placeholder="Opcional">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">C&oacute;d. Barras</label>
                        <input type="text" name="codigo_barras" class="form-control bg-light" placeholder="Opcional">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">C&oacute;d. SUNAT</label>
                        <input type="text" name="codigo_sunat" class="form-control bg-light" placeholder="Opcional">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Unidad Base</label>
                        <select name="idunidad" class="form-select border-2 edit-product-select">
                            <option value="">Seleccione...</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->descripcion }} ({{ $unit->codigo }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase text-secondary">Categor&iacute;a</label>
                        <select name="idcategoria" class="form-select border-2 edit-product-select">
                            <option value="">Seleccione categor&iacute;a...</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->descripcion }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Campo obligatorio</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Afectaci&oacute;n IGV</label>
                        <select name="idcodigo_igv" class="form-select border-2 edit-product-select" id="edit_idcodigo_igv" data-default="{{ $igvTypeAffections->firstWhere('codigo', '10')?->id ?? $igvTypeAffections->first()?->id }}">
                            <option value="">Seleccione...</option>
                            @foreach ($igvTypeAffections as $igvType)
                                <option value="{{ $igvType->id }}" data-code="{{ $igvType->codigo }}">{{ $igvType->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr class="text-muted opacity-25">

                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted text-uppercase d-block">Tipo de Item</label>
                        <div class="btn-group w-100 shadow-sm" role="group">
                            <input type="radio" class="btn-check" name="opcion" id="edit_producto" value="1">
                            <label class="btn btn-outline-primary py-2" for="edit_producto">
                                <i class="fas fa-boxes me-1"></i> Producto F&iacute;sico
                            </label>

                            <input type="radio" class="btn-check" name="opcion" id="edit_servicio" value="2">
                            <label class="btn btn-outline-primary py-2" for="edit_servicio">
                                <i class="fas fa-tools me-1"></i> Servicio
                            </label>
                        </div>
                    </div>

                    <div class="col-md-5" id="container-edit-rentable">
                        <label class="form-label small fw-bold text-muted text-uppercase d-block">&iquest;Herramienta de Barra / Alquiler?</label>
                        <div class="form-check form-switch p-2 bg-light rounded border px-4 d-flex align-items-center justify-content-between">
                            <label class="form-check-label small fw-semibold text-dark mb-0" for="edit_rentable">
                                <i class="fas fa-cocktail text-warning me-1"></i> Alquilable / Retornable (Checklist)
                            </label>
                            <input class="form-check-input ms-2" type="checkbox" role="switch" id="edit_rentable" name="rentable" value="1">
                        </div>
                    </div>
                </div>

                {{-- ===== PRESENTACIONES / VARIANTES (EDIT) ===== --}}
                <hr class="text-muted opacity-25">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold mb-0"><i class="fas fa-tags text-warning me-2"></i>Presentaciones / Variantes de precio</h6>
                        <small class="text-muted">Opcional &mdash; Define otras unidades con precios diferentes (ej: Docena, Caja, Pack).</small>
                    </div>
                    <button type="button" id="btn-add-presentation-edit" class="btn btn-sm btn-outline-warning fw-bold">
                        <i class="fas fa-plus me-1"></i> Agregar presentaci&oacute;n
                    </button>
                </div>

                <table class="table table-sm table-bordered align-middle mb-0" id="presentations-table-edit">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-bold" style="min-width:140px;">Descripci&oacute;n</th>
                            <th class="text-uppercase small fw-bold" style="min-width:130px;">Unidad</th>
                            <th class="text-uppercase small fw-bold text-center" style="width:100px;">Factor conv.</th>
                            <th class="text-uppercase small fw-bold text-center" style="width:120px;">P. Compra</th>
                            <th class="text-uppercase small fw-bold text-center" style="width:120px;">P. Venta</th>
                            <th class="text-center" style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="presentations-tbody-edit">
                        <tr id="presentations-empty-row-edit">
                            <td colspan="6" class="text-center text-muted small py-3">
                                <i class="fas fa-inbox me-1"></i> Sin presentaciones adicionales
                            </td>
                        </tr>
                    </tbody>
                </table>
                {{-- ===== FIN PRESENTACIONES (EDIT) ===== --}}

            </div>

            <div class="modal-footer border-top-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4 fw-bold text-secondary" data-bs-dismiss="modal">Cancelar</button>

                <button class="btn btn-primary px-5 fw-bold shadow-sm btn-store-product" type="submit">
                    <span class="text-store-product">
                        <i class="fas fa-check-circle me-1"></i> Guardar Cambios
                    </span>
                    <span class="d-none text-storing-product">
                        <i class="fas fa-spinner fa-spin me-1"></i> Guardando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="modalUpload" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form_excel" class="modal-content border-0 shadow-lg" onsubmit="event.preventDefault()" enctype="multipart/form-data">
            @csrf
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="modalUploadTitle">
                    <i class="ri-file-excel-2-line text-success me-2 fs-4"></i> Carga Masiva de Productos y Servicios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <div class="alert alert-info border-0 shadow-sm mb-3">
                    <div class="d-flex">
                        <div class="me-2"><i class="fas fa-info-circle fs-5"></i></div>
                        <div class="small">
                            <strong>Importaci&oacute;n Mixta:</strong> Puedes subir en la misma hoja tanto <strong>PRODUCTOS</strong> (con control de stock y almac&eacute;n) como <strong>SERVICIOS</strong> (sin control de stock).
                            <br>
                            <em>Si una fila presenta alguna inconsistencia (ej. producto sin almac&eacute;n), el sistema importar&aacute; las filas v&aacute;lidas y te reportar&aacute; el detalle de las filas observadas.</em>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="excel" class="form-label small fw-bold text-muted text-uppercase">Seleccionar archivo Excel (.xlsx)</label>
                        <input type="file" id="excel" class="form-control form-control-lg border-2" name="excel" accept=".xlsx,.xls">
                        <div class="invalid-feedback">Seleccione un archivo v&aacute;lido.</div>
                    </div>

                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-1">
                        <a href="{{ route('products.download_excel') }}" class="btn btn-outline-dark btn-sm">
                            <i class="ri-download-2-line me-1"></i>
                            Descargar plantilla con datos actuales
                        </a>
                        <small class="text-muted">L&iacute;mite m&aacute;ximo: 10 MB</small>
                    </div>

                    {{-- Contenedor del reporte de importación --}}
                    <div class="col-12 d-none" id="import-summary-container">
                        <hr class="text-muted opacity-25 my-3">
                        <div class="card bg-light border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="fas fa-chart-pie me-1 text-primary"></i> Resultado de la Importaci&oacute;n
                                    </h6>
                                    <div>
                                        <span class="badge bg-success-subtle text-success fs-13 px-2 py-1" id="badge-imported-count">Importados: 0</span>
                                        <span class="badge bg-danger-subtle text-danger fs-13 px-2 py-1 ms-1" id="badge-error-count">Observaciones: 0</span>
                                    </div>
                                </div>
                                <div id="import-error-table-wrapper" class="d-none mt-3">
                                    <p class="small text-danger fw-semibold mb-1">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Filas omitidas con observaciones:
                                    </p>
                                    <div class="table-responsive" style="max-height: 180px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered bg-white small mb-0" id="table-import-errors">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th class="text-center" style="width: 70px;">Fila</th>
                                                    <th style="min-width: 180px;">Descripci&oacute;n</th>
                                                    <th>Motivo de la Observaci&oacute;n</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody-import-errors"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer border-top-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4 fw-bold text-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-success px-4 fw-bold btn-upload-product">
                    <span class="text-upload-product">
                        <i class="ri-upload-cloud-2-line me-1"></i> Procesar Importaci&oacute;n
                    </span>
                    <span class="spinner-border spinner-border-sm me-1 d-none text-uploads-product" role="status" aria-hidden="true"></span>
                    <span class="text-uploads-product d-none">Procesando archivo...</span>
                </button>
            </div>
        </form>
    </div>
</div>
