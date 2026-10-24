<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cotiza tu Barra Móvil | Shipibo Coctelería - Tarapoto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('css/order-public.css') }}">
</head>
<body>
    {{-- Top Hero Header --}}
    <header class="hero-section text-center">
        <div class="container" style="max-width: 880px;">
            <div class="mb-3">
                <span class="hero-badge">
                    <i class="ri-cocktail-fill text-warning"></i> SHIPIBO COCTELERÍA &bull; TARAPOTO, SAN MARTÍN
                </span>
            </div>
            <h1 class="display-5 font-serif fw-bold mb-3">Cotiza tu Barra Móvil</h1>
            <p class="fs-16 opacity-90 mx-auto mb-0" style="max-width: 700px; line-height: 1.6;">
                Selecciona el paquete ideal para tu evento, elige tus cócteles favoritos y recibe una cotización con
                cálculo exacto en tiempo real.
            </p>
        </div>
    </header>

    {{-- Navigation quick links --}}
    <div class="container mt-n4" style="position: relative; z-index: 10;">
        <div class="d-flex justify-content-center">
            <nav class="nav-pills-custom">
                <a href="#section-packages" class="nav-link active"><i class="ri-gift-line"></i> Paquetes Todo Costo (01
                    - 07)</a>
                <a href="#section-barman" class="nav-link"><i class="ri-user-star-line"></i> Servicio de Barman</a>
                <a href="#section-carta" class="nav-link"><i class="ri-goblet-line"></i> Nuestra Carta</a>
                <a href="#section-condiciones" class="nav-link"><i class="ri-file-text-line"></i> Políticas y
                    Condiciones</a>
            </nav>
        </div>
    </div>

    {{-- Main Container --}}
    <main class="container py-3 mb-5" style="max-width: 1220px;">
        <form id="orderForm" onsubmit="event.preventDefault()">
            @csrf

            {{-- Honeypot anti-spam field --}}
            <input type="text" name="_hp_website" id="_hp_website" style="display:none !important;" tabindex="-1"
                autocomplete="off">
            <input type="hidden" name="package_id" id="selected_package_id" value="">

            <div class="row g-4">
                {{-- Left Content: Flyers, Carta, Barman & Terms --}}
                <div class="col-lg-7 col-xl-8">

                    {{-- SECTION 1: 7 OPEN BAR PACKAGES --}}
                    <section id="section-packages" class="mb-5">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <span
                                    class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1 rounded-pill mb-1">
                                    <i class="ri-check-double-line me-1"></i> PAQUETES A TODO COSTO
                                </span>
                                <h3 class="fw-bold font-serif text-dark mb-0">Barras Móviles Completas</h3>
                            </div>
                            <span class="text-muted small d-none d-sm-inline">Haz clic en cualquier paquete para
                                cotizar</span>
                        </div>

                        {{-- Metadata definition for the 7 flyer packages --}}
                        @php
                            $flyerMeta = [
                                'Paquete ESENCIAL' => [
                                    'num'       => '01',
                                    'theme'     => '01',
                                    'glasses'   => '100 vasos',
                                    'title'     => 'ESENCIAL',
                                    'tagline'   => 'Ideal para reuniones íntimas y celebraciones pequeñas.',
                                    'color'     => '#275c34',
                                    'light_bg'  => '#e9edea',
                                    'ideal'     => '20 a 50 invitados',
                                    'cost'      => 'S/ 1,200',
                                    'img'       => 'img/packages/pkg-01.png',
                                    'features'  => [
                                        '1 Bartender + 1 asistente',
                                        'Barra móvil',
                                        'Cristalería',
                                        'Hielo',
                                        'Insumos',
                                        'Utensilios de bar',
                                        '4 opciones',
                                    ],
                                    'default_guests' => 35,
                                ],
                                'Paquete TROPICAL' => [
                                    'num' => '02',
                                    'theme' => '02',
                                    'glasses' => '200 vasos',
                                    'title' => 'TROPICAL',
                                    'tagline' => 'Sabores frescos que transportan a los mejores destinos.',
                                    'color' => '#189e82',
                                    'light_bg' => '#e4f4f1',
                                    'ideal' => '50 a 80 invitados',
                                    'cost' => 'S/ 2,000',
                                    'img' => 'img/packages/pkg-02.png',
                                    'features' => [
                                        '1 Bartender + 1 asistente',
                                        'Barra móvil',
                                        'Cristalería',
                                        'Hielo',
                                        'Insumos',
                                        'Utensilios de bar',
                                        '5 opciones',
                                    ],
                                    'default_guests' => 65,
                                ],
                                'Paquete PREMIUM' => [
                                    'num' => '03',
                                    'theme' => '03',
                                    'glasses' => '300 vasos',
                                    'title' => 'PREMIUM',
                                    'tagline' => 'Una experiencia sofisticada con cocteles de alta calidad.',
                                    'color' => '#273e6b',
                                    'light_bg' => '#e9eef6',
                                    'ideal' => '50 a 120 invitados',
                                    'cost' => 'S/ 2,900',
                                    'img' => 'img/packages/pkg-03.png',
                                    'features' => [
                                        '1 Bartender + 1 asistente',
                                        'Barra móvil',
                                        'Cristalería variada',
                                        'Hielo Gourmet',
                                        'Insumos',
                                        'Utensilios de bar',
                                        '5 opciones',
                                    ],
                                    'default_guests' => 85,
                                ],
                                'Paquete GOLD' => [
                                    'num' => '04',
                                    'theme' => '04',
                                    'glasses' => '400 vasos',
                                    'title' => 'GOLD',
                                    'tagline' => 'Cocteles selectos para eventos que merecen algo más.',
                                    'color' => '#be8632',
                                    'light_bg' => '#f8f3e9',
                                    'ideal' => '80 a 150 invitados',
                                    'cost' => 'S/ 3,600',
                                    'img' => 'img/packages/pkg-04.png',
                                    'features' => [
                                        '1 Bartender + 1 asistente',
                                        'Barra móvil',
                                        'Cristalería variada',
                                        'Hielo Gourmet',
                                        'Insumos',
                                        'Utensilios de bar',
                                        '5 opciones',
                                    ],
                                    'default_guests' => 115,
                                ],
                                'Paquete SIGNATURE' => [
                                    'num' => '05',
                                    'theme' => '05',
                                    'glasses' => '500 vasos',
                                    'title' => 'SIGNATURE',
                                    'tagline' => 'Cocteles que elevan tu evento a otro nivel.',
                                    'color' => '#9e2d5c',
                                    'light_bg' => '#faedf2',
                                    'ideal' => '100 a 180 invitados',
                                    'cost' => 'S/ 4,200',
                                    'img' => 'img/packages/pkg-05.png',
                                    'features' => [
                                        '1 Bartender + 1 asistente',
                                        'Barra móvil',
                                        'Cristalería variada',
                                        'Hielo Gourmet',
                                        'Insumos',
                                        'Utensilios de bar',
                                        '5 - 6 opciones',
                                    ],
                                    'default_guests' => 140,
                                ],
                                'Paquete LUXURY' => [
                                    'num' => '06',
                                    'theme' => '06',
                                    'glasses' => '600 vasos',
                                    'title' => 'LUXURY',
                                    'tagline' => 'La máxima experiencia en coctelería para eventos inolvidables.',
                                    'color' => '#14728f',
                                    'light_bg' => '#e8f5f8',
                                    'ideal' => '100 a 250 invitados',
                                    'cost' => 'S/ 4,800',
                                    'img' => 'img/packages/pkg-06.png',
                                    'features' => [
                                        '1 Bartender + 1 asistente',
                                        'Barra móvil de lujo',
                                        'Cristalería variada',
                                        'Hielo Gourmet',
                                        'Insumos',
                                        'Utensilios de bar',
                                        '5 - 6 opciones',
                                    ],
                                    'default_guests' => 180,
                                ],
                                'Paquete EXPERIENCE' => [
                                    'num' => '07',
                                    'theme' => '07',
                                    'glasses' => '700 vasos',
                                    'title' => 'EXPERIENCE',
                                    'tagline' => 'Más que un open bar, una experiencia completa.',
                                    'color' => '#234928',
                                    'light_bg' => '#ecf2ed',
                                    'ideal' => '200 invitados en adelante',
                                    'cost' => 'S/ 5,300',
                                    'img' => 'img/packages/pkg-07.png',
                                    'features' => [
                                        '1 Bartender + 1 asistente',
                                        'Barra móvil de lujo',
                                        'Cristalería variada',
                                        'Hielo Gourmet',
                                        'Insumos',
                                        'Utensilios de bar',
                                        '5 - 6 opciones',
                                        'Personal adicional de apoyo',
                                    ],
                                    'default_guests' => 250,
                                ],
                            ];

                            // Filter the open bar packages (exclude barman)
                            $openBarList = $packages
                                ->filter(function ($p) {
                                    return ! $p->is_barman && ! str_contains(strtolower($p->name), 'barman');
                                })
                                ->sortBy(function ($p) {
                                    return (int) ($p->order_num ?: 99);
                                })
                                ->values();

                            $barmanPkg = $packages->first(function ($p) {
                                return $p->is_barman || str_contains(strtolower($p->name), 'barman');
                            });
                        @endphp

                        <div class="row g-4">
                            @foreach ($openBarList as $pkg)
                                @php
                                    $num = $pkg->order_num ?: ($flyerMeta[$pkg->name]['num'] ?? sprintf('%02d', $loop->iteration));
                                    $color = $pkg->color ?: ($flyerMeta[$pkg->name]['color'] ?? '#275c34');
                                    $lightBg = $pkg->light_bg ?: ($flyerMeta[$pkg->name]['light_bg'] ?? '#e9edea');
                                    $glasses = $pkg->badge_glasses ?: ($flyerMeta[$pkg->name]['glasses'] ?? 'Vasos');
                                    $title = Str::replace('Paquete ', '', $pkg->name);
                                    $tagline = $pkg->tagline ?: ($flyerMeta[$pkg->name]['tagline'] ?? $pkg->description);
                                    $ideal = $pkg->ideal_capacity ?: ($flyerMeta[$pkg->name]['ideal'] ?? $pkg->guest_range_label);
                                    $features = (!empty($pkg->features) && is_array($pkg->features))
                                        ? $pkg->features
                                        : ($flyerMeta[$pkg->name]['features'] ?? [
                                            '1 Bartender + 1 asistente',
                                            'Barra móvil',
                                            'Cristalería',
                                            'Hielo',
                                            'Insumos',
                                            'Utensilios de bar',
                                        ]);
                                    $defaultGuests = $pkg->default_guests ?: ($flyerMeta[$pkg->name]['default_guests'] ?? $pkg->min_guests);
                                    $img = $pkg->image ? $pkg->image_url : ($flyerMeta[$pkg->name]['img'] ?? 'img/packages/pkg-01.png');

                                    if ($pkg->price_mode === 'tiered' && $pkg->priceTiers && $pkg->priceTiers->isNotEmpty()) {
                                        $minTierPrice = $pkg->priceTiers->min('price');
                                        $cost = $signo . ' ' . number_format((float) $minTierPrice, 0);
                                    } elseif ($pkg->base_price > 0) {
                                        $cost = $signo . ' ' . number_format((float) $pkg->base_price, 0) . ($pkg->price_mode === 'per_guest' ? ' / inv.' : '');
                                    } else {
                                        $cost = $flyerMeta[$pkg->name]['cost'] ?? 'Consultar';
                                    }

                                    $meta = [
                                        'num'       => $num,
                                        'theme'     => sprintf('%02d', ((int) $num) % 7 ?: 7),
                                        'glasses'   => $glasses,
                                        'title'     => $title,
                                        'tagline'   => $tagline,
                                        'color'     => $color,
                                        'light_bg'  => $lightBg,
                                        'ideal'     => $ideal,
                                        'cost'      => $cost,
                                        'img'       => $img,
                                        'features'  => $features,
                                        'default_guests' => $defaultGuests,
                                    ];
                                    $isFullWidth = $meta['num'] === '07';
                                @endphp

                                <div class="{{ $isFullWidth ? 'col-12' : 'col-md-6' }}">
                                    <div class="flyer-card pkg-theme-{{ $meta['theme'] }}" data-id="{{ $pkg->id }}"
                                        data-name="{{ $pkg->name }}" data-glasses="{{ $meta['glasses'] }}"
                                        data-min="{{ $pkg->min_guests }}" data-max="{{ $pkg->max_guests ?: 999 }}"
                                        data-default-guests="{{ $meta['default_guests'] }}"
                                        data-color="{{ $meta['color'] }}" data-img="{{ asset($meta['img']) }}">
                                        {{-- Header with number badge and title --}}
                                        <div class="flyer-card-header">
                                            <div class="d-flex align-items-center">
                                                <div class="badge-box">
                                                    <span class="badge-number"
                                                        style="color: {{ $meta['color'] }};">{{ $meta['num'] }}</span>
                                                    <span class="badge-pill-glasses"
                                                        style="background-color: {{ $meta['color'] }};">{{ $meta['glasses'] }}</span>
                                                </div>
                                                <div class="package-title-group">
                                                    <div class="package-label-sub"
                                                        style="color: {{ $meta['color'] }}; opacity: 0.85;">Paquete
                                                    </div>
                                                    <div class="package-title-main"
                                                        style="color: {{ $meta['color'] }};">{{ $meta['title'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Tagline --}}
                                        <div class="package-tagline">
                                            {{ $meta['tagline'] }}
                                        </div>

                                        {{-- Body with items and drink cutout --}}
                                        <div class="flyer-card-body">
                                            <div class="flex-grow-1">
                                                <div class="includes-title" style="color: {{ $meta['color'] }};">
                                                    INCLUYE:</div>
                                                <ul class="includes-list">
                                                    @foreach ($meta['features'] as $f)
                                                        <li>
                                                            @if (str_contains(strtolower($f), 'bartender'))
                                                                <i class="ri-user-star-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @elseif(str_contains(strtolower($f), 'barra'))
                                                                <i class="ri-store-2-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @elseif(str_contains(strtolower($f), 'cristaler'))
                                                                <i class="ri-goblet-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @elseif(str_contains(strtolower($f), 'hielo'))
                                                                <i class="ri-snowflake-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @elseif(str_contains(strtolower($f), 'insumo'))
                                                                <i class="ri-drinks-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @elseif(str_contains(strtolower($f), 'utensilio'))
                                                                <i class="ri-cup-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @elseif(str_contains(strtolower($f), 'opcion'))
                                                                <i class="ri-checkbox-circle-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @elseif(str_contains(strtolower($f), 'apoyo'))
                                                                <i class="ri-team-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @else
                                                                <i class="ri-check-line"
                                                                    style="color: {{ $meta['color'] }};"></i>
                                                            @endif
                                                            <span>{{ $f }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <div class="cocktail-cutout-container">
                                                <img src="{{ asset($meta['img']) }}" alt="{{ $pkg->name }}"
                                                    class="cocktail-cutout-img">
                                            </div>
                                        </div>

                                        {{-- Footer with ideal guests and cost badge --}}
                                        <div class="flyer-card-footer">
                                            <div class="footer-left-guests"
                                                style="background-color: {{ $meta['light_bg'] }};">
                                                <i class="ri-group-line" style="color: {{ $meta['color'] }};"></i>
                                                <div>
                                                    <div
                                                        style="font-size: 0.72rem; color: #66756c; text-transform: uppercase;">
                                                        Ideal para</div>
                                                    <div class="fw-bold" style="color: #1a271f;">{{ $meta['ideal'] }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="footer-right-cost"
                                                style="background-color: {{ $meta['color'] }};">
                                                <span class="cost-label-sub">Costo:</span>
                                                <span class="cost-amount-main">{{ $meta['cost'] }}</span>
                                            </div>
                                        </div>

                                        {{-- Selection button --}}
                                        <button type="button" class="card-select-btn">
                                            <i class="ri-checkbox-circle-line fs-16 check-icon"></i>
                                            <span class="select-text">Elegir este Paquete</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- SECTION 2: SERVICIO DE BARMAN (Screenshot 3) --}}
                    @if ($barmanPkg)
                        @php
                            $barmanColor = $barmanPkg->color ?: '#be8632';
                            $barmanPrice = $barmanPkg->base_price > 0 ? $signo . ' ' . number_format($barmanPkg->base_price, 0) : 'S/ 600';
                            $barmanMax = $barmanPkg->max_guests ?: 100;
                            $barmanIdeal = $barmanPkg->ideal_capacity ?: "máximo {$barmanMax} personas";
                            $barmanImg = $barmanPkg->image ? $barmanPkg->image_url : asset('img/packages/pkg-01.png');
                        @endphp
                        <section id="section-barman" class="mb-5">
                            <div class="text-center mb-3">
                                <h3 class="fw-bold font-serif text-dark mb-1">{{ $barmanPkg->name }}</h3>
                                <p class="text-muted fs-15 mb-3">{{ $barmanPkg->tagline ?: 'Tu pones los insumos y nosotros nos encargamos de lo demás.' }}</p>
                            </div>

                            <div class="barman-service-card" data-id="{{ $barmanPkg->id }}"
                                data-name="{{ $barmanPkg->name }}" data-glasses="{{ $barmanPkg->badge_glasses ?: 'Solo Servicio' }}"
                                data-min="{{ $barmanPkg->min_guests }}"
                                data-max="{{ $barmanPkg->max_guests ?: 100 }}" data-default-guests="{{ $barmanPkg->default_guests ?: 50 }}"
                                data-color="{{ $barmanColor }}" data-img="{{ asset($barmanImg) }}">
                                <div class="row align-items-center text-center text-md-start gy-3">
                                    <div class="col-12 col-md-3 barman-col-sep text-center">
                                        <span
                                            class="d-inline-block p-2 rounded-circle bg-warning bg-opacity-10 text-warning mb-2">
                                            <i class="ri-user-star-line fs-24"></i>
                                        </span>
                                        <h6 class="fw-bold text-dark font-montserrat text-uppercase mb-0 fs-14">
                                            SERVICIO DE<br>BARMAN</h6>
                                    </div>

                                    <div class="col-6 col-md-2 barman-col-sep text-center text-md-start ps-md-3">
                                        <div class="fw-bold text-dark fs-14">1 Bartender</div>
                                        <small class="text-muted">1 asistente de bar</small>
                                    </div>

                                    <div class="col-6 col-md-3 barman-col-sep text-center text-md-start ps-md-3">
                                        <div class="fw-bold text-dark fs-13 mb-1">Incluye:</div>
                                        <div class="small text-muted mb-0">Barra móvil</div>
                                        <div class="small text-muted mb-0">Herramientas de bar</div>
                                        <div class="small text-danger fw-semibold fst-italic">_No cristalería _No
                                            insumos</div>
                                    </div>

                                    <div class="col-6 col-md-2 barman-col-sep text-center">
                                        <div class="fw-bold text-dark fs-16 mb-0">5 horas</div>
                                        <small class="text-muted">de servicio</small>
                                    </div>

                                    <div class="col-6 col-md-2 text-center text-md-end pe-md-2">
                                        <div class="fs-22 fw-bold font-montserrat text-dark mb-2">{{ $barmanPrice }}</div>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-pill px-3 py-1 barman-btn">
                                            Elegir
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center text-muted small mt-2">
                                *Capacidad de atención <strong>{{ $barmanIdeal }}</strong>.
                            </div>
                        </section>
                    @endif

                    {{-- SECTION 3: NUESTRA CARTA (Screenshot 4) --}}
                    <section id="section-carta" class="mb-5">
                        <div class="carta-section-card">
                            <div class="text-center mb-4">
                                <h2 class="display-6 fw-bold font-montserrat text-dark mb-1 letter-spacing-1">NUESTRA
                                    CARTA</h2>
                                <p class="text-muted fw-bold small text-uppercase letter-spacing-2 mb-2">ELIGE LOS
                                    COCTELES QUE MÁS TE GUSTEN</p>
                                <div
                                    class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-pill fs-13 fw-semibold text-dark">
                                    <i class="ri-star-fill text-warning"></i>
                                    <span>Cócteles seleccionados: <strong id="cocktailCounter">0</strong> de 6
                                        recomendados</span>
                                </div>
                            </div>

                            <div class="row g-4">
                                @foreach ($cocktailCategories as $category)
                                    @php
                                        $catSlug = strtolower($category->nombre);
                                        $catColorClass = match (true) {
                                            str_contains($catSlug, 'ron') => 'spirit-ron',
                                            str_contains($catSlug, 'pisco') => 'spirit-pisco',
                                            str_contains($catSlug, 'vodka') => 'spirit-vodka',
                                            str_contains($catSlug, 'gin') => 'spirit-gin',
                                            str_contains($catSlug, 'tequila') => 'spirit-tequila',
                                            default => 'spirit-ron',
                                        };
                                        $catIcon = match (true) {
                                            str_contains($catSlug, 'ron') => 'ri-goblet-line',
                                            str_contains($catSlug, 'pisco') => 'ri-goblet-fill',
                                            str_contains($catSlug, 'vodka') => 'ri-cup-line',
                                            str_contains($catSlug, 'gin') => 'ri-drinks-line',
                                            str_contains($catSlug, 'tequila') => 'ri-contrast-drop-line',
                                            default => 'ri-goblet-line',
                                        };
                                    @endphp

                                    <div class="col-12 col-md-6 col-lg-{{ $loop->iteration <= 2 ? '6' : '4' }}">
                                        <div class="p-3 bg-light bg-opacity-50 rounded-4 border h-100">
                                            <div class="spirit-title {{ $catColorClass }}">
                                                <i class="{{ $catIcon }}"></i> {{ $category->nombre }}
                                            </div>

                                            <div class="cocktails-grid">
                                                @foreach ($category->cocktails as $cocktail)
                                                    <div class="cocktail-chip" data-name="{{ $cocktail->nombre }}">
                                                        <span class="chip-name">{{ $cocktail->nombre }}</span>
                                                        <span class="chip-dot"></span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Bottom notice box from flyer --}}
                            <div class="advance-banner mt-4">
                                <div class="d-flex align-items-center gap-2 flex-grow-1">
                                    <i class="ri-star-line text-warning fs-22"></i>
                                    <div class="small fw-semibold text-dark">
                                        De esta carta es recomendable elegir máximo de <strong>5 a 6 opciones</strong>.
                                    </div>
                                </div>
                                <div class="ps-3 border-start text-end">
                                    <div class="badge bg-warning text-dark fw-bold px-2 py-1 fs-12">SOLO +18</div>
                                    <div class="fs-10 text-muted fw-bold text-uppercase mt-1">Solo mayores de 18 años
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- SECTION 4: CONDICIONES Y POLÍTICAS (Footers of Flyer 1 & 2) --}}
                    <section id="section-condiciones" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <div class="info-card-box h-100">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="ri-checkbox-circle-fill text-warning fs-20"></i>
                                        <h6 class="fw-bold text-dark font-montserrat text-uppercase mb-0">IMPORTANTE
                                        </h6>
                                    </div>
                                    <ul class="list-unstyled mb-0 small text-muted">
                                        <li class="mb-2 d-flex align-items-start gap-2">
                                            <i class="ri-check-line text-success mt-1"></i>
                                            <span>Trabajamos con insumos de calidad y ampliamente conocidos en el
                                                mercado.</span>
                                        </li>
                                        <li class="mb-2 d-flex align-items-start gap-2">
                                            <i class="ri-check-line text-success mt-1"></i>
                                            <span>Los paquetes son a todo costo; comprenden de (01) Barra móvil, insumos
                                                para preparación de los cocteles, hielo en cubos, cooler para hielo,
                                                vasos, herramientas de bar y logística.</span>
                                        </li>
                                        <li class="d-flex align-items-start gap-2">
                                            <i class="ri-check-line text-success mt-1"></i>
                                            <span>Todos los precios son para la zona de <strong>Tarapoto</strong>, fuera
                                                de la ciudad aplican costos por distancia, traslado y estadía.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="advance-banner h-100 flex-column justify-content-center text-center">
                                    <div class="p-3 rounded-circle bg-warning bg-opacity-15 text-warning mb-2 mx-auto"
                                        style="width: 58px; height: 58px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ri-shake-hands-line fs-28"></i>
                                    </div>
                                    <div class="fs-15 text-dark fw-bold mb-1">Condición de Reserva</div>
                                    <p class="fs-13 text-muted mb-0">
                                        Todo contrato se realiza con el <strong>50% de adelanto</strong> del valor total
                                        contratado.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Right Column: Interactive Quote Panel & Order Confirmation --}}
                <div class="col-lg-5 col-xl-4">
                    <div class="sticky-order-box">
                        <div class="order-summary-card">
                            <div class="order-card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-warning text-uppercase fw-bold letter-spacing-1">
                                            Cotizador Oficial</div>
                                        <h5 class="fw-bold mb-0 text-white font-serif">Tu Cotización</h5>
                                    </div>
                                    <span class="badge bg-warning text-dark px-2 py-1 fs-11 fw-bold">EN TIEMPO
                                        REAL</span>
                                </div>
                            </div>

                            <div class="p-4">
                                {{-- Step 1: Selected Package Info --}}
                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4 mb-3 border">
                                    <img src="{{ asset('img/packages/pkg-01.png') }}" id="selected_pkg_img"
                                        alt="Paquete" class="rounded-3 shadow-sm"
                                        style="width: 60px; height: 60px; object-fit: contain; background: #fff; padding: 4px;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="badge bg-dark text-white fs-10 text-uppercase"
                                                id="selected_pkg_glasses">100 vasos</span>
                                            <span class="fw-bold fs-16 text-dark" id="summary_package_cost">S/
                                                1,200</span>
                                        </div>
                                        <h6 class="fw-bold text-dark font-cinzel mb-0 mt-1 fs-16"
                                            id="summary_package_name">Paquete ESENCIAL</h6>
                                        <small class="text-muted" id="summary_guest_range_label">Ideal para 20 a 50
                                            invitados</small>
                                    </div>
                                </div>

                                {{-- Selected Cocktails List --}}
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold text-muted text-uppercase">Cócteles Elegidos</span>
                                        <span class="small text-muted" id="selected_cocktails_count">0
                                            seleccionados</span>
                                    </div>
                                    <div id="selected_cocktails_container"
                                        class="d-flex flex-wrap gap-1 p-2 bg-light bg-opacity-60 rounded-3 border min-h-35">
                                        <span class="text-muted small fst-italic p-1"
                                            id="cocktails_empty_notice">Elige hasta 6 cócteles de la carta</span>
                                    </div>
                                    <div id="hidden_cocktails_inputs"></div>
                                </div>

                                {{-- Step 2: Event Parameters --}}
                                <div class="mb-3">
                                    <label for="guests"
                                        class="form-label small fw-bold text-muted text-uppercase mb-1">
                                        Número de Invitados <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary border-2 px-3" type="button"
                                            id="btnGuestsMinus"><i class="ri-subtract-line"></i></button>
                                        <input type="number" id="guests" name="guests"
                                            class="form-control text-center fw-bold text-dark fs-16 border-2"
                                            value="35" min="1" required>
                                        <button class="btn btn-outline-secondary border-2 px-3" type="button"
                                            id="btnGuestsPlus"><i class="ri-add-line"></i></button>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted" id="guest_range_hint">Rango: 20 a 50
                                            invitados.</small>
                                        <small class="text-primary fw-semibold cursor-pointer"
                                            id="btnResetIdealGuests" style="cursor: pointer;">Ajustar al ideal</small>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-7">
                                        <label for="fecha_evento"
                                            class="form-label small fw-bold text-muted text-uppercase mb-1">
                                            Fecha del Evento <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" id="fecha_evento" name="fecha_evento"
                                            class="form-control border-2 fw-semibold fs-14"
                                            value="{{ date('Y-m-d', strtotime('+15 days')) }}"
                                            min="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-5">
                                        <label for="event_type"
                                            class="form-label small fw-bold text-muted text-uppercase mb-1">
                                            Celebración
                                        </label>
                                        <select id="event_type" name="event_type" class="form-select border-2 fs-13">
                                            <option value="Boda">Boda</option>
                                            <option value="Cumpleaños">Cumpleaños</option>
                                            <option value="Corporativo">Empresa</option>
                                            <option value="Aniversario">Aniversario</option>
                                            <option value="Graduación">Graduación</option>
                                            <option value="Fiesta Privada">Privada</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="my-3 opacity-25">

                                {{-- Step 3: Breakdown and Tax Calculation --}}
                                <div id="sim_loading" class="text-center py-3" style="display: none;">
                                    <i class="ri-loader-4-line ri-spin fs-24 text-success"></i>
                                    <div class="small text-muted mt-1">Calculando montos tributarios...</div>
                                </div>

                                <div id="sim_content">
                                    <div class="d-flex justify-content-between mb-2 fs-13">
                                        <span class="text-muted">Subtotal (Base Imponible)</span>
                                        <span class="fw-semibold text-dark" id="summary_subtotal">{{ $signo }}
                                            0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 fs-13">
                                        <span class="text-muted">I.G.V. (18% Ley SUNAT)</span>
                                        <span class="fw-semibold text-dark" id="summary_igv">{{ $signo }}
                                            0.00</span>
                                    </div>
                                    <div
                                        class="d-flex justify-content-between align-items-center pt-2 border-top border-2 mb-2">
                                        <span class="fs-15 fw-bold text-dark">Total Estimado</span>
                                        <span class="fs-22 fw-bold text-success font-montserrat"
                                            id="summary_total">{{ $signo }} 0.00</span>
                                    </div>
                                    <div
                                        class="p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25 d-flex justify-content-between align-items-center fs-12 mb-2">
                                        <span class="text-dark fw-semibold"><i
                                                class="ri-hand-coin-line text-warning me-1"></i> Adelanto 50%
                                            Sugerido:</span>
                                        <strong class="text-dark" id="summary_advance">{{ $signo }}
                                            0.00</strong>
                                    </div>
                                </div>

                                <div id="sim_error" class="alert alert-danger py-2 small mb-3"
                                    style="display: none;"></div>

                                <hr class="my-3 opacity-25">

                                {{-- Step 4: Contact Form (Ley N° 29733 - Minimal PII) --}}
                                <div class="mb-3">
                                    <label for="nombre"
                                        class="form-label small fw-bold text-muted text-uppercase mb-1">
                                        Tu Nombre o Contacto <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="nombre" name="nombre"
                                        class="form-control border-2 fw-semibold" placeholder="Ej: MARÍA LÓPEZ"
                                        required maxlength="100">
                                </div>

                                <div class="mb-3">
                                    <label for="telefono"
                                        class="form-label small fw-bold text-muted text-uppercase mb-1">
                                        Teléfono Celular / WhatsApp <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-2 fw-bold">+51</span>
                                        <input type="tel" id="telefono" name="telefono"
                                            class="form-control border-2 fw-semibold" placeholder="987654321" required
                                            maxlength="15">
                                    </div>
                                    <small class="text-muted fs-11">9 dígitos de celular peruano.</small>
                                </div>

                                <div class="p-2 rounded bg-light border mb-3 fs-11 text-muted">
                                    <div class="form-check">
                                        <input class="form-check-input mt-1" type="checkbox" id="consent"
                                            name="consent" required checked>
                                        <label class="form-check-label" for="consent">
                                            En cumplimiento de la <strong>Ley N° 29733 de Protección de Datos
                                                Personales</strong>: {{ $consentText }}
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn-request-quote" id="btnSubmitOrder">
                                    <i class="ri-send-plane-fill me-2"></i> Solicitar Cotización de Evento
                                </button>
                                <div class="text-center text-muted fs-11 mt-2">
                                    <i class="ri-shield-check-line text-success me-1"></i> Solicitud preliminar sin
                                    compromiso
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    {{-- Mobile Floating Quick Bar --}}
    <div class="mobile-bottom-bar">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
                <div class="small fw-bold text-dark font-cinzel mb-0" id="mobile_bar_pkg_name">Paquete ESENCIAL</div>
                <div class="fs-15 fw-bold text-success font-montserrat" id="mobile_bar_total">S/ 1,200.00</div>
            </div>
            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill px-3 py-2 fs-13"
                onclick="document.querySelector('.sticky-order-box').scrollIntoView({behavior: 'smooth'})">
                Completar Pedido <i class="ri-arrow-right-line ms-1"></i>
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        const CURRENCY_SIGN = "{{ $signo }}";
        let debounceTimer = null;
        let selectedCocktailsList = [];

        $(document).ready(function() {
            // Select default first package
            const firstPkg = $('.flyer-card').first();
            if (firstPkg.length) {
                selectPackage(firstPkg);
            }

            // Click package card
            $('.flyer-card').on('click', function(e) {
                selectPackage($(this));
            });

            // Click Barman card
            $('.barman-service-card').on('click', function(e) {
                $('.flyer-card').removeClass('selected');
                $(this).addClass('selected');

                const pkgId = $(this).data('id');
                const pkgName = $(this).data('name');
                const min = $(this).data('min');
                const max = $(this).data('max');
                const defaultGuests = $(this).data('default-guests');

                $('#selected_package_id').val(pkgId);
                $('#selected_pkg_glasses').text('Solo Servicio').css('background-color', '#be8632');
                $('#summary_package_name').text(pkgName);
                $('#summary_package_cost').text('S/ 600');
                $('#summary_guest_range_label').text('Hasta 100 personas (5 horas de servicio)');
                $('#selected_pkg_img').attr('src', "{{ asset('img/packages/pkg-01.png') }}");
                $('#guest_range_hint').text('Rango permitido: 10 a 100 personas.');

                $('#guests').val(defaultGuests).attr('min', min).attr('max', max);
                recalculateSimulation();
            });

            // Cocktail selection chip clicks
            $('.cocktail-chip').on('click', function() {
                const name = $(this).data('name');
                if ($(this).hasClass('active')) {
                    $(this).removeClass('active');
                    selectedCocktailsList = selectedCocktailsList.filter(item => item !== name);
                } else {
                    if (selectedCocktailsList.length >= 6) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Límite recomendado',
                            text: 'La carta recomienda elegir hasta 6 opciones para garantizar la mejor frescura y rapidez de servicio en tu barra.',
                            confirmButtonColor: '#1b382b'
                        });
                    }
                    $(this).addClass('active');
                    if (!selectedCocktailsList.includes(name)) {
                        selectedCocktailsList.push(name);
                    }
                }
                updateCocktailsUI();
            });

            // Guest stepper buttons
            $('#btnGuestsMinus').on('click', function() {
                let val = parseInt($('#guests').val()) || 1;
                let min = parseInt($('#guests').attr('min')) || 1;
                if (val > min) {
                    $('#guests').val(val - 5 < min ? min : val - 5).trigger('change');
                }
            });

            $('#btnGuestsPlus').on('click', function() {
                let val = parseInt($('#guests').val()) || 1;
                let max = parseInt($('#guests').attr('max')) || 999;
                if (val < max) {
                    $('#guests').val(val + 5 > max ? max : val + 5).trigger('change');
                }
            });

            $('#btnResetIdealGuests').on('click', function() {
                const activeCard = $('.flyer-card.selected, .barman-service-card.selected');
                if (activeCard.length) {
                    const defaultGuests = activeCard.data('default-guests') || activeCard.data('min') || 50;
                    $('#guests').val(defaultGuests).trigger('change');
                }
            });

            // Guests input change
            $('#guests').on('input change', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(recalculateSimulation, 300);
            });

            // Submit order
            $('#orderForm').on('submit', function(e) {
                e.preventDefault();

                if (!$('#consent').is(':checked')) {
                    Swal.fire('Atención', 'Debe autorizar el tratamiento de datos para continuar.',
                        'warning');
                    return;
                }

                const btn = $('#btnSubmitOrder');
                btn.prop('disabled', true).html(
                    '<i class="ri-loader-4-line ri-spin me-2"></i> Procesando solicitud...');

                const formData = $(this).serialize();

                $.ajax({
                    url: "{{ route('public.orders.store') }}",
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        if (res.status) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Solicitud Registrada!',
                                text: 'Tu cotización ha sido generada con éxito.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.href = res.redirect_url;
                            });
                        } else {
                            btn.prop('disabled', false).html(
                                '<i class="ri-send-plane-fill me-2"></i> Solicitar Cotización de Evento'
                                );
                            Swal.fire('Atención', res.msg ||
                                'No se pudo procesar la solicitud.', 'error');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(
                            '<i class="ri-send-plane-fill me-2"></i> Solicitar Cotización de Evento'
                            );
                        const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON
                            .msg : 'Error al registrar pedido. Intente nuevamente.';
                        Swal.fire('Atención', msg, 'error');
                    }
                });
            });
        });

        function selectPackage(card) {
            $('.flyer-card, .barman-service-card').removeClass('selected');
            card.addClass('selected');

            const pkgId = card.data('id');
            const pkgName = card.data('name');
            const glasses = card.data('glasses');
            const min = card.data('min');
            const max = card.data('max');
            const defaultGuests = card.data('default-guests') || min;
            const color = card.data('color');
            const img = card.data('img');

            $('#selected_package_id').val(pkgId);
            $('#selected_pkg_glasses').text(glasses).css('background-color', color);
            $('#summary_package_name').text(pkgName);
            $('#mobile_bar_pkg_name').text(pkgName);
            $('#selected_pkg_img').attr('src', img);

            const rangeText = (max < 900) ? `${min} a ${max} invitados` : `${min}+ invitados`;
            $('#summary_guest_range_label').text('Ideal para ' + rangeText);
            $('#guest_range_hint').text(`Rango permitido: ${min} a ${(max < 900 ? max : 'más')} personas.`);

            $('#guests').attr('min', min).attr('max', max);
            let currentGuests = parseInt($('#guests').val()) || defaultGuests;
            if (currentGuests < min) currentGuests = min;
            if (max < 900 && currentGuests > max) currentGuests = max;
            $('#guests').val(currentGuests);

            recalculateSimulation();
        }

        function updateCocktailsUI() {
            $('#cocktailCounter').text(selectedCocktailsList.length);
            $('#selected_cocktails_count').text(`${selectedCocktailsList.length} seleccionados`);

            let html = '';
            let hiddenInputs = '';

            if (selectedCocktailsList.length === 0) {
                html =
                    '<span class="text-muted small fst-italic p-1" id="cocktails_empty_notice">Elige hasta 6 cócteles de la carta</span>';
            } else {
                selectedCocktailsList.forEach(name => {
                    html += `
                        <span class="badge bg-white text-dark border shadow-sm px-2 py-1 fs-11 d-inline-flex align-items-center gap-1">
                            <i class="ri-cocktail-line text-warning"></i> ${name}
                            <i class="ri-close-line text-muted cursor-pointer ms-1" onclick="removeCocktail('${name}')" style="cursor: pointer;"></i>
                        </span>
                    `;
                    hiddenInputs += `<input type="hidden" name="cocktails[]" value="${name}">`;
                });
            }

            $('#selected_cocktails_container').html(html);
            $('#hidden_cocktails_inputs').html(hiddenInputs);
        }

        function removeCocktail(name) {
            selectedCocktailsList = selectedCocktailsList.filter(c => c !== name);
            $(`.cocktail-chip[data-name="${name}"]`).removeClass('active');
            updateCocktailsUI();
        }

        function recalculateSimulation() {
            const packageId = $('#selected_package_id').val();
            const guests = parseInt($('#guests').val()) || 1;

            if (!packageId || guests < 1) return;

            $('#sim_error').hide();
            $('#sim_loading').show();
            $('#sim_content').css('opacity', '0.4');

            $.post("{{ route('public.orders.simulate') }}", {
                _token: CSRF_TOKEN,
                package_id: packageId,
                guests: guests,
                extras: []
            }, function(res) {
                $('#sim_loading').hide();
                $('#sim_content').css('opacity', '1');

                if (res.status) {
                    const data = res.data;
                    const subtotal = parseFloat(data.subtotal);
                    const igv = parseFloat(data.igv);
                    const total = parseFloat(data.total);
                    const advance = total * 0.50;

                    $('#summary_subtotal').text(CURRENCY_SIGN + ' ' + subtotal.toFixed(2));
                    $('#summary_igv').text(CURRENCY_SIGN + ' ' + igv.toFixed(2));
                    $('#summary_total').text(CURRENCY_SIGN + ' ' + total.toFixed(2));
                    $('#mobile_bar_total').text(CURRENCY_SIGN + ' ' + total.toFixed(2));
                    $('#summary_package_cost').text(CURRENCY_SIGN + ' ' + total.toFixed(2));
                    $('#summary_advance').text(CURRENCY_SIGN + ' ' + advance.toFixed(2));
                } else {
                    $('#sim_error').text(res.msg).show();
                }
            }).fail(function(xhr) {
                $('#sim_loading').hide();
                $('#sim_content').css('opacity', '1');
                const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg :
                    'Cantidad de invitados fuera de rango.';
                $('#sim_error').text(msg).show();
            });
        }
    </script>
</body>

</html>
