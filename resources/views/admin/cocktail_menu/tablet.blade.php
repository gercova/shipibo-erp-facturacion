<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Carta Digital de C&oacute;cteles &bull; {{ $business->nombre_comercial ?? 'HS Coctelería' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.php" rel="stylesheet" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css';">

    <style>
        :root {
            --bg-deep: #080c14;
            --bg-card: rgba(18, 25, 41, 0.85);
            --bg-card-hover: rgba(28, 38, 62, 0.95);
            --gold-primary: #d97706;
            --gold-light: #fbbf24;
            --gold-accent: #f59e0b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-glass: rgba(245, 158, 11, 0.18);
            --border-subtle: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-deep);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(217, 119, 6, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(245, 158, 11, 0.06) 0%, transparent 45%);
            background-attachment: fixed;
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding-bottom: 60px;
            overflow-x: hidden;
            user-select: none;
        }

        .font-serif {
            font-family: 'Cinzel', serif;
            letter-spacing: 1px;
        }

        /* Topbar Header */
        .tablet-topbar {
            background: rgba(8, 12, 20, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-glass);
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 12px 24px;
        }

        /* Filter Pills */
        .category-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-subtle);
            color: var(--text-muted);
            border-radius: 50px;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.25s ease;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .category-pill:hover,
        .category-pill.active {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #ffffff;
            border-color: #f59e0b;
            box-shadow: 0 4px 15px rgba(217, 119, 6, 0.35);
            transform: translateY(-1px);
        }

        .categories-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: none;
        }
        .categories-scroll::-webkit-scrollbar { display: none; }

        /* Search input */
        .search-container {
            position: relative;
            min-width: 240px;
        }
        .search-input {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            color: #fff;
            padding: 8px 18px 8px 40px;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--gold-accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
            color: #fff;
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        /* Toggle Switch Precios */
        .price-toggle-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            padding: 6px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-check-input:checked {
            background-color: var(--gold-primary);
            border-color: var(--gold-primary);
        }

        /* Cocktail Cards */
        .cocktail-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .cocktail-card:hover {
            transform: translateY(-4px);
            border-color: rgba(245, 158, 11, 0.45);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5), 0 0 20px rgba(217, 119, 6, 0.18);
        }

        .cocktail-card:active {
            transform: scale(0.98);
        }

        .cocktail-image-box {
            position: relative;
            width: 100%;
            height: 220px;
            background-color: #121826;
            overflow: hidden;
        }

        .cocktail-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .cocktail-card:hover .cocktail-image {
            transform: scale(1.05);
        }

        .cocktail-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(18, 25, 41, 1) 0%, rgba(18, 25, 41, 0.2) 60%, transparent 100%);
        }

        .cocktail-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(8px);
        }

        .badge-autor {
            background: rgba(217, 119, 6, 0.9);
            color: #fff;
            border: 1px solid rgba(251, 191, 36, 0.5);
        }

        .badge-destacado {
            background: rgba(16, 185, 129, 0.9);
            color: #fff;
            border: 1px solid rgba(52, 211, 153, 0.5);
        }

        .cocktail-price-tag {
            position: absolute;
            bottom: 12px;
            right: 14px;
            background: rgba(8, 12, 20, 0.85);
            backdrop-filter: blur(8px);
            border: 1px solid var(--gold-accent);
            color: var(--gold-light);
            font-weight: 800;
            font-size: 15px;
            padding: 4px 14px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .cocktail-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cocktail-title {
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .cocktail-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            color: #cbd5e1;
            padding: 4px 10px;
            border-radius: 50px;
        }

        /* Modal Touch de Detalle */
        .modal-touch .modal-content {
            background: #0f172a;
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            color: #fff;
            overflow: hidden;
        }

        .modal-touch-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }

        /* Hide price class */
        body.hide-prices .cocktail-price-tag,
        body.hide-prices .modal-price-box {
            display: none !important;
        }
    </style>
