<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $contract->contract_number }} - {{ $contract->title }}</title>

    <style>
        /* ==========================================================================
           [FIXED BRANDING / ESTILO / MODIFICABLE]
           CONFIGURACIÓN DE MARCA Y ESTILOS VISUALES (HS COCTELERÍA / HARRY SALAZAR)
           Modifique esta sección cuando el cliente proporcione su manual de marca,
           paleta de colores definitiva, o tipografías corporativas.
           ========================================================================== */
        :root {
            --brand-primary: #0f172a;       /* Color primario (Azul noche elegante) */
            --brand-secondary: #1e293b;     /* Color secundario / encabezados tabla */
            --brand-accent: #b45309;        /* Tono dorado / coctelería premium */
            --brand-accent-light: #fef3c7;  /* Fondo dorado suave para alertas */
            --brand-text: #1e293b;          /* Color de texto principal */
            --brand-muted: #64748b;         /* Color de texto atenuado / etiquetas */
            --brand-border: #cbd5e1;        /* Bordes de tablas y contenedores */
            --brand-bg-light: #f8fafc;      /* Fondo de filas alternas y cajas */
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
            margin-bottom: 10px;
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

        /* Recuadro RUC y Numeración de Contrato */
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

        /* Bloque de Comparecientes / Partes Contratantes */
        .parties-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #f8fafc;
            padding: 7px 9px;
        }

        .parties-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }

        .party-col {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }

        .party-col p {
            margin: 1.5px 0;
            font-size: 8.5px;
            color: #334155;
        }

        .party-col strong {
            color: #0f172a;
            font-weight: 700;
        }

        /* Ficha del Evento */
        .event-details-bar {
            width: 100%;
            border: 1px solid #93c5fd;
            background-color: #eff6ff;
            border-radius: 5px;
            padding: 5px 8px;
            margin-bottom: 10px;
        }

        .event-details-bar table {
            width: 100%;
            border-collapse: collapse;
        }

        .event-details-bar td {
            font-size: 8.5px;
        }

        .event-details-bar strong {
            color: #1e3a8a;
        }

        /* Títulos de Sección */
        .section-heading {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 8px 0 4px 0;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 2px;
            letter-spacing: 0.3px;
        }

        /* Tablas de Ítems y Cuotas */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            padding: 4px 6px;
            border: 1px solid #1e293b;
            text-align: center;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 4px 6px;
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
            margin-bottom: 10px;
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
            padding: 2.5px 6px;
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
            padding: 3.5px 6px;
        }

        /* Caja de Garantía */
        .guarantee-callout {
            background-color: #fef3c7;
            border: 1.5px solid #f59e0b;
            border-radius: 4px;
            padding: 5px 8px;
            margin-bottom: 10px;
            font-size: 8px;
            color: #92400e;
        }

        .guarantee-callout strong {
            color: #78350f;
        }

        /* Cláusulas Legales */
        .clause-item {
            margin-bottom: 6px;
            text-align: justify;
        }

        .clause-title {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .clause-body {
            font-size: 8px;
            color: #334155;
            line-height: 1.38;
            white-space: pre-wrap;
        }

        /* Firmas de Conformidad */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-cell {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0 20px;
        }

        .signature-space {
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
        }

        .signature-img {
            max-height: 60px;
            max-width: 150px;
            object-fit: contain;
        }

        .signature-line {
            border-top: 1px solid #0f172a;
            width: 80%;
            margin: 0 auto 4px auto;
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
                <div class="company-info">{{ $business?->direccion }}</div>
                <div class="company-info">Teléf: {{ $business?->telefono ?: '-' }} | Email: {{ $business?->email ?: '-' }}</div>
            </td>
            <td class="header-box">
                <div class="ruc-text">RUC N° {{ $business?->ruc ?: '00000000000' }}</div>
                <div class="doc-title">{{ $contract->title ?: 'CONTRATO DE SERVICIOS' }}</div>
                <div class="doc-number">N° {{ $contract->contract_number }}</div>
            </td>
        </tr>
    </table>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         BLOQUE DE COMPARECIENTES (PRESTADOR Y CLIENTE CONTRATANTE)
         ========================================================================== -->
    <div class="parties-box">
        <div class="parties-title">Comparecientes / Partes Contratantes</div>
        <div class="party-col">
            <p><strong>PRESTADOR:</strong> {{ $contract->provider_name ?: ($business?->razon_social ?: $business?->nombre_comercial) }}</p>
            <p><strong>RUC / DOC:</strong> {{ $contract->provider_document ?: $business?->ruc }}</p>
            <p><strong>REPRESENTANTE:</strong> {{ $contract->provider_representative ?: ($business?->representante ?: 'Gerencia General') }}</p>
        </div>
        <div class="party-col" style="margin-left: 2%;">
            <p><strong>CLIENTE CONTRATANTE:</strong> {{ $client?->nombres }}</p>
            <p><strong>DOC. IDENTIDAD:</strong> {{ $client?->tipoDocumento?->descripcion ?: 'DNI/RUC' }}: {{ $client?->nro_documento }}</p>
            <p><strong>DIRECCIÓN:</strong> {{ $client?->direccion ?: 'No especificada' }}</p>
            <p><strong>CONTACTO:</strong> Tel: {{ $client?->telefono ?: '-' }} | Email: {{ $client?->email ?: '-' }}</p>
        </div>
    </div>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         FICHA TÉCNICA DEL EVENTO
         ========================================================================== -->
    <div class="event-details-bar">
        <table>
            <tr>
                <td width="30%"><strong>FECHA DEL EVENTO:</strong> {{ \Carbon\Carbon::parse($contract->fecha_evento)->format('d/m/Y') }}</td>
                <td width="20%"><strong>HORA:</strong> {{ $contract->hora_evento ? \Carbon\Carbon::parse($contract->hora_evento)->format('H:i A') : 'Por coordinar' }}</td>
                <td width="50%"><strong>LUGAR / LOCAL:</strong> {{ $contract->lugar_evento ?: 'Lugar acordado por las partes' }}</td>
            </tr>
        </table>
    </div>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         DETALLE DE SERVICIOS Y PRODUCTOS CONTRATADOS
         ========================================================================== -->
    <div class="section-heading">Detalle de Servicios de Barra, Cristalería y Menaje Contratados</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th>Descripción del Servicio / Insumo / Cristalería</th>
                <th width="12%">Cantidad</th>
                <th width="16%">Precio Unit.</th>
                <th width="16%">Importe</th>
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

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         TOTALES Y MONTO EN LETRAS
         ========================================================================== -->
    <table class="totals-table">
        <tr>
            <td class="totals-words">
                <div class="words-box">
                    <strong>SON:</strong> {{ $numero_letras }} CON 00/100 {{ $moneda }}
                    @if (!empty($contract->observaciones))
                        <div style="margin-top: 4px; border-top: 1px dashed #cbd5e1; padding-top: 2px;">
                            <strong>CONDICIONES / OBSERVACIONES ADICIONALES:</strong><br>
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
                        <td style="text-align: right;">TOTAL GENERAL:</td>
                        <td style="text-align: right;">{{ $signo }} {{ number_format($contract->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         CRONOGRAMA DE PAGOS (50% ANTICIPO Y CUOTAS)
         ========================================================================== -->
    @if ($contract->installments && $contract->installments->count() > 0)
    <div class="section-heading">Cronograma de Pagos y Condiciones Financieras</div>
    <table class="items-table" style="margin-bottom: 8px;">
        <thead>
            <tr>
                <th width="6%">N°</th>
                <th width="40%">Concepto / Cuota</th>
                <th width="10%">%</th>
                <th width="22%">Fecha de Vencimiento</th>
                <th width="22%">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contract->installments as $inst)
                <tr>
                    <td style="text-align: center;">{{ $inst->numero_cuota }}</td>
                    <td>
                        {{ $inst->descripcion }}
                        @if ($inst->estado == 1)
                            <span style="color: #16a34a; font-weight: bold; font-size: 7.5px;"> [CANCELADO]</span>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ number_format($inst->porcentaje, 1) }}%</td>
                    <td style="text-align: center; font-weight: bold;">{{ \Carbon\Carbon::parse($inst->fecha_vencimiento)->format('d/m/Y') }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ $signo }} {{ number_format($inst->monto, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- ==========================================================================
         [FIXED BRANDING / ESTILO / MODIFICABLE]
         LLAMADO DEL FONDO DE GARANTÍA (20%) - CLÁUSULA OCTAVA
         Texto legal estipulado para respaldo de cristalería y barras móviles.
         ========================================================================== -->
    <div class="guarantee-callout">
        <strong>FONDO DE GARANTÍA CONTRACTUAL POR CRISTALERÍA Y EQUIPAMIENTO (20%):</strong>
        Conforme a la Cláusula Octava del presente documento, el fondo de garantía fijado es de 
        <strong>{{ $signo }} {{ number_format($contract->total * 0.20, 2) }}</strong>. 
        Dicho importe respalda eventuales roturas, pérdidas o daños en cristalería, herramientas de barra y estaciones móviles, y será reintegrado al cliente tras la verificación conforme en el desmontaje del evento mediante el Acta de Checklist correspondiente.
    </div>

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         CLÁUSULAS CONTRACTUALES DINÁMICAS (ALIMENTADAS POR EL EDITOR DE CLÁUSULAS)
         El usuario puede modificar, agregar o precargar cláusulas desde el formulario.
         NO HARDCODEAR TEXTOS LEGALES AQUÍ PARA MANTENER LA FLEXIBILIDAD.
         ========================================================================== -->
    <div class="section-heading">Términos y Cláusulas Contractuales</div>
    @foreach ($clauses as $clause)
        <div class="clause-item">
            <div class="clause-title">{{ $clause->titulo }}</div>
            <div class="clause-body">{{ $clause->contenido }}</div>
        </div>
    @endforeach

    <!-- ==========================================================================
         [DATOS DINÁMICOS DEL SISTEMA / NO TOCAR]
         FIRMAS DIGITALES Y CONFORMIDAD LEGAL
         Soporta firma digital vía Canvas o subida de imagen de cliente y prestador.
         ========================================================================== -->
    <table class="signatures-table">
        <tr>
            <!-- Firma Prestador -->
            <td class="signature-cell">
                <div class="signature-space">
                    @if (!empty($contract->firma_proveedor) && file_exists(public_path($contract->firma_proveedor)))
                        <img src="{{ public_path($contract->firma_proveedor) }}" class="signature-img" alt="Firma Prestador" />
                    @else
                        <div style="color: #94a3b8; font-size: 8px; font-style: italic; padding-top: 25px;">[ Sello y Firma Autorizada ]</div>
                    @endif
                </div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $contract->provider_name ?: ($business?->razon_social ?: $business?->nombre_comercial) }}</div>
                <div class="signature-sub">EL PRESTADOR DE SERVICIOS</div>
                <div class="signature-sub">RUC: {{ $contract->provider_document ?: $business?->ruc }}</div>
            </td>

            <!-- Firma Cliente Contratante -->
            <td class="signature-cell">
                <div class="signature-space">
                    @if (!empty($contract->firma_cliente) && file_exists(public_path($contract->firma_cliente)))
                        <img src="{{ public_path($contract->firma_cliente) }}" class="signature-img" alt="Firma Digital Cliente" />
                    @else
                        <div style="color: #94a3b8; font-size: 8px; font-style: italic; padding-top: 25px;">[ Firma del Cliente Contratante ]</div>
                    @endif
                </div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $client?->nombres }}</div>
                <div class="signature-sub">EL CLIENTE CONTRATANTE</div>
                <div class="signature-sub">{{ $client?->tipoDocumento?->descripcion ?: 'DOC' }}: {{ $client?->nro_documento }}</div>
            </td>
        </tr>
    </table>

    <!-- ==========================================================================
         [FIXED BRANDING / ESTILO / MODIFICABLE]
         PIE DE PÁGINA Y NOTA LEGAL
         ========================================================================== -->
    <div class="footer">
        Contrato de Servicios para Eventos emitido el {{ \Carbon\Carbon::parse($contract->fecha_emision)->format('d/m/Y') }} | Documento privado con valor legal según el Código Civil Peruano | {{ $business?->nombre_comercial ?: 'HS Coctelería' }}
    </div>

</body>
</html>
