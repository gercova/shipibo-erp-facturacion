<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Solicitud Registrada! | Shipibo Coctelería</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --primary-gold: #c59b27;
            --primary-dark: #121815;
            --primary-green: #1b382b;
            --bg-body: #f8faf9;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #2b332e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .font-serif {
            font-family: 'Playfair Display', serif;
        }

        .confirmation-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(18, 24, 21, 0.08);
            border: 1px solid #e6ede8;
            max-width: 650px;
            width: 100%;
            overflow: hidden;
        }

        .tracking-box {
            background: #fdfaf1;
            border: 2px dashed #e3cf8d;
            border-radius: 16px;
            padding: 1.25rem;
        }

        .btn-whatsapp {
            background-color: #25d366;
            color: #ffffff;
            font-weight: 700;
            border: none;
            transition: all 0.2s;
        }

        .btn-whatsapp:hover {
            background-color: #20ba5a;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.35);
        }
    </style>
</head>
<body>

    <div class="confirmation-card text-center p-4 p-md-5">
        {{-- Success icon --}}
        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 80px; height: 80px;">
            <i class="ri-checkbox-circle-fill display-5"></i>
        </div>

        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fs-13 mb-2">
            <i class="ri-check-line me-1"></i> Solicitud Preliminar Recibida
        </span>

        <h2 class="display-6 font-serif fw-bold text-dark mb-2">¡Gracias por tu Preferencia!</h2>
        <p class="text-muted fs-15 mb-4">
            Hemos recibido los detalles de tu evento. Un asesor de <strong>Shipibo Coctelería</strong> se pondrá en contacto contigo en breve para afinar la cotización y reservar tu fecha.
        </p>

        {{-- Tracking Code Box --}}
        <div class="tracking-box mb-4">
            <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Tu Código Único de Seguimiento</span>
            <div class="d-flex align-items-center justify-content-center gap-2">
                <span class="fs-28 fw-bold text-dark letter-spacing-1" id="trackingCodeText">{{ $contract->tracking_code }}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" onclick="copyTrackingCode()" title="Copiar código">
                    <i class="ri-file-copy-line"></i> Copiar
                </button>
            </div>
            <small class="text-muted d-block mt-1">N° de Cotización Preliminar: <strong>{{ $contract->contract_number }}</strong></small>
        </div>

        {{-- Order Details Summary --}}
        <div class="card bg-light border-0 rounded-3 text-start p-3 mb-4">
            <h6 class="fw-bold text-dark mb-2 font-serif border-bottom pb-2">Resumen de la Solicitud</h6>
            <div class="row g-2 small">
                <div class="col-6 text-muted">Nombre Registrado:</div>
                <div class="col-6 text-end fw-semibold text-dark">{{ $contract->client ? $contract->client->nombres : '-' }}</div>

                <div class="col-6 text-muted">Fecha Tentativa:</div>
                <div class="col-6 text-end fw-semibold text-dark">{{ $contract->fecha_evento ? $contract->fecha_evento->format('d/m/Y') : '-' }}</div>

                <div class="col-6 text-muted">Invitados Estimados:</div>
                <div class="col-6 text-end fw-semibold text-dark">{{ $contract->guest_count ?: '-' }} personas</div>

                <div class="col-6 text-muted">Tipo de Evento:</div>
                <div class="col-6 text-end fw-semibold text-dark">{{ $contract->event_type ?: 'Celebración' }}</div>

                <div class="col-6 text-muted pt-2 border-top">Total Estimado (Inc. IGV):</div>
                <div class="col-6 text-end fw-bold text-success fs-15 pt-2 border-top">S/ {{ number_format((float) $contract->total, 2) }}</div>
            </div>
        </div>

        {{-- WhatsApp Direct Contact Button --}}
        <div class="d-grid gap-2">
            <a href="{{ $waUrl }}" target="_blank" class="btn btn-whatsapp py-3 rounded-3 fs-16 shadow-sm d-flex align-items-center justify-content-center">
                <i class="ri-whatsapp-line fs-20 me-2"></i> Chatear con un Asesor por WhatsApp
            </a>

            <a href="{{ route('public.orders.index') }}" class="btn btn-link text-muted fs-14 mt-1">
                <i class="ri-arrow-left-line me-1"></i> Volver a la página principal
            </a>
        </div>
    </div>

    <script>
        function copyTrackingCode() {
            const code = document.getElementById('trackingCodeText').innerText;
            navigator.clipboard.writeText(code).then(() => {
                alert('¡Código de seguimiento copiado al portapapeles!');
            });
        }
    </script>
</body>
</html>