</head>
<body>

    {{-- BARRA SUPERIOR DE CONTROL PARA TABLET --}}
    <header class="tablet-topbar">
        <div class="container-fluid px-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                {{-- Logo y Título --}}
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-box bg-gradient text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #b45309, #d97706); width: 44px; height: 44px;">
                        <i class="ri-goblet-line fs-22"></i>
                    </div>
                    <div>
                        <h4 class="font-serif fw-bold text-white mb-0" style="letter-spacing: 1px;">
                            {{ $business->nombre_comercial ?? 'HS COCTELERÍA' }}
                        </h4>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-subtle text-warning fs-11 px-2 py-0">CARTA DE EVENTOS</span>
                            <small class="text-muted fs-12">{{ count($allCocktails) }} Opciones Disponibles</small>
                        </div>
                    </div>
                </div>

                {{-- Controles: Buscador, Switch Precios y Salir --}}
                <div class="d-flex align-items-center gap-3">
                    {{-- Buscador instantáneo --}}
                    <div class="search-container d-none d-sm-block">
                        <i class="ri-search-line search-icon"></i>
                        <input type="text" id="tabletSearchInput" class="search-input" placeholder="Buscar cóctel, pisco, gin...">
                    </div>

                    {{-- Switch Mostrar/Ocultar Precios --}}
                    <div class="price-toggle-box" title="Alternar visibilidad de precios para clientes o invitados">
                        <i class="ri-money-dollar-circle-line text-warning fs-18"></i>
                        <label class="form-check-label small fw-semibold text-white mb-0" for="switchShowPrices" style="cursor: pointer;">
                            Precios
                        </label>
                        <div class="form-check form-switch mb-0 ms-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="switchShowPrices" checked>
                        </div>
                    </div>

                    {{-- Botón Pantalla Completa --}}
                    <button type="button" class="btn btn-outline-light rounded-circle p-0 d-flex align-items-center justify-content-center" id="btnFullscreen" style="width: 40px; height: 40px;" title="Pantalla Completa">
                        <i class="ri-fullscreen-line fs-18"></i>
                    </button>

                    {{-- Botón Volver a Administración --}}
                    <a href="{{ route('admin.cocktail_menu.index') }}" class="btn btn-outline-secondary rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Volver al Panel">
                        <i class="ri-settings-4-line fs-18"></i>
                    </a>
                </div>
            </div>

            {{-- Buscador para móviles/pantallas angostas --}}
            <div class="d-block d-sm-none mt-3">
                <div class="search-container w-100">
                    <i class="ri-search-line search-icon"></i>
                    <input type="text" id="tabletSearchInputMobile" class="search-input w-100" placeholder="Buscar cóctel, insumo...">
                </div>
            </div>

            {{-- Pestañas de Categorías --}}
            <div class="categories-scroll mt-3 pt-2">
                <div class="category-pill active" data-category-id="all">
                    <i class="ri-apps-2-line"></i> Todos los Cócteles ({{ count($allCocktails) }})
                </div>

                @foreach ($categories as $cat)
                    <div class="category-pill" data-category-id="{{ $cat->id }}">
                        <i class="{{ $cat->icono ?: 'ri-goblet-line' }}"></i> {{ $cat->nombre }} ({{ $cat->activeItems->count() }})
                    </div>
                @endforeach
            </div>
        </div>
    </header>

    {{-- GRILLA DE CÓCTELES --}}
    <main class="container-fluid px-3 px-md-4 py-4">
        <div class="row g-3 g-md-4" id="cocktailsGrid">
            @forelse ($allCocktails as $item)
                <div class="col-12 col-sm-6 col-md-4 col-xl-3 cocktail-item-wrapper" 
                    data-category-id="{{ $item->menu_category_id }}"
                    data-name="{{ mb_strtolower($item->nombre) }}"
                    data-desc="{{ mb_strtolower($item->descripcion_corta . ' ' . $item->garnish . ' ' . $item->cristaleria) }}">
                    
                    <div class="cocktail-card" onclick="openCocktailDetail({{ json_encode($item) }})">
                        {{-- Foto del Cóctel --}}
                        <div class="cocktail-image-box">
                            <img src="{{ $item->image_url }}" alt="{{ $item->nombre }}" class="cocktail-image" loading="lazy">
                            <div class="cocktail-overlay"></div>

                            {{-- Badges --}}
                            @if ($item->es_autor)
                                <span class="cocktail-badge badge-autor">
                                    <i class="ri-magic-line me-1"></i> De Autor
                                </span>
                            @elseif ($item->destacado)
                                <span class="cocktail-badge badge-destacado">
                                    <i class="ri-star-fill me-1"></i> Recomendado
                                </span>
                            @endif

                            {{-- Precio flotante --}}
                            <span class="cocktail-price-tag">
                                {{ $signo }} {{ number_format((float) $item->precio, 2) }}
                            </span>
                        </div>

                        {{-- Contenido Textual --}}
                        <div class="cocktail-body">
                            <div>
                                <span class="text-warning text-uppercase small fw-bold d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                                    {{ $item->category ? $item->category->nombre : 'Coctelería' }}
                                </span>
                                <h5 class="cocktail-title">{{ $item->nombre }}</h5>
                                <p class="cocktail-desc">{{ $item->descripcion_corta ?: 'Receta balanceada preparada al momento por nuestro bartender.' }}</p>
                            </div>

                            <div class="d-flex flex-wrap gap-1 mt-auto pt-2 border-top border-secondary border-opacity-25">
                                @if ($item->cristaleria)
                                    <span class="tag-pill">
                                        <i class="ri-goblet-line text-warning"></i> {{ $item->cristaleria }}
                                    </span>
                                @endif
                                @if ($item->garnish)
                                    <span class="tag-pill">
                                        <i class="ri-sparkling-line text-warning"></i> {{ Str::limit($item->garnish, 24) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 py-5 text-center">
                    <i class="ri-goblet-line fs-48 text-muted d-block mb-3"></i>
                    <h4 class="text-white fw-bold">No hay cócteles configurados en la carta.</h4>
                    <p class="text-muted">Ingresa al panel administrativo para registrar las opciones de cócteles del evento.</p>
                </div>
            @endforelse
        </div>

        {{-- Estado sin resultados de búsqueda --}}
        <div id="noResultsState" class="py-5 text-center d-none">
            <i class="ri-search-eye-line fs-48 text-warning d-block mb-3"></i>
            <h4 class="text-white fw-bold">No se encontraron cócteles para esta búsqueda</h4>
            <p class="text-muted">Prueba buscando por otro nombre o insumo principal.</p>
        </div>
    </main>

    {{-- MODAL DE DETALLE TÁCTIL --}}
    <div class="modal fade modal-touch" id="modalCocktailDetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="position-relative">
                    <img id="detailModalImage" src="" alt="Cóctel" class="modal-touch-image">
                    <button type="button" class="btn btn-dark rounded-circle position-absolute top-0 end-0 m-3 d-flex align-items-center justify-content-center" data-bs-dismiss="modal" style="width: 40px; height: 40px; background: rgba(0,0,0,0.65); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="ri-close-line fs-20 text-white"></i>
                    </button>
                    <div class="position-absolute bottom-0 start-0 w-100 p-4" style="background: linear-gradient(to top, #0f172a 15%, transparent 100%);">
                        <span id="detailModalCategory" class="badge bg-warning text-dark fw-bold px-3 py-1 text-uppercase fs-12 mb-2"></span>
                        <h2 id="detailModalTitle" class="font-serif fw-bold text-white mb-0"></h2>
                    </div>
                </div>

                <div class="p-4 pt-2">
                    {{-- Precio en modal --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex gap-2" id="detailBadgesContainer"></div>
                        <div class="modal-price-box">
                            <span class="fs-22 fw-extrabold text-warning" id="detailModalPrice"></span>
                        </div>
                    </div>

                    {{-- Notas de cata --}}
                    <div class="mb-4">
                        <h6 class="text-uppercase small fw-bold text-muted mb-2">
                            <i class="ri-sparkling-fill text-warning me-1"></i> Experiencia & Notas de Cata
                        </h6>
                        <p id="detailModalDesc" class="fs-15 text-light leading-relaxed mb-0"></p>
                    </div>

                    {{-- Cristalería y Decoración --}}
                    <div class="row g-3 p-3 rounded-3" style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--border-subtle);">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Cristaler&iacute;a de Servicio</span>
                            <span id="detailModalGlass" class="fw-bold text-white fs-14"></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Decoraci&oacute;n / Garnish</span>
                            <span id="detailModalGarnish" class="fw-bold text-white fs-14"></span>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-outline-warning px-5 py-2 fw-bold rounded-pill" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.php" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';"></script>

    <script>
        // 1. Alternancia de Precios (Mostrar / Ocultar)
        const priceSwitch = document.getElementById('switchShowPrices');
        const savedPriceState = localStorage.getItem('hs_cocktails_show_prices');

        if (savedPriceState !== null) {
            const shouldShow = savedPriceState === 'true';
            priceSwitch.checked = shouldShow;
            document.body.classList.toggle('hide-prices', !shouldShow);
        }

        priceSwitch.addEventListener('change', function() {
            const isChecked = this.checked;
            document.body.classList.toggle('hide-prices', !isChecked);
            localStorage.setItem('hs_cocktails_show_prices', isChecked);
        });

        // 2. Filtrado por Categorías
        const categoryPills = document.querySelectorAll('.category-pill');
        const items = document.querySelectorAll('.cocktail-item-wrapper');
        const noResults = document.getElementById('noResultsState');

        let currentCategory = 'all';
        let currentSearchQuery = '';

        categoryPills.forEach(pill => {
            pill.addEventListener('click', function() {
                categoryPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');

                currentCategory = this.getAttribute('data-category-id');
                filterCocktails();
            });
        });

        // 3. Filtrado por Buscador
        const searchInput = document.getElementById('tabletSearchInput');
        const searchInputMobile = document.getElementById('tabletSearchInputMobile');

        function onSearchInput(val) {
            currentSearchQuery = val.trim().toLowerCase();
            filterCocktails();
        }

        if (searchInput) {
            searchInput.addEventListener('input', e => onSearchInput(e.target.value));
        }
        if (searchInputMobile) {
            searchInputMobile.addEventListener('input', e => onSearchInput(e.target.value));
        }

        function filterCocktails() {
            let visibleCount = 0;

            items.forEach(item => {
                const catId = item.getAttribute('data-category-id');
                const name = item.getAttribute('data-name') || '';
                const desc = item.getAttribute('data-desc') || '';

                const matchesCategory = (currentCategory === 'all' || catId === currentCategory);
                const matchesSearch = (!currentSearchQuery || name.includes(currentSearchQuery) || desc.includes(currentSearchQuery));

                if (matchesCategory && matchesSearch) {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            });

            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }

        // 4. Modal Táctil de Detalle de Cóctel
        const detailModal = new bootstrap.Modal(document.getElementById('modalCocktailDetail'));

        function openCocktailDetail(item) {
            document.getElementById('detailModalImage').src = item.image_url || '';
            document.getElementById('detailModalTitle').textContent = item.nombre || '';
            document.getElementById('detailModalCategory').textContent = item.category ? item.category.nombre : 'Coctelería';
            document.getElementById('detailModalDesc').textContent = item.descripcion_corta || 'Receta balanceada preparada con insumos premium al momento por nuestros bartenders.';
            document.getElementById('detailModalGlass').textContent = item.cristaleria || 'Vaso de Coctelería Especial';
            document.getElementById('detailModalGarnish').textContent = item.garnish || 'Guarnición fresca del día';

            const signo = "{{ $signo }}";
            const precioFormatted = item.precio ? signo + ' ' + parseFloat(item.precio).toFixed(2) : '';
            document.getElementById('detailModalPrice').textContent = precioFormatted;

            const badgesContainer = document.getElementById('detailBadgesContainer');
            badgesContainer.innerHTML = '';
            if (item.es_autor) {
                badgesContainer.innerHTML += '<span class="badge badge-autor"><i class="ri-magic-line me-1"></i> Cóctel de Autor</span>';
            }
            if (item.destacado) {
                badgesContainer.innerHTML += '<span class="badge badge-destacado"><i class="ri-star-fill me-1"></i> Recomendación de Barra</span>';
            }

            detailModal.show();
        }

        // 5. Botón Pantalla Completa
        document.getElementById('btnFullscreen').addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    </script>
</body>
</html>
