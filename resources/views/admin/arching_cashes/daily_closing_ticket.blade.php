<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja del Día - {{ $currentDate }}</title>
    <style>
        @page {
            margin: 8px 10px;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }

        .ticket {
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .subtitle {
            font-size: 11px;
            font-weight: 700;
            margin-top: 4px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .separator {
            border-top: 1px dashed #9ca3af;
            margin: 8px 0;
        }

        .meta-row,
        .summary-row {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-row td,
        .summary-row td {
            padding: 2px 0;
            vertical-align: top;
        }

        .summary-label {
            width: 62%;
        }

        .summary-value {
            width: 38%;
            text-align: right;
            font-weight: 700;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-size: 10px;
            color: #374151;
        }

        .total-box {
            background: #f3f4f6;
            border-radius: 6px;
            padding: 6px 8px;
            margin-top: 6px;
        }

        .sign-area {
            margin-top: 30px;
            width: 100%;
        }

        .sign-line {
            border-top: 1px solid #4b5563;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="text-center">
            <div class="title">{{ $business?->nombre_comercial ?: 'EasyStock' }}</div>
            @if (!empty($business?->razon_social))
                <div>{{ $business->razon_social }}</div>
            @endif
            @if (!empty($business?->ruc))
                <div>RUC: {{ $business->ruc }}</div>
            @endif
            @if (!empty($business?->direccion))
                <div class="muted">{{ $business->direccion }}</div>
            @endif
            <div class="subtitle">CIERRE DE CAJA DEL DÍA</div>
        </div>

        <table class="meta-row">
            <tr>
                <td class="muted">Fecha de Cierre</td>
                <td class="text-right"><strong>{{ $currentDate }}</strong></td>
            </tr>
            <tr>
                <td class="muted">Hora Emisión</td>
                <td class="text-right">{{ $currentTime }}</td>
            </tr>
            <tr>
                <td class="muted">Caja</td>
                <td class="text-right">{{ $context['assignedCash']?->descripcion ?? 'Caja General' }}</td>
            </tr>
            <tr>
                <td class="muted">Almacén</td>
                <td class="text-right">{{ $context['warehouse']?->descripcion ?? 'Principal' }}</td>
            </tr>
            <tr>
                <td class="muted">Responsable</td>
                <td class="text-right">{{ $user->nombres }}</td>
            </tr>
            <tr>
                <td class="muted">Estado Caja</td>
                <td class="text-right">{{ $context['openArching'] ? 'Abierta' : 'Cerrada' }}</td>
            </tr>
        </table>

        <div class="separator"></div>

        <div class="section-title">1. Ventas del Día</div>
        <table class="summary-row">
            <tr>
                <td class="summary-label">Ventas Pagadas ({{ $summary['sales_paid_count'] }})</td>
                <td class="summary-value">{{ $signo }} {{ number_format($summary['sales_paid_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Ventas Pendientes / Crédito ({{ $summary['sales_pending_count'] }})</td>
                <td class="summary-value">{{ $signo }} {{ number_format($summary['sales_pending_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Devoluciones / N.C. ({{ $summary['sales_returned_count'] }})</td>
                <td class="summary-value text-right">-{{ $signo }} {{ number_format($summary['sales_returned_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Comprobantes Anulados ({{ $summary['sales_cancelled_count'] }})</td>
                <td class="summary-value text-right">{{ $signo }} {{ number_format($summary['sales_cancelled_total'], 2) }}</td>
            </tr>
        </table>

        <div class="separator"></div>

        <div class="section-title">2. Gastos del Día</div>
        <table class="summary-row">
            <tr>
                <td class="summary-label">Egresos / Gastos Registrados ({{ $summary['expenses_count'] }})</td>
                <td class="summary-value text-right">-{{ $signo }} {{ number_format($summary['expenses_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="font-size: 10px; color: #6b7280;">Egresos en Efectivo de Caja</td>
                <td class="summary-value text-right" style="font-size: 10px;">-{{ $signo }} {{ number_format($summary['expenses_cash_total'], 2) }}</td>
            </tr>
        </table>

        <div class="separator"></div>

        <div class="section-title">3. Cobros por Método de Pago</div>
        <table class="summary-row">
            @foreach ($summary['payment_methods'] as $method)
                @if ($method['total'] > 0 || $method['label'] === 'Efectivo')
                    <tr>
                        <td class="summary-label">{{ $method['label'] }} ({{ $method['count'] }})</td>
                        <td class="summary-value">{{ $signo }} {{ number_format($method['total'], 2) }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        <div class="separator"></div>

        <div class="section-title">4. Arqueo de Efectivo en Caja</div>
        <table class="summary-row">
            <tr>
                <td class="summary-label">(+) Saldo Inicial Apertura</td>
                <td class="summary-value">{{ $signo }} {{ number_format($summary['opening_amount'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">(+) Ventas Pagadas en Efectivo</td>
                <td class="summary-value">{{ $signo }} {{ number_format($summary['payment_methods']['Efectivo']['total'] ?? $summary['sales_paid_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">(-) Gastos Pagados en Efectivo</td>
                <td class="summary-value">-{{ $signo }} {{ number_format($summary['expenses_cash_total'], 2) }}</td>
            </tr>
        </table>

        <div class="total-box">
            <table class="summary-row">
                <tr>
                    <td class="summary-label"><strong>EFECTIVO ESPERADO EN CAJA</strong></td>
                    <td class="summary-value" style="font-size: 13px;">
                        <strong>{{ $signo }} {{ number_format($summary['expected_cash_in_box'], 2) }}</strong>
                    </td>
                </tr>
            </table>
        </div>

        <table class="sign-area">
            <tr>
                <td style="width: 50%;">
                    <div class="sign-line">
                        {{ $user->nombres }}<br>
                        <span class="muted">Cajero(a)</span>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="sign-line">
                        Supervisor / Admin<br>
                        <span class="muted">V°B° Conforme</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
