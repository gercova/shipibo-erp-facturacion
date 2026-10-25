{{-- MODAL CREAR / EDITAR PAQUETE --}}
<div class="modal fade" id="modalPackage" tabindex="-1" aria-labelledby="modalPackageTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formPackage" class="modal-content border-0 shadow-lg" enctype="multipart/form-data" onsubmit="event.preventDefault()">
            @csrf
            <input type="hidden" name="id" id="package_id" value="">
            <input type="hidden" name="image_preset" id="image_preset" value="">
            <div class="modal-header bg-dark text-white border-bottom-0 py-3 px-4">
                <div class="d-flex align-items-center">
                    <div>
                        <h5 class="modal-title text-white fw-bold mb-0" id="modalPackageTitle">Configurar Paquete del Catálogo Web</h5>
                        <p class="text-white-50 small mb-0">Gestión de especificaciones, capacidades, esquema de precios y estilo flyer para la vista <span class="badge bg-warning text-dark">/pedido</span></p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Nav Tabs --}}
                <ul class="nav nav-pills nav-justified bg-light p-1 rounded-3 mb-4" id="packageTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-2" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                            <i class="ri-information-line me-1"></i> 1. Información & Web
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-2" id="tab-capacity-btn" data-bs-toggle="tab" data-bs-target="#tab-capacity" type="button" role="tab">
                            <i class="ri-group-line me-1"></i> 2. Capacidad (Personas)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-2" id="tab-colors-btn" data-bs-toggle="tab" data-bs-target="#tab-colors" type="button" role="tab">
                            <i class="ri-palette-line me-1"></i> 3. Colores & Estilo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-2" id="tab-pricing-btn" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button" role="tab">
                            <i class="ri-money-dollar-circle-line me-1"></i> 4. Precios & Tramos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-2" id="tab-specs-btn" data-bs-toggle="tab" data-bs-target="#tab-specs" type="button" role="tab">
                            <i class="ri-file-list-3-line me-1"></i> 5. Especificaciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-2" id="tab-image-btn" data-bs-toggle="tab" data-bs-target="#tab-image" type="button" role="tab">
                            <i class="ri-image-line me-1"></i> 6. Foto / Cutout
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="packageTabContent">
                    {{-- TAB 1: INFORMACIÓN GENERAL & WEB --}}
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="name" class="form-label small fw-bold text-muted text-uppercase">
                                    Nombre del Paquete <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name" class="form-control border-2 text-uppercase fw-bold" placeholder="Ej: PAQUETE SIGNATURE" required>
                                <small class="text-muted">Nombre principal visible en la tarjeta del catálogo web.</small>
                            </div>
                            <div class="col-md-2">
                                <label for="order_num" class="form-label small fw-bold text-muted text-uppercase">
                                    N° Orden Web
                                </label>
                                <input type="text" id="order_num" name="order_num" class="form-control text-center fw-bold" placeholder="Ej: 01, 05" maxlength="10">
                                <small class="text-muted">Orden en el flyer.</small>
                            </div>
                            <div class="col-md-3">
                                <label for="badge_glasses" class="form-label small fw-bold text-muted text-uppercase">
                                    Insignia Vasos / Copas
                                </label>
                                <input type="text" id="badge_glasses" name="badge_glasses" class="form-control fw-semibold" placeholder="Ej: 180 Vasos">
                                <small class="text-muted">Badge superior derecho.</small>
                            </div>
                            <div class="col-12">
                                <label for="tagline" class="form-label small fw-bold text-muted text-uppercase">
                                    Tagline / Subtítulo Web
                                </label>
                                <input type="text" id="tagline" name="tagline" class="form-control" placeholder="Ej: Vasos en acrílico, cristalería, barra móvil, cócteles clásicos y de autor">
                                <small class="text-muted">Breve descripción destacada bajo el título del paquete.</small>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label small fw-bold text-muted text-uppercase">
                                    Descripción Completa & Términos
                                </label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Detalle completo de logística, horarios, cristalería y condiciones de catering..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="card border p-3 bg-light rounded-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="is_barman" name="is_barman" value="1">
                                        <label class="form-check-label fw-bold text-dark" for="is_barman">
                                            <i class="ri-user-star-line text-warning me-1"></i> Modalidad "Servicio de Barman"
                                        </label>
                                        <div class="small text-muted mt-1">Activar si este ítem corresponde a servicio por horas donde el cliente proporciona insumos.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border p-3 bg-light rounded-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" checked>
                                        <label class="form-check-label fw-bold text-success" for="active">
                                            <i class="ri-checkbox-circle-line me-1"></i> Paquete Activo y Visible en /pedido
                                        </label>
                                        <div class="small text-muted mt-1">Si se desactiva, no se mostrará a los clientes en el formulario público.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- TAB 2: CAPACIDAD (PERSONAS / INVITADOS) --}}
                    <div class="tab-pane fade" id="tab-capacity" role="tabpanel">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="ri-information-line me-1"></i> Configure los rangos de invitados para los cálculos de cotización y los textos orientativos mostrados al usuario en el catálogo web.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <label for="min_guests" class="form-label small fw-bold text-muted text-uppercase">
                                    Mínimo Invitados <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="ri-user-follow-line"></i></span>
                                    <input type="number" id="min_guests" name="min_guests" class="form-control fw-bold" min="1" value="100" required>
                                </div>
                                <small class="text-muted">Límite inferior para contratar.</small>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="max_guests" class="form-label small fw-bold text-muted text-uppercase">
                                    Máximo Invitados
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="ri-user-shared-line"></i></span>
                                    <input type="number" id="max_guests" name="max_guests" class="form-control fw-bold" min="1" value="180" placeholder="Sin límite">
                                </div>
                                <small class="text-muted">Límite superior de capacidad.</small>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="default_guests" class="form-label small fw-bold text-muted text-uppercase">
                                    Invitados Sugeridos (Defecto)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="ri-user-settings-line"></i></span>
                                    <input type="number" id="default_guests" name="default_guests" class="form-control fw-bold" min="1" value="100">
                                </div>
                                <small class="text-muted">Valor inicial al seleccionar el paquete.</small>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="ideal_capacity" class="form-label small fw-bold text-muted text-uppercase">
                                    Etiqueta "Ideal Para"
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="ri-award-line"></i></span>
                                    <input type="text" id="ideal_capacity" name="ideal_capacity" class="form-control fw-semibold" placeholder="Ej: 100 a 180 personas">
                                </div>
                                <small class="text-muted">Texto visible en el pie de la tarjeta flyer.</small>
                            </div>
                        </div>
                    </div>
                    {{-- TAB 3: COLORES & ESTILO FLYER --}}
                    <div class="tab-pane fade" id="tab-colors" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <h6 class="fw-bold text-dark mb-2">Paleta de Colores de la Tarjeta Web</h6>
                                <p class="text-muted small mb-3">Defina los colores corporativos con los que se representará este paquete en la vista de pedidos de los clientes.</p>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="color" class="form-label small fw-bold text-muted text-uppercase">Color Primario Flyer</label>
                                        <div class="input-group">
                                            <input type="color" id="color_picker" class="form-control form-control-color border-2" value="#275c34" title="Seleccionar color primario">
                                            <input type="text" id="color" name="color" class="form-control font-monospace fw-bold" value="#275c34" maxlength="30">
                                        </div>
                                        <small class="text-muted">Usado en títulos, números, insignias y botones.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="light_bg" class="form-label small fw-bold text-muted text-uppercase">Fondo Claro de Contraste</label>
                                        <div class="input-group">
                                            <input type="color" id="light_bg_picker" class="form-control form-control-color border-2" value="#e9edea" title="Seleccionar fondo claro">
                                            <input type="text" id="light_bg" name="light_bg" class="form-control font-monospace fw-bold" value="#e9edea" maxlength="30">
                                        </div>
                                        <small class="text-muted">Usado en el cajetín de capacidad "Ideal para".</small>
                                    </div>
                                </div>
                                <h6 class="fw-bold text-dark mb-2">Paletas Rápidas Inspiradas en Flyers</h6>
                                <p class="text-muted small mb-2">Haga clic sobre una paleta preestablecida para aplicarla automáticamente:</p>
                                <div class="d-flex flex-wrap gap-2 mb-3" id="quickColorPalettes">
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 p-1 pe-2 rounded-pill palette-preset-btn" data-color="#275c34" data-bg="#e9edea">
                                        <span class="rounded-circle" style="width: 18px; height: 18px; background-color: #275c34;"></span>
                                        <span class="small fw-semibold">01 Esencial</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 p-1 pe-2 rounded-pill palette-preset-btn" data-color="#189e82" data-bg="#e3f6f1">
                                        <span class="rounded-circle" style="width: 18px; height: 18px; background-color: #189e82;"></span>
                                        <span class="small fw-semibold">02 Tropical</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 p-1 pe-2 rounded-pill palette-preset-btn" data-color="#273e6b" data-bg="#e3e9f4">
                                        <span class="rounded-circle" style="width: 18px; height: 18px; background-color: #273e6b;"></span>
                                        <span class="small fw-semibold">03 Premium</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 p-1 pe-2 rounded-pill palette-preset-btn" data-color="#be8632" data-bg="#f6efe3">
                                        <span class="rounded-circle" style="width: 18px; height: 18px; background-color: #be8632;"></span>
                                        <span class="small fw-semibold">04 Gold</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 p-1 pe-2 rounded-pill palette-preset-btn" data-color="#9e2d5c" data-bg="#f5e4ec">
                                        <span class="rounded-circle" style="width: 18px; height: 18px; background-color: #9e2d5c;"></span>
                                        <span class="small fw-semibold">05 Signature</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 p-1 pe-2 rounded-pill palette-preset-btn" data-color="#14728f" data-bg="#e0f0f5">
                                        <span class="rounded-circle" style="width: 18px; height: 18px; background-color: #14728f;"></span>
                                        <span class="small fw-semibold">06 Luxury</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 p-1 pe-2 rounded-pill palette-preset-btn" data-color="#234928" data-bg="#e8f0e9">
                                        <span class="rounded-circle" style="width: 18px; height: 18px; background-color: #234928;"></span>
                                        <span class="small fw-semibold">07 Experience</span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Vista Previa en Vivo (Estilo /pedido)</label>
                                <div class="card border shadow-sm p-3 rounded-4" id="livePreviewCard" style="background: #ffffff; border-top: 5px solid #275c34 !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge fw-bold fs-16 px-2 py-1" id="previewOrderNum" style="background-color: #275c34; color: #fff;">01</span>
                                            <div>
                                                <small class="text-uppercase fw-semibold" style="color: #275c34; font-size: 0.7rem;">Paquete</small>
                                                <div class="fw-bold fs-15 text-uppercase" id="previewTitle" style="color: #275c34;">NOMBRE PAQUETE</div>
                                            </div>
                                        </div>
                                        <span class="badge rounded-pill px-2 py-1" id="previewBadgeGlasses" style="background-color: #275c34; color: #fff; font-size: 0.72rem;">120 Vasos</span>
                                    </div>
                                    <div class="small text-muted mb-2 fst-italic" id="previewTagline">Subtítulo o alcance general del paquete...</div>
                                    <div class="d-flex justify-content-between align-items-center p-2 rounded-3 mt-2" id="previewFooterBox" style="background-color: #e9edea;">
                                        <div class="d-flex align-items-center gap-1 small">
                                            <i class="ri-group-line" id="previewIconGroup" style="color: #275c34;"></i>
                                            <span class="fw-semibold text-dark" id="previewIdeal">100 a 180 personas</span>
                                        </div>
                                        <span class="badge fw-bold px-2 py-1" id="previewCostBadge" style="background-color: #275c34; color: #fff;">Ver Precio</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- TAB 4: PRECIOS & TRAMOS --}}
                    <div class="tab-pane fade" id="tab-pricing" role="tabpanel">
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label for="price_mode" class="form-label small fw-bold text-muted text-uppercase">
                                    Modalidad de Precio <span class="text-danger">*</span>
                                </label>
                                <select name="price_mode" id="price_mode" class="form-select border-2 fw-semibold" required>
                                    <option value="tiered" selected>Escalonado por Tramos de Invitados (Recomendado)</option>
                                    <option value="per_guest">Por Persona / Invitado</option>
                                    <option value="fixed">Precio Fijo Total</option>
                                </select>
                            </div>
                            <div class="col-md-7" id="box_base_price" style="display: none;">
                                <label for="base_price" class="form-label small fw-bold text-muted text-uppercase" id="label_base_price">
                                    Precio Base ({{ $signo }})
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-dark fw-bold">{{ $signo }}</span>
                                    <input type="number" step="0.50" id="base_price" name="base_price" class="form-control fw-bold fs-16" value="0.00">
                                </div>
                                <small class="text-muted">Aplica directamente cuando no se configuran tramos de invitados.</small>
                            </div>
                        </div>
                        {{-- TIER SECTION --}}
                        <div id="box_tiers_section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><i class="ri-stack-line text-primary me-1"></i> Configuración de Tramos de Invitados (Tiers)</h6>
                                    <p class="text-muted small mb-0">Rangos de invitados con su costo total o unitario sin solapamiento.</p>
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
                                <i class="ri-lightbulb-line me-1"></i> Ejemplo: Tramo de 100 a 180 invitados a S/ 4,200.00 como precio total del tramo.
                            </div>
                        </div>
                    </div>
                    {{-- TAB 5: ESPECIFICACIONES & COMPONENTES --}}
                    <div class="tab-pane fade" id="tab-specs" role="tabpanel">
                        <div class="row g-4">
                            {{-- SPECIFICATIONS WEB TAGS --}}
                            <div class="col-lg-6">
                                <div class="card border p-3 rounded-3 h-100">
                                    <h6 class="fw-bold text-dark mb-1">
                                        <i class="ri-check-double-line text-success me-1"></i> Especificaciones del Flyer Web ("INCLUYE:")
                                    </h6>
                                    <p class="text-muted small mb-3">Puntos clave que aparecen listados con viñetas en la tarjeta de /pedido.</p>
                                    <div class="input-group mb-2">
                                        <input type="text" id="new_feature_input" class="form-control" placeholder="Escriba especificación (ej: 1 Bartender + 1 asistente)">
                                        <button type="button" class="btn btn-primary fw-bold" id="btnAddFeature">
                                            <i class="ri-add-line me-1"></i> Añadir
                                        </button>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted fw-semibold d-block mb-1">Sugerencias rápidas:</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="1 Bartender + 1 asistente">+ Bartender + Asistente</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="Barra móvil">+ Barra móvil</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="Cristalería variada">+ Cristalería variada</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="Hielo Gourmet">+ Hielo Gourmet</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="Insumos">+ Insumos</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="Utensilios de bar">+ Utensilios de bar</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="4 - 5 opciones de cócteles">+ 4 - 5 opciones</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="Personal adicional de apoyo">+ Personal de apoyo</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-feature-preset" data-text="5 horas de servicio">+ 5h de servicio</button>
                                        </div>
                                    </div>
                                    <div class="border rounded-3 p-2 bg-light min-vh-15" id="featuresContainer" style="min-height: 140px;">
                                        <div class="text-center text-muted small py-3" id="featuresEmptyMessage">No hay especificaciones añadidas. Use el campo superior o las sugerencias.</div>
                                        <div class="d-flex flex-column gap-2" id="featuresBadgesList"></div>
                                    </div>
                                    <input type="hidden" name="features" id="features_json" value="[]">
                                </div>
                            </div>
                            {{-- INVENTORY & COCKTAIL LINKING --}}
                            <div class="col-lg-6">
                                <div class="card border p-3 rounded-3 h-100">
                                    <h6 class="fw-bold text-dark mb-1">
                                        <i class="ri-archive-line text-primary me-1"></i> Vinculación con Inventario & Cócteles
                                    </h6>
                                    <p class="text-muted small mb-3">Descarga automática de stock y cócteles al confirmar contratos de este paquete.</p>
                                    <div class="row g-2 align-items-end mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Tipo</label>
                                            <select id="item_picker_type" class="form-select form-select-sm" onchange="$('.picker-opt').hide(); $('#picker_' + this.value).show();">
                                                <option value="service">Servicio / Equipo</option>
                                                <option value="cocktail">Cóctel de Carta</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8 picker-opt" id="picker_service">
                                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Equipo / Servicio</label>
                                            <select id="picker_product_id" class="form-select form-select-sm">
                                                <option value="">-- Seleccionar --</option>
                                                @foreach ($products as $prod)
                                                    <option value="{{ $prod->id }}" data-precio="{{ $prod->precio_venta }}">
                                                        {{ $prod->descripcion }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-8 picker-opt" id="picker_cocktail" style="display: none;">
                                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Cóctel de Carta</label>
                                            <select id="picker_cocktail_id" class="form-select form-select-sm">
                                                <option value="">-- Seleccionar --</option>
                                                @foreach ($cocktails as $c)
                                                    <option value="{{ $c->id }}" data-precio="{{ $c->precio }}">
                                                        {{ $c->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        {{-- Button kept in DOM but hidden; items auto-add on dropdown change --}}
                                        <div class="col-md-2" style="display:none;">
                                            <button type="button" class="btn btn-sm btn-primary w-100 fw-bold" id="btnAddItemRow">
                                                <i class="ri-add-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Componente</th>
                                                    <th style="width: 80px;">Cant.</th>
                                                    <th style="width: 50px;" class="text-center">Quitar</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableItemsBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- TAB 6: FOTO / CUTOUT CÓCTEL --}}
                    <div class="tab-pane fade" id="tab-image" role="tabpanel">
                        <div class="row g-4 align-items-center">
                            <div class="col-md-8">
                                <h6 class="fw-bold text-dark mb-2">Seleccionar Ilustración Preset de Cóctel</h6>
                                <p class="text-muted small mb-3">Elija una de las 7 ilustraciones con transparencia oficial de los flyers Shipibo:</p>
                                <div class="row g-2 mb-4" id="presetCutoutsContainer">
                                    @for ($i = 1; $i <= 7; $i++)
                                        @php $padded = sprintf('%02d', $i); @endphp
                                        <div class="col-4 col-sm-3 col-md-3">
                                            <div class="preset-cutout-item border rounded-3 p-2 text-center bg-white shadow-sm cursor-pointer" data-preset="img/packages/pkg-{{ $padded }}.png" style="cursor: pointer; transition: all 0.2s;">
                                                <img src="{{ asset('img/packages/pkg-' . $padded . '.png') }}" class="img-fluid" style="height: 70px; object-fit: contain;">
                                                <div class="small fw-bold text-muted mt-1">Preset {{ $padded }}</div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                                <h6 class="fw-bold text-dark mb-2">O Subir Archivo Propio</h6>
                                <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                                <small class="text-muted">Formatos JPG, PNG, WEBP con transparencia (Máx 4MB).</small>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Imagen Actual</label>
                                <div class="border rounded-4 p-3 bg-light d-flex align-items-center justify-content-center shadow-sm" style="height: 180px;">
                                    <img id="image_current_preview" src="{{ asset('img/packages/pkg-01.png') }}" class="img-fluid" style="max-height: 150px; object-fit: contain;">
                                </div>
                            </div>
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
