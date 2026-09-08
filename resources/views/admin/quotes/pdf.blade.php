<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $quote->serie }}-{{ $quote->correlativo }}</title>

    <style>
        /* ==========================================================================
           [FIXED BRANDING / ESTILO / MODIFICABLE]
           CONFIGURACIÓN DE MARCA Y ESTILOS VISUALES (HS COCTELERÍA / HARRY SALAZAR)
           Modifique esta sección cuando el cliente proporcione su manual de marca,
           paleta de colores definitiva o tipografías corporativas.
           ========================================================================== */
        :root {
            --brand-primary: #0f172a;       /* Color primario corporativo */
            --brand-secondary: #1e293b;     /* Encabezados de tabla */
            --brand-accent: #b45309;        /* Tono dorado coctelería / bar */
            --brand-border: #cbd5e1;        /* Bordes de tablas y contenedores */
            --brand-bg-light: #f8fafc;      /* Fondo alterno suave */
            --brand-bg-accent: #fef3c7;     /* Fondo de avisos / condiciones */
        }

        @page {
            margin: 15mm 14mm 15mm 14mm;
            size: a4 portrait;
        }

        * {
            box-sizing: border-box;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5px;
            color: #1e293b;
        }

        body {
            margin: 0;
            padding: 0;
            line-height: 1.42;
        }

        /* Cabecera / Membrete Corporativo */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
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
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .header-business .company-slogan {
            font-size: 8px;
            font-weight: bold;
            color: #b45309;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .header-business .company-info {
            font-size: 8px;
            color: #475569;
        }

        /* Recuadro RUC y Numeración de Cotización */
        .header-box {
            width: 30%;
            vertical-align: middle;
            text-align: center;
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 6px 4px;
            background-color: #f8fafc;
        }

        .header-box .ruc-text {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .header-box .doc-title {
            font-size: 9.5px;
            font-weight: bold;
            background-color: #0f172a;
            color: #ffffff;
            padding: 3px 0;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .header-box .doc-number {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        /* Bloque de Información del Cliente */
        .client-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            margin-bottom: 12px;
            background-color: #f8fafc;
            padding: 7px 9px;
        }

        .client-box-title {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }

        .client-col {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }

        .client-col p {
            margin: 1.5px 0;
            font-size: 8.5px;
            color: #334155;
        }

        .client-col strong {
            color: #0f172a;
            font-weight: 700;
        }

        /* Títulos de Sección */
        .section-heading {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 10px 0 4px 0;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 2px;
            letter-spacing: 0.3px;
        }

        /* Tabla de Ítems */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            padding: 5px 6px;
            border: 1px solid #1e293b;
            text-align: center;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            vertical-align: middle;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Tabla de Totales */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .totals-words {
            width: 58%;
            vertical-align: top;
            padding-right: 12px;
        }

        .words-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 5px 7px;
            font-size: 8px;
        }

        .totals-numbers {
            width: 42%;
            vertical-align: top;
        }

        .totals-numbers table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-numbers td {
            padding: 3px 6px;
            font-size: 8.5px;
        }

        .totals-numbers .total-row {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.5px;
        }

        .totals-numbers .total-row td {
            color: #ffffff;
            padding: 4px 6px;
        }

        /* Bloque de Términos Comerciales y Cuentas Bancarias */
        .terms-box {
            width: 100%;
            border: 1px solid #fde68a;
            background-color: #fffbeb;
            border-radius: 5px;
            padding: 7px 9px;
            margin-bottom: 12px;
            font-size: 8px;
            color: #92400e;
        }

        .terms-box .terms-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #78350f;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .terms-box ul {
            margin: 0;
            padding-left: 15px;
        }

        .terms-box li {
            margin-bottom: 2px;
            color: #78350f;
        }

        .bank-accounts-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 5px;
        }

        .bank-accounts-table td {
            padding: 6px 10px;
            font-size: 8px;
            vertical-align: top;
        }

        /* Firmas y Conformidad */
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

        .signature-line {
            border-top: 1px solid #0f172a;
            width: 80%;
            margin: 40px auto 4px auto;
        }

        .signature-name {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        .signature-sub {
            font-size: 7.5px;
            color: #475569;
        }

        /* Pie de Página */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    @php
        $documentTitle = mb_strtoupper($type_document->descripcion ?? 'PROFORMA / COTIZACIÓN');
    @endphp

    <!-- ==========================================================================
         [FIXED BRANDING / ESTILO / MODIFICABLE]
         CABECERA CORPORATIVA Y CAJA FISCAL
         Fácilmente sustituible con el membrete oficial del cliente.
         ========================================================================== -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if (!empty($logo) && file_exists(public_path('files/logos/' . $logo)))
                    <img src="{{ public_path('files/logos/' . $logo) }}" alt="Logo" />
                @elseif(!empty($business?->logo) && file_exists(public_path($business->logo)))
                    <img src="{{ public_path($business->logo) }}" alt="Logo" />
                @elseif(file_exists(public_path('files/empty_logo.png')))
                    <img src="{{ public_path('files/empty_logo.png') }}" alt="Logo" />
                @endif
            </td>
            <td class="header-business">
                <div class="company-name">{{ $business?->nombre_comercial ?: ($business?->razon_social ?: 'HS COCTELERÍA') }}</div>
                <div class="company-slogan">Barras Móviles, Coctelería de Autor y Catering para Eventos</div>
                <div class="company-info">
                    @php
                        $direccionPrincipal = $business->direccion_principal ?? $business->direccion ?? null;
                    @endphp
                    {{ $direccionPrincipal ?: 'Lima, Perú' }}
                </div>
                <div class="company-info">Tel: {{ $business?->telefono ?: '-' }} | Email: {{ $business?->email ?: '-' }}</div>
            </td>
            <td class="header-box">
                <div class="ruc-text">RUC N° {{ $business?->ruc ?: '00000000000' }}</div>
                <div class="doc-title">{{ $documentTitle }}</div>
                <div class="doc-number">N° {{ $quote->serie }}-{{ $quote->correlativo }}</div>
            </td>
        </tr>
    </table>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         FICHA DEL CLIENTE Y FECHAS DE EMISIÓN / VIGENCIA
         ========================================================================== -->
    <div class="client-box">
        <div class="client-box-title">Datos del Cliente y Emisión</div>
        <div class="client-col">
            <p><strong>CLIENTE / EMPRESA:</strong> {{ $client->nombres }}</p>
            <p><strong>DNI / RUC:</strong> {{ $client->nro_documento ?: '-' }}</p>
            <p><strong>DIRECCIÓN:</strong> {{ $client->direccion ?: 'No especificada' }}</p>
            <p><strong>CONTACTO:</strong> Tel: {{ $client->telefono ?: '-' }} | Email: {{ $client->email ?: '-' }}</p>
        </div>
        <div class="client-col" style="margin-left: 2%;">
            <p><strong>FECHA DE EMISIÓN:</strong> {{ \Carbon\Carbon::parse($quote->fecha_emision)->format('d/m/Y') }}</p>
            <p><strong>VALIDEZ DE PROPUESTA:</strong> {{ !empty($quote->fecha_vencimiento) ? \Carbon\Carbon::parse($quote->fecha_vencimiento)->format('d/m/Y') : '15 días calendario' }}</p>
            <p><strong>MONEDA:</strong> {{ $moneda }} ({{ $signo }})</p>
            <p><strong>ASESOR / VENDEDOR:</strong> {{ $quote->user->nombre ?? 'HS Coctelería' }}</p>
        </div>
    </div>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         DETALLE DE SERVICIOS Y PRODUCTOS COTIZADOS
         ========================================================================== -->
    <div class="section-heading">Propuesta Económica: Servicios de Barra, Cristalería e Insumos</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th>Descripción del Servicio / Menaje / Cócteles</th>
                <th width="10%">Cant.</th>
                <th width="10%">Unidad</th>
                <th width="14%">P. Unitario</th>
                <th width="14%">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detail as $i => $item)
                <tr>
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $item['producto'] }}</strong>
                        @if(!empty($item['codigo_interno']))
                            <span style="font-size: 7.5px; color: #64748b;"> (Cód: {{ $item['codigo_interno'] }})</span>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ intval($item['cantidad']) == $item['cantidad'] ? intval($item['cantidad']) : number_format($item['cantidad'], 2) }}</td>
                    <td style="text-align: center;">{{ $item['unidad'] ?? 'UND' }}</td>
                    <td style="text-align: right;">{{ $signo }} {{ number_format($item['precio_unitario'], 2, '.', '') }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ $signo }} {{ number_format($item['precio_total'], 2, '.', '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         TOTALES Y MONTO EN LETRAS
         ========================================================================== -->
    <table class="totals-table">
        <tr>
            <td class="totals-words">
                <div class="words-box">
                    <strong>SON:</strong> {{ $numero_letras }} CON 00/100 {{ $moneda }}
                    @if (!empty($quote->observaciones))
                        <div style="margin-top: 4px; border-top: 1px dashed #cbd5e1; padding-top: 2px;">
                            <strong>OBSERVACIONES / NOTAS DE ATENCIÓN:</strong><br>
                            {{ $quote->observaciones }}
                        </div>
                    @endif
                </div>
            </td>
            <td class="totals-numbers">
                <table>
                    <tr>
                        <td style="text-align: right; color: #475569;">SUBTOTAL:</td>
                        <td style="text-align: right; font-weight: bold; width: 45%;">{{ $signo }} {{ number_format($quote->subtotal, 2, '.', '') }}</td>
                    </tr>
                    @if ($quote->igv > 0)
                    <tr>
                        <td style="text-align: right; color: #475569;">I.G.V. (18%):</td>
                        <td style="text-align: right; font-weight: bold;">{{ $signo }} {{ number_format($quote->igv, 2, '.', '') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td style="text-align: right;">TOTAL A PAGAR:</td>
                        <td style="text-align: right;">{{ $signo }} {{ number_format($quote->total, 2, '.', '') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ==========================================================================
         [FIXED BRANDING / ESTILO / MODIFICABLE]
         CONDICIONES COMERCIALES DEL SERVICIO (HS COCTELERÍA)
         Fácilmente modificable con las políticas de reserva del cliente.
         ========================================================================== -->
    <div class="terms-box">
        <div class="terms-title"><i class="ri-information-line"></i> Condiciones Comerciales y Reserva de Fecha:</div>
        <ul>
            <li><strong>Reserva de fecha:</strong> Se confirma mediante el pago del 50% de anticipo inicial junto con la firma del Contrato de Servicios correspondiente.</li>
            <li><strong>Saldo:</strong> El 50% restante deberá cancelarse según el cronograma acordado previo al inicio del montaje del evento.</li>
            <li><strong>Fondo de Garantía:</strong> En contratos con alquiler de cristalería fina y barras móviles, aplica una garantía del 20% reintegrable tras el inventario de desmontaje.</li>
            <li><strong>Vigencia de la oferta:</strong> Los precios y disponibilidad de stock cotizados tienen validez de 15 días calendario desde la fecha de emisión.</li>
            <li><strong>Montaje y Desmontaje:</strong> El equipo de bartenders llega al recinto con 2 horas de anticipación para el setup y enfriamiento de cristalería.</li>
        </ul>
    </div>

    <!-- ==========================================================================
         [FIXED BRANDING / ESTILO / MODIFICABLE]
         CUENTAS BANCARIAS Y MEDIOS DE PAGO
         Sustituir números de cuenta y CCI oficiales de HS Coctelería aquí.
         ========================================================================== -->
    <table class="bank-accounts-table">
        <tr>
            <td width="33%">
                <strong>BCP SOLES:</strong><br>
                Cta: 191-00000000-0-00<br>
                CCI: 002-191000000000000000<br>
                <span style="color: #64748b;">Titular: HS Coctelería</span>
            </td>
            <td width="33%">
                <strong>BBVA SOLES:</strong><br>
                Cta: 0011-0000-0000000000<br>
                CCI: 011-000000000000000000<br>
                <span style="color: #64748b;">Titular: HS Coctelería</span>
            </td>
            <td width="34%">
                <strong>BILLETERAS DIGITALES:</strong><br>
                Yape / Plin: {{ $business?->telefono ?: '999 999 999' }}<br>
                <span style="color: #64748b;">Enviar comprobante a: {{ $business?->email ?: 'contacto@hscocteleria.com' }}</span>
            </td>
        </tr>
    </table>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         FIRMAS Y ACEPTACIÓN DE LA PROPUESTA
         ========================================================================== -->
    <table class="signatures-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $business?->nombre_comercial ?: ($business?->razon_social ?: 'HS COCTELERÍA') }}</div>
                <div class="signature-sub">ÁREA COMERCIAL / EVENTOS</div>
                <div class="signature-sub">RUC: {{ $business?->ruc ?: '00000000000' }}</div>
            </td>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $client->nombres }}</div>
                <div class="signature-sub">CONFORMIDAD Y ACEPTACIÓN DEL CLIENTE</div>
                <div class="signature-sub">DNI / RUC: {{ $client->nro_documento ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <!-- ==========================================================================
         [FIXED BRANDING / ESTILO / MODIFICABLE]
         PIE DE PÁGINA
         ========================================================================== -->
    <div class="footer">
        Cotización N° {{ $quote->serie }}-{{ $quote->correlativo }} | Emitido por {{ $business?->nombre_comercial ?: 'HS Coctelería' }} | Proforma referencial no válida como comprobante de pago
    </div>

</body>
</html>
