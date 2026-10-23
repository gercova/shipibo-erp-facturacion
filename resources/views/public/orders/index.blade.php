<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cotizador Digital de Eventos | Shipibo Coctelería</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-gold: #c59b27;
            --primary-dark: #121815;
            --primary-green: #1b382b;
            --accent-jungle: #24583d;
            --bg-body: #f8faf9;
            --card-border: #e6ede8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #2b332e;
            -webkit-font-smoothing: antialiased;
        }

        .font-serif {
            font-family: 'Playfair Display', serif;
        }

        .hero-banner {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-green) 100%);
            color: #ffffff;
            padding: 3rem 1rem 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 24px;
            background: var(--bg-body);
            border-top-left-radius: 28px;
            border-top-right-radius: 28px;
        }

        .package-card {
            border: 2px solid var(--card-border);
            border-radius: 16px;
            transition: all 0.25s ease;
            cursor: pointer;
            background: #ffffff;
            overflow: hidden;
        }

        .package-card:hover, .package-card.selected {
            border-color: var(--primary-gold);
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(197, 155, 39, 0.18) !important;
        }

        .package-card.selected .card-check-indicator {
            background-color: var(--primary-gold);
            color: #ffffff;
        }

        .card-check-indicator {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #ccd6ce;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: transparent;
            transition: all 0.2s;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-green);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            margin-right: 8px;
        }

        .cocktail-thumb {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
        }

        .badge-gold {
            background-color: #fff8e6;
            color: #946c05;
            border: 1px solid #f3dc9e;
        }

        .sticky-summary {
            position: sticky;
            top: 20px;
        }

        .consent-box {
            background: #f1f6f3;
            border: 1px solid #d4e2d8;
            border-radius: 12px;
            padding: 1rem;
            font-size: 0.85rem;
            color: #435448;
        }

        .btn-gold {
            background: linear-gradient(135deg, #d4a938 0%, #b3851b 100%);
            color: #ffffff;
            font-weight: 700;
            border: none;
            transition: all 0.2s;
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, #b3851b 0%, #946c05 100%);
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(179, 133, 27, 0.35);
        }
    </style>
</head>
<body>

    {{-- Hero Header --}}
    <div class="hero-banner text-center">
        <div class="container" style="max-width: 800px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white mb-2 fs-13">
                <i class="ri-cocktail-line text-warning"></i> Shipibo Coctelería &bull; Tarapoto, Perú
            </div>
            <h1 class="display-5 font-serif fw-bold mb-2">Cotiza tu Barra Móvil</h1>
            <p class="lead opacity-90 fs-16 mb-0">Selecciona el paquete ideal para tu evento, elige tus cócteles favoritos y recibe una cotización con cálculo exacto en tiempo real.</p>
        </div>
    </div>

    {{-- Main Container --}}
    <div class="container py-3 mb-5" style="max-width: 1100px;">
        <form id="orderForm" onsubmit="event.preventDefault()">
            @csrf

            {{-- Honeypot anti-spam field (hidden from real users) --}}
            <input type="text" name="_hp_website" id="_hp_website" style="display:none !important;" tabindex="-1" autocomplete="off">
            <input type="hidden" name="package_id" id="selected_package_id" value="{{ optional($packages->first())->id }}">

            <div class="row g-4">
                {{-- Left Column: Interactive Form Steps --}}
                <div class="col-lg-7">

                    {{-- Step 1: Selección de Paquete --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="step-number">1</span>
                            <h5 class="fw-bold text-dark mb-0 font-serif">Elige tu Paquete de Barra</h5>
                        </div>
                        <p class="text-muted small mb-3">Todos los paquetes incluyen barra móvil profesional, bartender calificado, cristalería completa, hielo gourmet y logística para Tarapoto.</p>

                        <div class="row g-3">
                            @forelse ($packages as $pkg)
                                <div class="col-12">
                                    <div class="package-card p-3 {{ $loop->first ? 'selected' : '' }}" data-id="{{ $pkg->id }}" data-min="{{ $pkg->min_guests }}" data-max="{{ $pkg->max_guests ?: 999 }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ $pkg->image_url }}" alt="{{ $pkg->name }}" class="rounded-3 shadow-sm" style="width: 68px; height: 68px; object-fit: cover;">
                                                <div>
                                                    <h6 class="fw-bold mb-1 text-dark fs-16">{{ $pkg->name }}</h6>
                                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                                        <span class="badge badge-gold">
                                                            <i class="ri-group-line me-1"></i>{{ $pkg->guest_range_label }}
                                                        </span>
                                                        @if($pkg->price_mode === 'tiered')
                                                            <span class="badge bg-light text-secondary border">Precio Escalonado</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-muted small mb-0">{{ Str::limit($pkg->description, 85) }}</p>
                                                </div>
                                            </div>
                                            <div class="text-end ps-2">
                                                <div class="card-check-indicator mb-2">
                                                    <i class="ri-check-line"></i>
                                                </div>
                                                <div class="fw-bold text-dark fs-14">
                                                    @if($pkg->price_mode === 'tiered' && $pkg->priceTiers->isNotEmpty())
                                                        {{ $signo }} {{ number_format((float) $pkg->priceTiers->first()->price, 2) }}
                                                    @else
                                                        {{ $signo }} {{ number_format((float) $pkg->base_price, 2) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-warning">No hay paquetes activos en este momento.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Step 2: Parámetros del Evento --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="step-number">2</span>
                            <h5 class="fw-bold text-dark mb-0 font-serif">Detalles de tu Evento</h5>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="guests" class="form-label small fw-bold text-muted text-uppercase">
                                    Número Estimado de Invitados <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-2"><i class="ri-group-line"></i></span>
                                    <input type="number" id="guests" name="guests" class="form-control border-2 fw-bold text-dark fs-16" min="1" value="120" required>
                                </div>
                                <small class="text-muted" id="guest_range_hint">Rango permitido: 100 - 180 personas.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="fecha_evento" class="form-label small fw-bold text-muted text-uppercase">
                                    Fecha Tentativa del Evento <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-2"><i class="ri-calendar-event-line"></i></span>
                                    <input type="date" id="fecha_evento" name="fecha_evento" class="form-control border-2 fw-semibold" value="{{ date('Y-m-d', strtotime('+15 days')) }}" min="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="event_type" class="form-label small fw-bold text-muted text-uppercase">
                                    Tipo de Celebración
                                </label>
                                <select id="event_type" name="event_type" class="form-select border-2">
                                    <option value="Boda">Boda / Matrimonio</option>
                                    <option value="Cumpleaños">Cumpleaños / Quinceañero</option>
                                    <option value="Corporativo">Evento Corporativo / Empresa</option>
                                    <option value="Aniversario">Aniversario</option>
                                    <option value="Graduación">Graduación / Promoción</option>
                                    <option value="Fiesta Privada">Fiesta Privada / Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: Carta de Cócteles sugeridos --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span class="step-number">3</span>
                                <h5 class="fw-bold text-dark mb-0 font-serif">Nuestra Carta de Cócteles</h5>
                            </div>
                            <span class="badge bg-light text-secondary border">5 a 6 opciones recomendadas</span>
                        </div>
                        <p class="text-muted small mb-3">Conoce las opciones de cócteles clásicos y de autor que podrás elegir para tu evento (Pisco, Ron, Vodka, Gin, Tequila):</p>

                        <div class="accordion" id="accordionCocktails">
                            @foreach ($cocktailCategories as $cat)
                                <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                                    <h2 class="accordion-header" id="headingCat{{ $cat->id }}">
                                        <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }} py-2 px-3 fw-bold text-dark bg-light bg-opacity-50" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCat{{ $cat->id }}">
                                            <i class="{{ $cat->icono ?: 'ri-goblet-line' }} text-warning me-2"></i> {{ $cat->nombre }} ({{ $cat->cocktails->count() }} cócteles)
                                        </button>
                                    </h2>
                                    <div id="collapseCat{{ $cat->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#accordionCocktails">
                                        <div class="accordion-body p-3">
                                            <div class="row g-2">
                                                @foreach ($cat->cocktails as $cocktail)
                                                    <div class="col-sm-6">
                                                        <div class="d-flex align-items-center p-2 rounded border bg-white">
                                                            <div class="me-2 text-warning fs-18"><i class="ri-checkbox-circle-line"></i></div>
                                                            <div>
                                                                <div class="fw-semibold text-dark fs-13">{{ $cocktail->nombre }}</div>
                                                                <div class="text-muted fs-11 fst-italic">{{ Str::limit($cocktail->descripcion_corta ?: 'Cristalería: ' . ($cocktail->cristaleria ?: 'Vaso bar'), 45) }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Step 4: Datos de Contacto (Ley N° 29733 - Minimal PII) --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="step-number">4</span>
                            <h5 class="fw-bold text-dark mb-0 font-serif">Datos de Contacto para Cotización</h5>
                        </div>
                        <p class="text-muted small mb-3">
                            En estricto cumplimiento de la <strong>Ley N° 29733 de Protección de Datos Personales</strong>, únicamente te solicitamos tu nombre y teléfono móvil para que un asesor te contacte. <em>No te solicitaremos DNI, dirección ni correos en este paso.</em>
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label small fw-bold text-muted text-uppercase">
                                    Tu Nombre o Contacto <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="nombre" name="nombre" class="form-control border-2 fw-semibold" placeholder="Ej: MARÍA LÓPEZ" required maxlength="100">
                            </div>

                            <div class="col-md-6">
                                <label for="telefono" class="form-label small fw-bold text-muted text-uppercase">
                                    Teléfono Celular / WhatsApp <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-2 fw-bold">+51</span>
                                    <input type="tel" id="telefono" name="telefono" class="form-control border-2 fw-semibold" placeholder="987654321" required maxlength="15">
                                </div>
                                <small class="text-muted">9 dígitos de celular peruano.</small>
                            </div>

                            <div class="col-12 mt-3">
                                <div class="consent-box">
                                    <div class="form-check">
                                        <input class="form-check-input mt-1" type="checkbox" id="consent" name="consent" required>
                                        <label class="form-check-label" for="consent">
                                            {{ $consentText }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right Column: Live Sticky Summary & Submission --}}
                <div class="col-lg-5">
                    <div class="sticky-summary">
                        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-3">
                            <div class="card-header bg-dark text-white p-3 px-4 border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0 text-white font-serif">Resumen de Cotización</h6>
                                    <span class="badge bg-warning text-dark px-2 py-1 fs-11 fw-bold">EN TIEMPO REAL</span>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div id="sim_loading" class="text-center py-4" style="display: none;">
                                    <i class="ri-loader-4-line ri-spin fs-24 text-primary"></i>
                                    <div class="small text-muted mt-2">Calculando montos tributarios en el servidor...</div>
                                </div>

                                <div id="sim_content">
                                    <div class="mb-3">
                                        <span class="text-muted small text-uppercase fw-semibold">Paquete Seleccionado</span>
                                        <h5 class="fw-bold text-dark mb-0" id="summary_package_name">{{ optional($packages->first())->name ?: 'Paquete' }}</h5>
                                        <small class="text-muted" id="summary_guest_count">Para 120 invitados</small>
                                    </div>

                                    <hr class="my-3 text-muted opacity-25">

                                    {{-- Component breakdown list --}}
                                    <div class="mb-3">
                                        <span class="text-muted small text-uppercase fw-semibold d-block mb-2">Desglose de Componentes</span>
                                        <div id="summary_items_list" class="small text-muted" style="max-height: 200px; overflow-y: auto;">
                                            {{-- Dynamic items --}}
                                        </div>
                                    </div>

                                    <hr class="my-3 text-muted opacity-25">

                                    {{-- Tax & Price calculation --}}
                                    <div class="d-flex justify-content-between mb-2 fs-14">
                                        <span class="text-muted">Subtotal (Base Imponible)</span>
                                        <span class="fw-semibold text-dark" id="summary_subtotal">{{ $signo }} 0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 fs-14">
                                        <span class="text-muted">I.G.V. (Afectación Individual)</span>
                                        <span class="fw-semibold text-dark" id="summary_igv">{{ $signo }} 0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-2">
                                        <span class="fs-16 fw-bold text-dark">Total Estimado</span>
                                        <span class="fs-22 fw-bold text-success" id="summary_total">{{ $signo }} 0.00</span>
                                    </div>
                                    <div class="text-end text-muted fs-11 mt-1">
                                        * Precios calculados estrictamente por el servidor.
                                    </div>
                                </div>

                                <div id="sim_error" class="alert alert-danger py-2 small mb-0 mt-3" style="display: none;"></div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-gold w-100 py-3 rounded-3 fs-16 shadow-sm" id="btnSubmitOrder">
                                        <i class="ri-send-plane-fill me-2"></i> Solicitar Cotización de Evento
                                    </button>
                                    <div class="text-center text-muted small mt-2">
                                        <i class="ri-shield-check-line me-1 text-success"></i> Solicitud preliminar sin compromiso
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Quality notice --}}
                        <div class="card border-0 bg-white shadow-sm rounded-4 p-3 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2 text-muted small">
                                <i class="ri-checkbox-circle-fill text-success fs-16"></i>
                                <span>Insumos premium de marcas reconocidas &bull; Hielo Gourmet</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        const CURRENCY_SIGN = "{{ $signo }}";
        let debounceTimer = null;

        $(document).ready(function() {
            // Package selection card click
            $('.package-card').on('click', function() {
                $('.package-card').removeClass('selected');
                $(this).addClass('selected');

                const pkgId = $(this).data('id');
                $('#selected_package_id').val(pkgId);

                const min = $(this).data('min');
                const max = $(this).data('max');
                $('#guest_range_hint').text('Rango permitido: ' + min + ' a ' + (max < 900 ? max : 'más') + ' personas.');

                // Adjust guests input if outside bounds
                let guests = parseInt($('#guests').val()) || min;
                if (guests < min) guests = min;
                if (max < 900 && guests > max) guests = max;
                $('#guests').val(guests);

                recalculateSimulation();
            });

            // Guests change trigger
            $('#guests').on('input change', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(recalculateSimulation, 300);
            });

            // Initial calculation
            recalculateSimulation();

            // Submit Order
            $('#orderForm').on('submit', function(e) {
                e.preventDefault();

                if (!$('#consent').is(':checked')) {
                    Swal.fire('Atención', 'Debe autorizar el tratamiento de datos para continuar.', 'warning');
                    return;
                }

                const btn = $('#btnSubmitOrder');
                btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-2"></i> Procesando solicitud...');

                const formData = $(this).serialize();

                $.ajax({
                    url: "{{ route('public.orders.store') }}",
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        if (res.status) {
                            window.location.href = res.redirect_url;
                        } else {
                            btn.prop('disabled', false).html('<i class="ri-send-plane-fill me-2"></i> Solicitar Cotización de Evento');
                            Swal.fire('Atención', res.msg || 'No se pudo procesar la solicitud.', 'error');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html('<i class="ri-send-plane-fill me-2"></i> Solicitar Cotización de Evento');
                        const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error al registrar pedido. Intente nuevamente.';
                        Swal.fire('Atención', msg, 'error');
                    }
                });
            });
        });

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
                    $('#summary_package_name').text(data.package_name);
                    $('#summary_guest_count').text('Para ' + data.guests + ' invitados estimados');
                    $('#summary_subtotal').text(CURRENCY_SIGN + ' ' + parseFloat(data.subtotal).toFixed(2));
                    $('#summary_igv').text(CURRENCY_SIGN + ' ' + parseFloat(data.igv).toFixed(2));
                    $('#summary_total').text(CURRENCY_SIGN + ' ' + parseFloat(data.total).toFixed(2));

                    let itemsHtml = '';
                    (data.breakdown || []).forEach(item => {
                        itemsHtml += `
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <div>
                                    <div class="text-dark fw-semibold fs-12">${item.name}</div>
                                    <small class="text-muted">${item.quantity} un. &bull; ${item.tax_affection === '10' ? 'Gravado 18%' : 'Exonerado 0%'}</small>
                                </div>
                                <span class="text-dark fw-bold fs-12">${CURRENCY_SIGN} ${parseFloat(item.total).toFixed(2)}</span>
                            </div>
                        `;
                    });
                    $('#summary_items_list').html(itemsHtml);
                } else {
                    $('#sim_error').text(res.msg).show();
                }
            }).fail(function(xhr) {
                $('#sim_loading').hide();
                $('#sim_content').css('opacity', '1');
                const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Cantidad de invitados fuera de rango.';
                $('#sim_error').text(msg).show();
            });
        }
    </script>
</body>
</html>
