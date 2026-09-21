<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Productos</title>
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11px; }
        .title { font-size: 16px; font-weight: bold; color: #1e293b; }
        .subtitle { font-size: 11px; color: #64748b; }
        .kpi-title { font-weight: bold; background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .kpi-value { font-weight: bold; background-color: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; text-align: center; }
        .th-header { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #334155; padding: 6px; }
        .td-cell { border: 1px solid #e2e8f0; padding: 4px; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge-prod { color: #15803d; font-weight: bold; }
        .badge-serv { color: #0369a1; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $totalItems = $products->count();
        $totalProducts = $products->where('opcion', 1)->count();
        $totalServices = $products->where('opcion', 2)->count();
        $totalRentable = $products->where('rentable', true)->count();
    @endphp

    <table>
        <tr>
            <td colspan="19" class="title">REPORTE INTEGRAL DE CATÁLOGO DE PRODUCTOS Y SERVICIOS</td>
        </tr>
        <tr>
            <td colspan="19" class="subtitle">Fecha y hora de exportación: {{ now()->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr></tr>
        <tr>
            <td colspan="3" class="kpi-title">TOTAL ÍTEMS</td>
            <td class="kpi-value">{{ $totalItems }}</td>
            <td></td>
            <td colspan="2" class="kpi-title">PRODUCTOS (BIENES)</td>
            <td class="kpi-value">{{ $totalProducts }}</td>
            <td></td>
            <td colspan="2" class="kpi-title">SERVICIOS</td>
            <td class="kpi-value">{{ $totalServices }}</td>
            <td></td>
            <td colspan="2" class="kpi-title">ALQUILABLE / EVENTOS</td>
            <td class="kpi-value">{{ $totalRentable }}</td>
        </tr>
        <tr></tr>
        <thead>
            <tr>
                <th class="th-header" style="width: 60px;"># ID</th>
                <th class="th-header" style="width: 120px;">Cód. Interno</th>
                <th class="th-header" style="width: 130px;">Cód. Barras</th>
                <th class="th-header" style="width: 110px;">Cód. SUNAT</th>
                <th class="th-header" style="width: 100px;">Tipo de Ítem</th>
                <th class="th-header" style="width: 260px;">Descripción / Nombre</th>
                <th class="th-header" style="width: 140px;">Categoría</th>
                <th class="th-header" style="width: 140px;">Unidad de Medida</th>
                <th class="th-header" style="width: 160px;">Tipo Afectación IGV</th>
                <th class="th-header" style="width: 80px;">Tasa IGV</th>
                <th class="th-header" style="width: 100px;">Precio Compra</th>
                <th class="th-header" style="width: 100px;">Precio Venta</th>
                <th class="th-header" style="width: 90px;">Margen Est.</th>
                <th class="th-header" style="width: 90px;">Stock Total</th>
                <th class="th-header" style="width: 90px;">Stock Mínimo</th>
                <th class="th-header" style="width: 260px;">Distribución por Almacén</th>
                <th class="th-header" style="width: 260px;">Presentaciones Alternativas</th>
                <th class="th-header" style="width: 90px;">Alquilable</th>
                <th class="th-header" style="width: 130px;">Fecha Registro</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $item)
                @php
                    $isService = (int) $item->opcion === 2;
                    $compra = (float) ($item->precio_compra ?? 0);
                    $venta = (float) ($item->precio_venta ?? 0);
                    $margen = ($compra > 0) ? round((($venta - $compra) / $compra) * 100, 1) . '%' : '-';

                    // Compute total stock from relation or attribute
                    $stockTotal = $isService ? '-' : (float) ($item->stockProducts->sum('stock_actual') ?: ($item->stock_actual ?? 0));
                    $stockMinimo = $isService ? '-' : (float) ($item->stockProducts->first()?->stock_minimo ?? 10);

                    // Warehouse breakdown
                    $warehousesBreakdown = $item->stockProducts->map(function ($sp) {
                        $wName = $sp->warehouse?->descripcion ?? 'Almacén';
                        $qty = is_numeric($sp->stock_actual) ? (float) $sp->stock_actual : 0;
                        return "{$wName}: {$qty}";
                    })->implode(' | ');

                    if (empty($warehousesBreakdown) && ! $isService && isset($item->stock_actual)) {
                        $warehousesBreakdown = "Principal: " . (float) $item->stock_actual;
                    }

                    // Presentations breakdown
                    $presentationsBreakdown = $item->presentations->map(function ($p) use ($item) {
                        $und = $item->unit?->codigo ?? 'UND';
                        $qty = (float) $p->cantidad;
                        $prc = number_format((float) $p->precio, 2);
                        return "{$p->descripcion} ({$qty} {$und} - S/ {$prc})";
                    })->implode('; ');
                @endphp
                <tr>
                    <td class="td-cell text-center">{{ $item->id }}</td>
                    <td class="td-cell text-center" style="mso-number-format:'\@';">{{ $item->codigo_interno ?? '-' }}</td>
                    <td class="td-cell text-center" style="mso-number-format:'\@';">{{ $item->codigo_barras ?? '-' }}</td>
                    <td class="td-cell text-center" style="mso-number-format:'\@';">{{ $item->codigo_sunat ?? '-' }}</td>
                    <td class="td-cell text-center">
                        <span class="{{ $isService ? 'badge-serv' : 'badge-prod' }}">
                            {{ $isService ? 'SERVICIO' : 'PRODUCTO' }}
                        </span>
                    </td>
                    <td class="td-cell">{{ $item->descripcion }}</td>
                    <td class="td-cell">{{ $item->category?->descripcion ?? '-' }}</td>
                    <td class="td-cell text-center">{{ $item->unit ? "{$item->unit->codigo} - {$item->unit->descripcion}" : '-' }}</td>
                    <td class="td-cell">{{ $item->igvTypeAffection?->descripcion ?? ($item->idcodigo_igv ? 'Código ' . $item->idcodigo_igv : '10 - GRAVADO') }}</td>
                    <td class="td-cell text-center">{{ ($item->igv !== null) ? $item->igv . '%' : '18%' }}</td>
                    <td class="td-cell text-right" style="mso-number-format:'\#\,\#\#0\.00';">{{ number_format($compra, 2, '.', '') }}</td>
                    <td class="td-cell text-right" style="mso-number-format:'\#\,\#\#0\.00';">{{ number_format($venta, 2, '.', '') }}</td>
                    <td class="td-cell text-center">{{ $margen }}</td>
                    <td class="td-cell text-center">{{ $stockTotal }}</td>
                    <td class="td-cell text-center">{{ $stockMinimo }}</td>
                    <td class="td-cell">{{ $warehousesBreakdown ?: '-' }}</td>
                    <td class="td-cell">{{ $presentationsBreakdown ?: '-' }}</td>
                    <td class="td-cell text-center">{{ $item->rentable ? 'SÍ' : 'NO' }}</td>
                    <td class="td-cell text-center">{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
