<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $contract->contract_number }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: a4 portrait;
        }

        * {
            box-sizing: border-box;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #1e293b;
        }

        body {
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }

        .header-logo {
            width: 25%;
            vertical-align: middle;
        }

        .header-logo img {
            max-width: 140px;
            max-height: 65px;
            object-fit: contain;
        }

        .header-business {
            width: 45%;
            vertical-align: middle;
            text-align: center;
            padding: 0 10px;
        }

        .header-business .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .header-business .company-info {
            font-size: 8.5px;
            color: #475569;
        }

        .header-box {
            width: 30%;
            vertical-align: middle;
            text-align: center;
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 8px 4px;
            background-color: #f8fafc;
        }

        .header-box .ruc-text {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .header-box .doc-title {
            font-size: 10px;
            font-weight: bold;
            background-color: #0f172a;
            color: #ffffff;
            padding: 3px 0;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .header-box .doc-number {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        .parties-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            margin-bottom: 12px;
            background-color: #f8fafc;
            padding: 8px 10px;
        }

        .parties-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }

        .party-col {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }

        .party-col p {
            margin: 2px 0;
            font-size: 9px;
        }

        .party-col strong {
            color: #0f172a;
        }

        .event-details-bar {
            width: 100%;
            border: 1px solid #93c5fd;
            background-color: #eff6ff;
            border-radius: 5px;
            padding: 6px 10px;
            margin-bottom: 12px;
        }

        .event-details-bar table {
            width: 100%;
            border-collapse: collapse;
        }

        .event-details-bar td {
            font-size: 9px;
        }

        .event-details-bar strong {
            color: #1d4ed8;
        }

        .section-heading {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 10px 0 5px 0;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 5px;
            border: 1px solid #1e293b;
            text-align: center;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 5px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .totals-words {
            width: 60%;
            vertical-align: top;
            padding-right: 15px;
        }

        .words-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 8.5px;
        }

        .totals-numbers {
            width: 40%;
            vertical-align: top;
        }

        .totals-numbers table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-numbers td {
            padding: 3px 6px;
            font-size: 9px;
        }

        .totals-numbers .total-row {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
        }

        .totals-numbers .total-row td {
            color: #ffffff;
            padding: 4px 6px;
        }

        .clause-item {
            margin-bottom: 7px;
            text-align: justify;
        }

        .clause-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .clause-body {
            font-size: 8.5px;
            color: #334155;
            line-height: 1.4;
            white-space: pre-wrap;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .signature-cell {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0 25px;
        }

        .signature-space {
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .signature-img {
            max-height: 70px;
            max-width: 160px;
            object-fit: contain;
        }

        .signature-line {
            border-top: 1px solid #0f172a;
            width: 85%;
            margin: 0 auto 5px auto;
        }

        .signature-name {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        .signature-sub {
            font-size: 8px;
            color: #475569;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if (!empty($logo) && file_exists(public_path('files/logos/' . $logo)))
                    <img src="{{ public_path('files/logos/' . $logo) }}" alt="Logo" />
                @elseif(file_exists(public_path('files/empty_logo.png')))
                    <img src="{{ public_path('files/empty_logo.png') }}" alt="Logo" />
                @endif
            </td>
            <td class="header-business">
                <div class="company-name">{{ $business?->razon_social ?: ($business?->nombre_comercial ?: 'EMPRESA PRESTADORA') }}</div>
                <div class="company-info">{{ $business?->direccion }}</div>
                <div class="company-info">Teléf: {{ $business?->telefono }} | Email: {{ $business?->email }}</div>
            </td>
            <td class="header-box">
                <div class="ruc-text">RUC N° {{ $business?->ruc ?: '20000000001' }}</div>
                <div class="doc-title">{{ $contract->title }}</div>
                <div class="doc-number">N° {{ $contract->contract_number }}</div>
            </td>
        </tr>
    </table>

    <!-- Parties Box -->
    <div class="parties-box">
        <div class="parties-title">Comparecientes / Partes Contratantes</div>
        <div class="party-col">
            <p><strong>PRESTADOR:</strong> {{ $contract->provider_name ?: ($business?->razon_social ?: $business?->nombre_comercial) }}</p>
            <p><strong>RUC / DOC:</strong> {{ $contract->provider_document ?: $business?->ruc }}</p>
            <p><strong>REPRESENTANTE:</strong> {{ $contract->provider_representative ?: ($business?->representante ?: 'Gerencia General') }}</p>
        </div>
        <div class="party-col" style="margin-left: 2%;">
            <p><strong>CLIENTE:</strong> {{ $client?->nombres }}</p>
            <p><strong>DOC. IDENTIDAD:</strong> {{ $client?->tipoDocumento?->descripcion ?: 'DNI/RUC' }}: {{ $client?->nro_documento }}</p>
            <p><strong>DIRECCIÓN:</strong> {{ $client?->direccion ?: 'No especificada' }}</p>
            <p><strong>CONTACTO:</strong> Tel: {{ $client?->telefono ?: '-' }} | Email: {{ $client?->email ?: '-' }}</p>
        </div>
    </div>

    <!-- Event Info Bar -->
    <div class="event-details-bar">
        <table>
            <tr>
                <td width="30%"><strong>FECHA DEL EVENTO:</strong> {{ \Carbon\Carbon::parse($contract->fecha_evento)->format('d/m/Y') }}</td>
                <td width="20%"><strong>HORA:</strong> {{ $contract->hora_evento ? \Carbon\Carbon::parse($contract->hora_evento)->format('H:i A') : 'Por coordinar' }}</td>
                <td width="50%"><strong>LUGAR:</strong> {{ $contract->lugar_evento ?: 'Lugar acordado por las partes' }}</td>
            </tr>
        </table>
    </div>

    <!-- Items Section -->
    <div class="section-heading">Detalle de Servicios y Productos Contratados</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="6%">Ítem</th>
                <th>Descripción del Servicio / Producto</th>
                <th width="12%">Cant.</th>
                <th width="16%">Precio Unit.</th>
                <th width="16%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $item->descripcion }}</td>
                    <td style="text-align: center;">{{ number_format($item->cantidad, 2) }}</td>
                    <td style="text-align: right;">{{ $signo }} {{ number_format($item->precio_unitario, 2) }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ $signo }} {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Table -->
    <table class="totals-table">
        <tr>
            <td class="totals-words">
                <div class="words-box">
                    <strong>SON:</strong> {{ $numero_letras }} CON 00/100 {{ $moneda }}
                    @if (!empty($contract->observaciones))
                        <div style="margin-top: 5px; border-top: 1px dashed #cbd5e1; padding-top: 3px;">
                            <strong>CONDICIONES / OBSERVACIONES:</strong><br>
                            {{ $contract->observaciones }}
                        </div>
                    @endif
                </div>
            </td>
            <td class="totals-numbers">
                <table>
                    <tr>
                        <td style="text-align: right; color: #475569;">SUBTOTAL:</td>
                        <td style="text-align: right; font-weight: bold; width: 45%;">{{ $signo }} {{ number_format($contract->subtotal, 2) }}</td>
                    </tr>
                    @if ($contract->igv > 0)
                    <tr>
                        <td style="text-align: right; color: #475569;">I.G.V. (18%):</td>
                        <td style="text-align: right; font-weight: bold;">{{ $signo }} {{ number_format($contract->igv, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">{{ $signo }} {{ number_format($contract->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Clauses Section -->
    <div class="section-heading">Términos y Cláusulas Contractuales</div>
    @foreach ($clauses as $clause)
        <div class="clause-item">
            <div class="clause-title">{{ $clause->titulo }}</div>
            <div class="clause-body">{{ $clause->contenido }}</div>
        </div>
    @endforeach

    <!-- Signatures -->
    <table class="signatures-table">
        <tr>
            <!-- Provider Signature -->
            <td class="signature-cell">
                <div class="signature-space">
                    @if (!empty($contract->firma_proveedor) && file_exists(public_path($contract->firma_proveedor)))
                        <img src="{{ public_path($contract->firma_proveedor) }}" class="signature-img" alt="Firma Prestador" />
                    @elseif(!empty($business?->logo) && file_exists(public_path('files/logos/' . $business->logo)))
                        <div style="color: #94a3b8; font-size: 8px; font-style: italic; padding-top: 25px;">[ Sello y Firma Autorizada ]</div>
                    @else
                        <div style="color: #94a3b8; font-size: 8px; font-style: italic; padding-top: 25px;">[ Sello y Firma Autorizada ]</div>
                    @endif
                </div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $contract->provider_name ?: ($business?->razon_social ?: $business?->nombre_comercial) }}</div>
                <div class="signature-sub">EL PRESTADOR DE SERVICIOS</div>
                <div class="signature-sub">RUC: {{ $contract->provider_document ?: $business?->ruc }}</div>
            </td>

            <!-- Client Signature -->
            <td class="signature-cell">
                <div class="signature-space">
                    @if (!empty($contract->firma_cliente) && file_exists(public_path($contract->firma_cliente)))
                        <img src="{{ public_path($contract->firma_cliente) }}" class="signature-img" alt="Firma Digital Cliente" />
                    @else
                        <div style="color: #94a3b8; font-size: 8px; font-style: italic; padding-top: 25px;">[ Firma del Cliente ]</div>
                    @endif
                </div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $client?->nombres }}</div>
                <div class="signature-sub">EL CLIENTE CONTRATANTE</div>
                <div class="signature-sub">{{ $client?->tipoDocumento?->descripcion ?: 'DOC' }}: {{ $client?->nro_documento }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Contrato emitido el {{ \Carbon\Carbon::parse($contract->fecha_emision)->format('d/m/Y') }} | Documento válido según la legislación aplicable | Página 1
    </div>

</body>
</html>
