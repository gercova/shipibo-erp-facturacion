{{-- MODAL CREAR / EDITAR CÓCTEL --}}
<div class="modal fade" id="modalCocktail" tabindex="-1" aria-labelledby="modalCocktailTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="formCocktail" class="modal-content border-0 shadow-lg" enctype="multipart/form-data" onsubmit="event.preventDefault()">
            @csrf
            <input type="hidden" name="id" id="cocktail_id" value="">

            <div class="modal-header bg-dark text-white border-bottom-0 py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="modalCocktailTitle">
                    <i class="ri-goblet-line text-warning me-2 fs-20"></i> <span id="modalCocktailActionTitle">Nuevo Cóctel en Carta</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    {{-- Vincular con producto de servicio (opcional) --}}
                    <div class="col-12">
                        <label for="product_id" class="form-label small fw-bold text-muted text-uppercase">
                            Vincular a Producto de Catálogo (Opcional)
                        </label>
                        <select name="product_id" id="product_id" class="form-select">
                            <option value="">-- Sin vincular / Entrada personalizada --</option>
                            @foreach ($serviceProducts as $prod)
                                <option value="{{ $prod->id }}" data-nombre="{{ $prod->descripcion }}" data-precio="{{ $prod->precio_venta }}">
                                    {{ $prod->descripcion }} (S/ {{ number_format((float) $prod->precio_venta, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Si seleccionas un producto de servicio existente, se autocompletarán el nombre y precio.</small>
                    </div>

                    {{-- Categoría y Nombre --}}
                    <div class="col-md-5">
                        <label for="menu_category_id" class="form-label small fw-bold text-muted text-uppercase">
                            Categor&iacute;a del Men&uacute; <span class="text-danger">*</span>
                        </label>
                        <select name="menu_category_id" id="menu_category_id" class="form-select border-2" required>
                            <option value="">Seleccione categor&iacute;a...</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-7">
                        <label for="nombre" class="form-label small fw-bold text-muted text-uppercase">
                            Nombre del C&oacute;ctel <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" class="form-control border-2 text-uppercase fw-semibold" placeholder="Ej: PISCO SOUR CATEDRAL" required>
                    </div>

                    {{-- Descripción corta --}}
                    <div class="col-12">
                        <label for="descripcion_corta" class="form-label small fw-bold text-muted text-uppercase">
                            Descripci&oacute;n / Notas de Cata
                        </label>
                        <textarea id="descripcion_corta" name="descripcion_corta" class="form-control" rows="2" placeholder="Resumen atractivo para la tablet (ingredientes principales, equilibrio, frescura)..."></textarea>
                    </div>

                    {{-- Cristalería y Garnish --}}
                    <div class="col-md-6">
                        <label for="cristaleria" class="form-label small fw-bold text-muted text-uppercase">
                            Cristaler&iacute;a Recomendada
                        </label>
                        <input type="text" id="cristaleria" name="cristaleria" class="form-control" placeholder="Ej: Copa Coupe, Vaso Collins, Kero, Copa Balón">
                    </div>

                    <div class="col-md-6">
                        <label for="garnish" class="form-label small fw-bold text-muted text-uppercase">
                            Decoraci&oacute;n / Garnish
                        </label>
                        <input type="text" id="garnish" name="garnish" class="form-control" placeholder="Ej: Twist de lima, gotas de amargo, hojas de hierbabuena">
                    </div>

                    {{-- Precio, Orden y Foto --}}
                    <div class="col-md-4">
                        <label for="precio" class="form-label small fw-bold text-muted text-uppercase text-success">
                            Precio en Carta (S/)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white border-success fw-bold">{{ $signo }}</span>
                            <input type="number" id="precio" name="precio" class="form-control border-success shadow-sm" step="0.50" min="0" value="0.00">
                        </div>
                        <small class="text-muted">Se oculta en la tablet si se desactiva "Mostrar Precios".</small>
                    </div>

                    <div class="col-md-3">
                        <label for="orden" class="form-label small fw-bold text-muted text-uppercase">
                            Orden
                        </label>
                        <input type="number" id="orden" name="orden" class="form-control" min="0" value="1">
                    </div>

                    <div class="col-md-5">
                        <label for="imagen" class="form-label small fw-bold text-muted text-uppercase">
                            Fotograf&iacute;a del C&oacute;ctel
                        </label>
                        <input type="file" id="imagen" name="imagen" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                        <small class="text-muted">JPG, PNG o WEBP (Máx 4MB).</small>
                    </div>

                    {{-- Preview imagen actual --}}
                    <div class="col-12" id="containerCocktailImagePreview" style="display: none;">
                        <div class="d-flex align-items-center gap-3 p-2 bg-light rounded border">
                            <img id="cocktailImagePreview" src="" alt="Preview" class="rounded shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                            <div>
                                <span class="small fw-bold text-dark d-block">Imagen actual</span>
                                <span class="small text-muted">Seleccione una nueva imagen si desea reemplazarla.</span>
                            </div>
                        </div>
                    </div>

                    {{-- Switches de configuración --}}
                    <div class="col-12">
                        <hr class="text-muted opacity-25 my-2">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch p-2 bg-light rounded border px-4">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="es_autor" name="es_autor" value="1">
                                    <label class="form-check-label small fw-bold text-dark mb-0" for="es_autor">
                                        <i class="ri-magic-line text-warning me-1"></i> C&oacute;ctel de Autor
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check form-switch p-2 bg-light rounded border px-4">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="destacado" name="destacado" value="1">
                                    <label class="form-check-label small fw-bold text-dark mb-0" for="destacado">
                                        <i class="ri-star-line text-primary me-1"></i> Destacado en Tablet
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check form-switch p-2 bg-light rounded border px-4">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="activo" name="activo" value="1" checked>
                                    <label class="form-check-label small fw-bold text-dark mb-0" for="activo">
                                        <i class="ri-eye-line text-success me-1"></i> Visible en Carta
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4 fw-bold text-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm" id="btnSaveCocktail">
                    <span class="btn-text"><i class="ri-save-line me-1"></i> Guardar C&oacute;ctel</span>
                    <span class="btn-loading d-none"><i class="fas fa-spinner fa-spin me-1"></i> Guardando...</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL GESTIONAR CATEGORÍAS --}}
<div class="modal fade" id="modalCategories" tabindex="-1" aria-labelledby="modalCategoriesTitle" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="modalCategoriesTitle">
                    <i class="ri-folder-settings-line text-primary me-2"></i> Categor&iacute;as de la Carta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                {{-- Formulario para nueva categoría --}}
                <form id="formCategory" class="mb-4 p-3 bg-light rounded border" onsubmit="event.preventDefault()">
                    @csrf
                    <input type="hidden" name="id" id="category_id" value="">
                    <h6 class="fw-bold text-dark mb-2" id="categoryFormTitle">Agregar Nueva Categor&iacute;a</h6>
                    <div class="mb-2">
                        <input type="text" id="cat_nombre" name="nombre" class="form-control" placeholder="Nombre (Ej: Cócteles de Autor, Clásicos, etc.)" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" id="cat_descripcion" name="descripcion" class="form-control form-control-sm" placeholder="Breve descripción opcional">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <input type="number" id="cat_orden" name="orden" class="form-control form-control-sm" placeholder="Orden (ej: 1)" value="1">
                        </div>
                        <div class="col-6">
                            <input type="text" id="cat_icono" name="icono" class="form-control form-control-sm" placeholder="Ícono (ri-goblet-line)" value="ri-goblet-line">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-light" id="btnCancelCategoryEdit" style="display:none;">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-bold" id="btnSaveCategory">
                            <i class="ri-add-line me-1"></i> <span id="btnSaveCategoryText">Guardar Categor&iacute;a</span>
                        </button>
                    </div>
                </form>

                {{-- Lista de categorías existentes --}}
                <h6 class="fw-bold text-muted small text-uppercase mb-2">Categor&iacute;as Actuales</h6>
                <div class="list-group" id="categoryListGroup">
                    @forelse ($categories as $cat)
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 category-item-row" data-id="{{ $cat->id }}">
                            <div>
                                <span class="fw-bold text-dark">{{ $cat->nombre }}</span>
                                <small class="text-muted d-block">{{ $cat->descripcion ?: 'Sin descripción' }}</small>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-edit-cat" 
                                    data-id="{{ $cat->id }}" 
                                    data-nombre="{{ $cat->nombre }}" 
                                    data-descripcion="{{ $cat->descripcion }}" 
                                    data-orden="{{ $cat->orden }}"
                                    data-icono="{{ $cat->icono }}"
                                    title="Editar">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-cat" data-id="{{ $cat->id }}" data-nombre="{{ $cat->nombre }}" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">No hay categorías registradas.</div>
                    @endforelse
                </div>
            </div>

            <div class="modal-footer border-top-0 p-3 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
