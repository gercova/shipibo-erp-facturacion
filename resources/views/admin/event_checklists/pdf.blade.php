<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta Checklist {{ $checklist->code }}</title>
    <style>
        @page {
            margin: 16mm 14mm 16mm 14mm;
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
            line-height: 1.4;
        }

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
            max-width: 130px;
            max-height: 60px;
        }

        .header-business {
            width: 45%;
            vertical-align: middle;
            text-align: center;
            padding: 0 8px;
        }

        .header-business .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .header-business .company-info {
            font-size: 8px;
            color: #475569;
        }

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
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .header-box .doc-title {
            font-size: 9px;
            font-weight: bold;
            background-color: #0f172a;
            color: #ffffff;
            padding: 2px 0;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .header-box .doc-number {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }

        .info-table td {
            padding: 5px 8px;
            vertical-align: top;
            font-size: 8.5px;
        }

        .info-table td strong {
            color: #0f172a;
        }

        .guarantee-box {
            width: 100%;
            border: 1.5px solid {{ $checklist->hasIncidents() ? '#dc2626' : '#16a34a' }};
            background-color: {{ $checklist->hasIncidents() ? '#fef2f2' : '#f0fdf4' }};
            border-radius: 5px;
            padding: 8px 10px;
            margin-bottom: 12px;
        }

        .guarantee-box .title {
            font-size: 10px;
            font-weight: bold;
            color: {{ $checklist->hasIncidents() ? '#b91c1c' : '#15803d' }};
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #0f172a;
            text-align: center;
        }

        .items-table td {
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            vertical-align: middle;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .badge-warning {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 1px solid #475569;
            padding-top: 6px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($business->logo) && file_exists(public_path($business->logo)))
                    <img src="{{ public_path($business->logo) }}" alt="Logo">
                @else
                    <div style="font-size: 14px; font-weight: bold; color: #0f172a;">{{ $business->nombre_comercial ?? 'HS COCTELERIA' }}</div>
                @endif
            </td>
            <td class="header-business">
                <div class="company-name">{{ $business->nombre_comercial ?? $business->razon_social ?? 'HS COCTELERIA' }}</div>
                <div class="company-info">{{ $business->direccion ?? '' }}</div>
                <div class="company-info">Tel: {{ $business->telefono ?? '-' }} | Email: {{ $business->email ?? '-' }}</div>
            </td>
            <td class="header-box">
                <div class="ruc-text">RUC: {{ $business->ruc ?? '00000000000' }}</div>
                <div class="doc-title">ACTA DE CONTROL Y CIERRE</div>
                <div class="doc-number">{{ $checklist->code }}</div>
            </td>
        </tr>
    </table>

    <!-- Event & Contract Info -->
    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>N° CONTRATO:</strong> {{ $checklist->contract->contract_number ?? 'S/N' }}<br>
                <strong>CLIENTE:</strong> {{ $checklist->contract->client->nombres ?? 'No especificado' }}<br>
                <strong>DNI / RUC:</strong> {{ $checklist->contract->client->nro_documento ?? '-' }}<br>
                <strong>ALMACÉN ORIGEN:</strong> {{ $checklist->warehouse->nombre ?? 'Principal' }}
            </td>
            <td width="50%">
                <strong>FECHA DEL EVENTO:</strong> {{ $checklist->contract ? \Carbon\Carbon::parse($checklist->contract->fecha_evento)->format('d/m/Y') : '-' }} {{ $checklist->contract?->hora_evento ? \Carbon\Carbon::parse($checklist->contract->hora_evento)->format('H:i') : '' }}<br>
                <strong>LUGAR DEL EVENTO:</strong> {{ $checklist->contract->lugar_evento ?? '-' }}<br>
                <strong>SUPERVISOR DESPACHO:</strong> {{ $checklist->user->nombre ?? 'Admin' }}<br>
                <strong>FECHA REGISTRO:</strong> {{ \Carbon\Carbon::parse($checklist->created_at)->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>

    <!-- Guarantee Alert & Contractual Clause Reference -->
    <div class="guarantee-box">
        <div class="title">
            {{ $checklist->hasIncidents() ? 'ALERTA: SE REGISTRARON INCIDENCIAS / ROTURAS (Fondo de Garantía Comprometido)' : 'CONFORMIDAD DE RETORNO (Fondo de Garantía 100% Liberable)' }}
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="font-size: 8.5px; vertical-align: middle;">
                    @if($checklist->hasIncidents())
                        Se reportaron <strong>{{ $checklist->total_incidencias }} ítem(s) con faltantes o roturas</strong> durante el desmontaje del evento. Según la Cláusula Octava del Contrato {{ $checklist->contract->contract_number ?? '' }}, procede la liquidación de costos con cargo al Fondo de Garantía.
                    @else
                        Todos los equipos, cristalería y herramientas de barra fueron recibidos completos y en perfecto estado. Procede la devolución o liberación íntegra del fondo de garantía contractual.
                    @endif
                </td>
                <td style="width: 32%; text-align: right; vertical-align: middle;">
                    <div style="font-size: 8px; color: #64748b;">FONDO GARANTÍA (20%):</div>
                    <div style="font-size: 12px; font-weight: bold; color: #0f172a;">{{ $signo }} {{ number_format($guaranteeAmount, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Detail Table with 6 Return Categories -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="28%" style="text-align: left;">DESCRIPCIÓN DEL ÍTEM</th>
                <th width="8%">ENVIADO</th>
                <th width="8%">OK</th>
                <th width="8%">DAÑADO</th>
                <th width="8%">ROTO</th>
                <th width="8%">FALTANTE</th>
                <th width="8%">CONSUMO</th>
                <th width="20%">ESTADO / OBSERVACIÓN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($checklist->items as $index => $item)
            @php
                $sentQty = (float) ($item->qty_enviada > 0 ? $item->qty_enviada : ($item->cantidad_llevada > 0 ? $item->cantidad_llevada : $item->cantidad_planeada));
            @endphp
            <tr>
                <td style="text-align: center; color: #64748b;">{{ $loop->iteration }}</td>
                <td>
                    <strong style="color: #0f172a;">{{ $item->descripcion }}</strong>
                    <div style="font-size: 7.5px; color: #64748b;">{{ $item->categoria }}</div>
                </td>
                <td style="text-align: center; font-weight: bold; background-color: #f1f5f9;">
                    {{ rtrim(rtrim(number_format($sentQty, 2), '0'), '.') }}
                </td>
                <td style="text-align: center; color: #047857; font-weight: bold;">
                    {{ rtrim(rtrim(number_format($item->qty_ok, 2), '0'), '.') }}
                </td>
                <td style="text-align: center; color: #b45309;">
                    {{ $item->qty_danada > 0 ? rtrim(rtrim(number_format($item->qty_danada, 2), '0'), '.') : '-' }}
                </td>
                <td style="text-align: center; color: #b91c1c; font-weight: bold;">
                    {{ $item->qty_quebrada > 0 ? rtrim(rtrim(number_format($item->qty_quebrada, 2), '0'), '.') : '-' }}
                </td>
                <td style="text-align: center; color: #dc2626; font-weight: bold;">
                    {{ $item->qty_faltante > 0 ? rtrim(rtrim(number_format($item->qty_faltante, 2), '0'), '.') : '-' }}
                </td>
                <td style="text-align: center; color: #0284c7;">
                    {{ $item->qty_consumida > 0 ? rtrim(rtrim(number_format($item->qty_consumida, 2), '0'), '.') : '-' }}
                </td>
                <td>
                    @if($item->tiene_incidencia || ($item->qty_quebrada + $item->qty_faltante + $item->qty_danada) > 0)
                        <span class="badge badge-danger">Incidencia</span>
                        <span style="font-size: 7.5px; color: #dc2626;">{{ $item->observaciones ?: $item->tipo_incidencia }}</span>
                    @elseif($item->isReturnBalanced())
                        <span class="badge badge-success">Conforme</span>
                    @else
                        <span class="badge badge-warning">Descuadre: {{ $item->return_discrepancy }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Incidents & Additional Charges Summary Table -->
    @php
        $affectedItems = $checklist->items->filter(function($i) {
            return $i->tiene_incidencia || ($i->qty_quebrada > 0 || $i->qty_faltante > 0 || $i->qty_danada > 0);
        });
        $grandTotalLiquidacion = 0.00;
    @endphp
    @if($affectedItems->count() > 0)
    <div style="font-size: 9px; font-weight: bold; color: #b91c1c; text-transform: uppercase; margin-bottom: 4px;">
        LIQUIDACIÓN DE ROTURAS / FALTANTES / REPARACIONES (CARGOS ADICIONALES)
    </div>
    <table class="items-table" style="margin-bottom: 15px;">
        <thead>
            <tr style="background-color: #b91c1c;">
                <th width="5%" style="background-color: #b91c1c;">#</th>
                <th width="35%" style="background-color: #b91c1c; text-align: left;">ÍTEM AFECTADO</th>
                <th width="15%" style="background-color: #b91c1c;">DESGLOSE INCIDENCIA</th>
                <th width="15%" style="background-color: #b91c1c;">COSTO REPOSICIÓN</th>
                <th width="15%" style="background-color: #b91c1c; text-align: right;">SUBTOTAL CARGO</th>
                <th width="15%" style="background-color: #b91c1c; text-align: left;">OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            @foreach($affectedItems as $item)
            @php
                $prod = $item->product;
                $costUnit = $prod ? $prod->effective_replacement_cost : (float) $item->costo_penalidad_estimado;
                $affectedQty = (float) ($item->qty_quebrada + $item->qty_faltante + $item->qty_danada);
                $subtotalCargo = round($affectedQty * $costUnit, 2);
                $grandTotalLiquidacion += $subtotalCargo;
            @endphp
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td><strong>{{ $item->descripcion }}</strong></td>
                <td style="text-align: center; font-size: 8px;">
                    @if($item->qty_quebrada > 0) <span style="color:#b91c1c;">Roto: {{ $item->qty_quebrada }}</span><br> @endif
                    @if($item->qty_faltante > 0) <span style="color:#dc2626;">Falta: {{ $item->qty_faltante }}</span><br> @endif
                    @if($item->qty_danada > 0) <span style="color:#b45309;">Daño: {{ $item->qty_danada }}</span> @endif
                </td>
                <td style="text-align: center;">{{ $signo }} {{ number_format($costUnit, 2) }}</td>
                <td style="text-align: right; font-weight: bold; color: #b91c1c;">{{ $signo }} {{ number_format($subtotalCargo, 2) }}</td>
                <td style="font-size: 7.5px; font-style: italic;">{{ $item->observaciones ?? '-' }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #fef2f2; font-weight: bold;">
                <td colspan="4" style="text-align: right; color: #991b1b;">TOTAL CARGOS ADICIONALES A LIQUIDAR:</td>
                <td style="text-align: right; color: #991b1b; font-size: 10.5px;">{{ $signo }} {{ number_format($grandTotalLiquidacion, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Signatures -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">
                    <strong>{{ $checklist->user->nombre ?? 'Supervisor de Barra / Operaciones' }}</strong><br>
                    <span style="font-size: 8px; color: #64748b;">SUPERVISOR DE BARRA / EVENTO</span>
                </div>
            </td>
            <td>
                <div class="signature-line">
                    <strong>{{ $checklist->contract->client->nombres ?? 'Cliente Contratante' }}</strong><br>
                    <span style="font-size: 8px; color: #64748b;">CLIENTE / CONFORMIDAD DE RECEPCIÓN</span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
