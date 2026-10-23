{{-- MODAL CREAR / EDITAR PAQUETE --}}
<div class="modal fade" id="modalPackage" tabindex="-1" aria-labelledby="modalPackageTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formPackage" class="modal-content border-0 shadow-lg" enctype="multipart/form-data" onsubmit="event.preventDefault()">
            @csrf
            <input type="hidden" name="id" id="package_id" value="">

            <div class="modal-header bg-dark text-white border-bottom-0 py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="modalPackageTitle">
                    <i class="ri-gift-line text-warning me-2 fs-20"></i> <span id="modalPackageActionTitle">Nuevo Paquete de Evento</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                {{-- Nav Tabs --}}
                <ul class="nav nav-tabs nav-tabs-custom mb-3" id="packageTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                            <i class="ri-information-line me-1"></i> 1. Información General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-tiers-btn" data-bs-toggle="tab" data-bs-target="#tab-tiers" type="button" role="tab">
                            <i class="ri-stack-line me-1"></i> 2. Tramos de Invitados (Tiers)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
                            <i class="ri-list-check me-1"></i> 3. Componentes e Ítems Incluidos
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="packageTabContent">
                    {{-- TAB 1: INFORMACIÓN GENERAL --}}
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label small fw-bold text-muted text-uppercase">
                                    Nombre del Paquete <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name" class="form-control border-2 text-uppercase fw-semibold" placeholder="Ej: PAQUETE SIGNATURE" required>
                            </div>

                            <div class="col-md-4">
                                <label for="price_mode" class="form-label small fw-bold text-muted text-uppercase">
                                    Modalidad de Precio <span class="text-danger">*</span>
                                </label>
                                <select name="price_mode" id="price_mode" class="form-select border-2" required>
                                    <option value="tiered" selected>Escalonado por Tramos (Recomendado)</option>
                                    <option value="per_guest">Por Persona / Invitado</option>
                                    <option value="fixed">Precio Fijo Total</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="min_guests" class="form-label small fw-bold text-muted text-uppercase">
                                    Mínimo Invitados <span class="text-danger">*</span>
                                </label>
                                <input type="number" id="min_guests" name="min_guests" class="form-control" min="1" value="100" required>
                            </div>

                            <div class="col-md-3">
                                <label for="max_guests" class="form-label small fw-bold text-muted text-uppercase">
                                    Máximo Invitados
                                </label>
                                <input type="number" id="max_guests" name="max_guests" class="form-control" min="1" value="180" placeholder="Sin límite">
                            </div>

                            <div class="col-md-6" id="box_base_price" style="display: none;">
                                <label for="base_price" class="form-label small fw-bold text-muted text-uppercase" id="label_base_price">
                                    Precio Base ({{ $signo }})
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-dark fw-bold">{{ $signo }}</span>
                                    <input type="number" step="0.50" id="base_price" name="base_price" class="form-control" value="0.00">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label small fw-bold text-muted text-uppercase">
                                    Descripción & Alcance del Paquete
                                </label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Detalle vasos, cocteles incluidos, logística, traslados..."></textarea>
                            </div>

                            <div class="col-md-8">
                                <label for="image" class="form-label small fw-bold text-muted text-uppercase">
                                    Imagen del Paquete
                                </label>
                                <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                                <small class="text-muted">Formatos JPG, PNG, WEBP (Máx 4MB).</small>
                            </div>

                            <div class="col-md-4 d-flex align-items-center pt-4">
                                <div class="form-check form-switch fs-15">
                                    <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                    <label class="form-check-label fw-semibold text-dark" for="active">Paquete Activo en Catálogo</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: TRAMOS DE INVITADOS (TIERS) --}}
                    <div class="tab-pane fade" id="tab-tiers" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Configuración de Tramos de Invitados</h6>
                                <p class="text-muted small mb-0">Defina los rangos de invitados y el precio total o por persona aplicable a cada intervalo. No se permiten solapamientos de tramos.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnAddTierRow">
                                <i class="ri-add-line me-1"></i> Agregar Tramo
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 140px;">Desde (Invitados)</th>
                                        <th style="width: 140px;">Hasta (Invitados)</th>
                                        <th style="width: 190px;">Precio ({{ $signo }})</th>
                                        <th style="min-width: 180px;">Tipo de Precio</th>
                                        <th style="width: 60px;" class="text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="tableTiersBody">
                                    {{-- Dynamic Tiers Rows --}}
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="ri-information-line me-1"></i> Ejemplo: Para "Paquete SIGNATURE", configure tramo de 100 a 180 invitados a S/ 4,200.00 (Precio Total del Tramo).
                        </div>
                    </div>

                    {{-- TAB 3: COMPONENTES E ÍTEMS --}}
                    <div class="tab-pane fade" id="tab-items" role="tabpanel">
                        <div class="card bg-light border-0 p-3 mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Tipo de Componente</label>
                                    <select id="item_picker_type" class="form-select form-select-sm" onchange="$('.picker-opt').hide(); $('#picker_' + this.value).show();">
                                        <option value="service">Servicio / Equipo (Catálogo)</option>
                                        <option value="cocktail">Cóctel de Carta</option>
                                    </select>
                                </div>

                                <div class="col-md-7 picker-opt" id="picker_service">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Servicio / Equipo</label>
                                    <select id="picker_product_id" class="form-select form-select-sm">
                                        <option value="">-- Seleccionar Servicio o Equipo --</option>
                                        @foreach ($products as $prod)
                                            <option value="{{ $prod->id }}" data-precio="{{ $prod->precio_venta }}">
                                                {{ $prod->descripcion }} ({{ (int)$prod->opcion === 2 ? 'Servicio ZZ' : 'Bien Rentable' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-7 picker-opt" id="picker_cocktail" style="display: none;">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Cóctel del Menú</label>
                                    <select id="picker_cocktail_id" class="form-select form-select-sm">
                                        <option value="">-- Seleccionar Cóctel --</option>
                                        @foreach ($cocktails as $c)
                                            <option value="{{ $c->id }}" data-precio="{{ $c->precio }}">
                                                {{ $c->nombre }} [{{ optional($c->category)->nombre ?: 'Sin Cat.' }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm btn-primary w-100 fw-bold" id="btnAddItemRow">
                                        <i class="ri-add-line me-1"></i> Añadir
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Componente</th>
                                        <th style="width: 100px;">Cantidad</th>
                                        <th style="width: 90px;" class="text-center" title="Multiplicar cantidad por el número de invitados">Por Inv.</th>
                                        <th style="width: 90px;" class="text-center" title="Ítem mandatorio u opcional">Mandat.</th>
                                        <th style="width: 130px;">Precio Ref.</th>
                                        <th>Notas / Función</th>
                                        <th style="width: 50px;" class="text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="tableItemsBody">
                                    {{-- Dynamic Items Rows --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-top-0 py-3 px-4">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSavePackage">
                    <i class="ri-save-line me-1"></i> Guardar Paquete
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL SIMULADOR DE CÁLCULO DE PRECIOS E IGV --}}
<div class="modal fade" id="modalCalculatorSimulator" tabindex="-1" aria-labelledby="modalCalculatorSimulatorTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="modalCalculatorSimulatorTitle">
                    <i class="ri-calculator-line me-2 fs-20"></i> Simulador de Precios & Auditoría Tributaria IGV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-7">
                        <label for="sim_package_id" class="form-label small fw-bold text-muted text-uppercase">Paquete a Simular</label>
                        <select id="sim_package_id" class="form-select border-2">
                            @foreach (\App\Models\Package::orderBy('name')->get() as $pkg)
                                <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ $pkg->guest_range_label }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="sim_guests" class="form-label small fw-bold text-muted text-uppercase">N° de Invitados</label>
                        <input type="number" id="sim_guests" class="form-control border-2 text-center fw-bold" value="120" min="1">
                    </div>

                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100 fw-bold" id="btnRunSimulation">
                            <i class="ri-refresh-line me-1"></i> Calcular
                        </button>
                    </div>
                </div>

                <div id="sim_results_box">
                    <div class="text-center py-4 text-muted">
                        Seleccione un paquete y la cantidad de invitados para calcular los montos.
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
