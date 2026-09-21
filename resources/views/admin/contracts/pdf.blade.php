<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $contract->contract_number }} - {{ $contract->title }}</title>

    <style>
        @page {
            margin-top: 12mm;
            margin-bottom: 12mm;
            margin-left: 18mm;
            margin-right: 18mm;
            size: a4 portrait;
        }

        * {
            box-sizing: border-box;
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #111827;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 9.8pt;
            line-height: 1.45;
            background-color: #ffffff;
            position: relative;
        }

        /* Lateral Shipibo Watermarks */
        .watermark-left {
            position: fixed;
            top: 20px;
            left: -130px;
            width: 220px;
            height: 950px;
            opacity: 0.12;
            z-index: -1000;
        }

        .watermark-right {
            position: fixed;
            top: 20px;
            right: -130px;
            width: 220px;
            height: 950px;
            opacity: 0.12;
            z-index: -1000;
            transform: scaleX(-1);
        }

        /* Header Logo & Title */
        .header-logo-container {
            text-align: center;
            margin-top: 0;
            margin-bottom: 8px;
        }

        .header-logo-img {
            max-width: 125px;
            max-height: 100px;
            object-fit: contain;
        }

        .contract-main-title {
            text-align: center;
            font-size: 11.5pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 4px;
            margin-bottom: 12px;
        }

        /* Body Paragraphs */
        p {
            margin: 0 0 9px 0;
            text-align: justify;
            text-justify: inter-word;
            font-size: 9.6pt;
            line-height: 1.42;
        }

        strong {
            color: #000000;
            font-weight: bold;
        }

        /* Bullet list for PRIMERO (Items) */
        .bullet-list {
            margin: 3px 0 9px 0;
            padding-left: 6px;
        }

        .bullet-item {
            margin-bottom: 2.5px;
            font-size: 9.6pt;
            line-height: 1.38;
        }

        /* Payment schedule lines for SEGUNDO */
        .payment-list {
            margin: 3px 0 6px 0;
            padding-left: 0;
        }

        .payment-item {
            margin-bottom: 2px;
            font-size: 9.6pt;
        }

        /* Location and Date */
        .contract-place-date {
            text-align: right;
            margin-top: 14px;
            margin-bottom: 22px;
            font-size: 9.8pt;
        }

        /* Signatures block */
        .signatures-wrapper {
            width: 100%;
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-col {
            width: 50%;
            vertical-align: bottom;
            text-align: center;
            padding: 0 15px;
        }

        .sig-image-space {
            height: 50px;
            margin-bottom: 2px;
        }

        .sig-image-space img {
            max-height: 48px;
            max-width: 150px;
            object-fit: contain;
        }

        .sig-separator {
            border-top: 1px solid #111827;
            width: 80%;
            margin: 0 auto 4px auto;
        }

        .sig-person-name {
            font-size: 9.8pt;
            font-weight: bold;
            color: #000000;
            margin-bottom: 1px;
        }

        .sig-person-detail {
            font-size: 8.8pt;
            color: #1f2937;
        }

        /* Black Footer Bar */
        .footer-bar {
            position: fixed;
            bottom: -12mm;
            left: -18mm;
            right: -18mm;
            height: 28px;
            background-color: #000000;
            border-top: 2px solid #b45309;
            padding: 0 15px;
            z-index: 9999;
        }

        .footer-table {
            width: 100%;
            height: 28px;
            border-collapse: collapse;
        }

        .footer-table td {
            vertical-align: middle;
            font-size: 8.2pt;
            color: #ffffff;
            white-space: nowrap;
        }

        .footer-label {
            font-weight: bold;
            color: #d4af37 !important;
            letter-spacing: 0.3px;
            padding-right: 8px;
            width: 28%;
        }

        .footer-contact {
            text-align: center;
            color: #ffffff !important;
            padding: 0 6px;
        }

        .footer-icon-svg {
            display: inline-block;
            vertical-align: middle;
            margin-right: 3px;
        }
    </style>
</head>
<body>

    <!-- Lateral Shipibo Watermarks (Kené) -->
    @if (file_exists(public_path('files/shipibo_watermark.svg')))
        <img src="{{ public_path('files/shipibo_watermark.svg') }}" class="watermark-left" alt="" />
        <img src="{{ public_path('files/shipibo_watermark.svg') }}" class="watermark-right" alt="" />
    @endif

    <!-- 1. Centered Logo -->
    <div class="header-logo-container">
        @if (!empty($logo) && file_exists(public_path('files/logos/' . $logo)))
            <img src="{{ public_path('files/logos/' . $logo) }}" class="header-logo-img" alt="Logo" />
        @elseif(!empty($business?->logo) && file_exists(public_path('files/logos/' . $business->logo)))
            <img src="{{ public_path('files/logos/' . $business->logo) }}" class="header-logo-img" alt="Logo" />
        @elseif(file_exists(public_path('files/logos/hardys-peru-investments-sac-20260921003702.png')))
            <img src="{{ public_path('files/logos/hardys-peru-investments-sac-20260921003702.png') }}" class="header-logo-img" alt="Logo" />
        @endif
    </div>

    <!-- 2. Main Title -->
    <div class="contract-main-title">
        {{ $contract->title ?: 'CONTRATO DE PRESTACIÓN DE SERVICIOS' }}
    </div>

    <!-- 3. Legal Introduction Paragraph -->
    @php
        $providerRep = $contract->provider_representative ?: ($business?->representante ?: 'Harry Salazar García');
        $providerDoc = $contract->provider_document ?: '46784193';
        $providerCompany = $contract->provider_name ?: ($business?->razon_social ?: 'Hardys Perú Investments S.A.C.');
        $providerRuc = $business?->ruc ?: '20612900591';
        $providerAddress = $business?->direccion ?: 'Jr. Las Heliconias Mz J Lote 13 - Tarapoto';
        
        $clientName = $client?->nombres ?: 'Suiza Village S.A.C.';
        $clientDocType = $client?->tipoDocumento?->descripcion ?: 'RUC';
        $clientDocNum = $client?->nro_documento ?: '20610984666';

        // Event and Emission dates in Spanish words
        \Carbon\Carbon::setLocale('es');
        $eventDateCarbon = $contract->fecha_evento ? \Carbon\Carbon::parse($contract->fecha_evento) : null;
        $issueDateCarbon = $contract->fecha_emision ? \Carbon\Carbon::parse($contract->fecha_emision) : null;

        $eventDateFull = $fecha_evento_texto ?: ($eventDateCarbon ? $eventDateCarbon->isoFormat('dddd D [de] MMMM [del] YYYY') : 'viernes 14 de agosto del 2026');
        $issueDateFull = $fecha_emision_texto ?: ($issueDateCarbon ? $issueDateCarbon->isoFormat('DD [de] MMMM [del] YYYY') : '04 de agosto del 2026');

        $lugarEvento = $contract->lugar_evento ?: 'Resort residencial Suiza Village';

        // Filter operative clauses excluding legacy header duplicates
        $operativeClauses = $clauses->filter(function($c) {
            $t = mb_strtoupper($c->titulo, 'UTF-8');
            return !str_contains($t, 'PARTES CONTRATANTES') && 
                   !str_contains($t, 'OBJETO DEL CONTRATO') && 
                   !str_contains($t, 'HORA Y LUGAR DEL EVENTO') && 
                   !str_contains($t, 'PRECIO Y CONDICIONES DE PAGO');
        });
    @endphp

    <p>
        Conste por el presente documento un contrato de servicios de paquete de Open Bar, que celebran de una parte la persona natural <strong>{{ $providerRep }}</strong>, identificado con <strong>DNI n.° {{ $providerDoc }}</strong>, representante de <strong>{{ $providerCompany }}</strong> con <strong>RUC n.° {{ $providerRuc }}</strong>, con domicilio en {{ $providerAddress }}, que para el presente caso se llamará <strong>PROVEEDOR</strong>, y por la otra parte {{ strlen($clientDocNum) == 11 ? 'la empresa' : 'la persona' }} <strong>{{ $clientName }}</strong>, con <strong>{{ $clientDocType }} n.° {{ $clientDocNum }}</strong>, representada por el Sr. <strong>{{ $client?->representante ?: $clientName }}</strong>, identificado con <strong>DNI n.° {{ $client?->dni_representante ?: $clientDocNum }}</strong>, que para el presente caso se llamará el <strong>CLIENTE</strong>, previo diálogo de convenio con sus intereses, formalizan el negocio bajo los siguientes términos:
    </p>

    <!-- 4. PRIMERO: Servicios y Productos Contratados -->
    <p>
        <strong>PRIMERO:</strong> EL PROVEEDOR se compromete a brindar el siguiente servicio, para el día {{ $eventDateFull }} - Lugar: {{ $lugarEvento }}.
    </p>

    <div class="bullet-list">
        @forelse ($items as $item)
            @php
                $desc = trim($item->descripcion);
                if (!str_starts_with($desc, '-')) {
                    $desc = '- ' . $desc;
                }
            @endphp
            <div class="bullet-item">{{ $desc }}</div>
        @empty
            <div class="bullet-item">- 200 cócteles: Chilcano clásico/ maracuyá, Machu Picchu, Primavera y Mojito.</div>
            <div class="bullet-item">- 1 barman - 1 asistente - Insumos - Barra Movil - Logística.</div>
            <div class="bullet-item">- 1 Mozo</div>
        @endforelse
    </div>

    <!-- 5. SEGUNDO: Precio y Cronograma de Pagos -->
    @php
        $totalFormattedNum = (int)$contract->total == $contract->total 
            ? number_format($contract->total, 0) 
            : number_format($contract->total, 2);
    @endphp

    <p>
        <strong>SEGUNDO:</strong> El precio pactado entre las partes por el servicio es de {{ $numero_letras }} {{ (int)$contract->total }}/100 SOLES ({{ $signo }} {{ $totalFormattedNum }}), lo cual será cancelado de la siguiente manera:
    </p>

    <div class="payment-list">
        @if ($installments->isNotEmpty())
            @foreach ($installments as $idx => $inst)
                @php
                    $instMontoFormatted = (int)$inst->monto == $inst->monto 
                        ? number_format($inst->monto, 0) 
                        : number_format($inst->monto, 2);
                    $instDate = $inst->fecha_vencimiento ? \Carbon\Carbon::parse($inst->fecha_vencimiento)->format('d/m/Y') : '';
                    
                    $label = $inst->descripcion;
                    if (empty($label) || str_contains(strtolower($label), 'cuota')) {
                        $label = ($idx == 0) ? 'Primer pago' : 'Segundo pago';
                    }
                    
                    $cond = '';
                    if ($idx == 0) {
                        $cond = ', a la firma del contrato';
                    } elseif ($loop->last) {
                        $cond = ', antes de iniciar el servicio.';
                    }
                @endphp
                <div class="payment-item">
                    {{ $label }}: {{ $signo }} {{ $instMontoFormatted }} - {{ $instDate }}{{ $cond }}
                </div>
            @endforeach
        @else
            @php
                $half = $contract->total / 2;
                $halfFormatted = (int)$half == $half ? number_format($half, 0) : number_format($half, 2);
                $dEmision = $contract->fecha_emision ? \Carbon\Carbon::parse($contract->fecha_emision)->format('d/m/Y') : '';
                $dEvento = $contract->fecha_evento ? \Carbon\Carbon::parse($contract->fecha_evento)->format('d/m/Y') : '';
            @endphp
            <div class="payment-item">Primer pago: {{ $signo }} {{ $halfFormatted }} - {{ $dEmision }}, a la firma del contrato</div>
            <div class="payment-item">Segundo pago: {{ $signo }} {{ $halfFormatted }} – {{ $dEvento }}, antes de iniciar el servicio.</div>
        @endif
    </div>

    <p style="margin-top: 6px;">
        La cual será abonada a la Cuenta Corriente Soles en Interbank: <strong>750-3006347253</strong> - CCI: <strong>003-750-003006347253-71</strong> - <strong>Hardys Perú Investments S.A.C.</strong> o al número del representante <strong>980 034 767</strong> (Yape o Plin).
    </p>

    <!-- 6. TERCERO, CUARTO y Demás Cláusulas Legales -->
    @if ($operativeClauses->isNotEmpty())
        @foreach ($operativeClauses as $clause)
            @php
                $t = trim($clause->titulo);
                $c = trim($clause->contenido);
                if (!str_ends_with($t, ':')) {
                    $t .= ':';
                }
            @endphp
            <p>
                <strong>{{ $t }}</strong> {{ $c }}
            </p>
        @endforeach
    @else
        <p>
            <strong>TERCERO:</strong> Se empezará a brindar el servicio una vez culminada la ceremonia protocolar o, en su defecto, previa coordinación directa con el cliente.
        </p>

        <p>
            <strong>CUARTO:</strong> La cancelación del evento no implica la devolución del dinero; puede canjear el servicio para otra fecha que esté a disposición del proveedor.
        </p>
    @endif

    <!-- 7. Cierre Formal y Fecha de Emisión -->
    <p style="margin-top: 14px;">
        En virtud, estando las partes enteradas del contenido de todas las cláusulas del presente contrato, en señal de conformidad proceden a firmar.
    </p>

    <div class="contract-place-date">
        Tarapoto, {{ $issueDateFull }}
    </div>

    <!-- 8. Bloque de Firmas -->
    <div class="signatures-wrapper">
        <table class="signatures-table">
            <tr>
                <td class="sig-col">
                    <div class="sig-image-space">
                        @if (!empty($contract->firma_proveedor) && file_exists(public_path($contract->firma_proveedor)))
                            <img src="{{ public_path($contract->firma_proveedor) }}" alt="Firma Proveedor" />
                        @elseif(file_exists(public_path('files/contracts/signatures/sig_client_1789969806_6ab0c58ebb375.png')))
                            <!-- Trazo gráfico representativo de firma -->
                            <img src="{{ public_path('files/contracts/signatures/sig_client_1789969806_6ab0c58ebb375.png') }}" alt="Firma Proveedor" />
                        @endif
                    </div>
                    <div class="sig-separator"></div>
                    <div class="sig-person-name">{{ $providerRep }}</div>
                    <div class="sig-person-detail">Rep. {{ $business?->nombre_comercial ?: 'Shipibo Bar & Eventos' }}</div>
                </td>
                <td class="sig-col">
                    <div class="sig-image-space">
                        @if (!empty($contract->firma_cliente) && file_exists(public_path($contract->firma_cliente)))
                            <img src="{{ public_path($contract->firma_cliente) }}" alt="Firma Cliente" />
                        @endif
                    </div>
                    <div class="sig-separator"></div>
                    <div class="sig-person-name">{{ $client?->representante ?: $clientName }}</div>
                    <div class="sig-person-detail">DNI: {{ $client?->dni_representante ?: $clientDocNum }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 9. Franja Inferior Oscura con Contacto -->
    <div class="footer-bar">
        <table class="footer-table">
            <tr>
                <td class="footer-label">CONSULTAS Y COTIZACIONES:</td>
                <td class="footer-contact">
                    <svg class="footer-icon-svg" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="#d4af37"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.35-5.43-3.9-6.63-6.82l1.97-1.61a.996.996 0 0 0 .26-1.03c-.36-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
                    +51 {{ $business?->telefono ?: '980 034 767' }}
                </td>
                <td class="footer-contact">
                    <svg class="footer-icon-svg" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="#d4af37"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    {{ $business?->nombre_comercial ?: 'Shipibo Bar & Eventos' }}
                </td>
                <td class="footer-contact" style="text-align: right; padding-right: 15px;">
                    <svg class="footer-icon-svg" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="#d4af37"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    {{ $business?->email ?: 'shipiboevents@gmail.com' }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
