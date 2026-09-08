<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantilla de Cat&aacute;logo de Productos y Servicios</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        #table_items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .th-common { background-color: #2c3e50; color: #ffffff; padding: 6px; text-align: center; border: 1px solid #bdc3c7; }
        .th-product-only { background-color: #d35400; color: #ffffff; padding: 6px; text-align: center; border: 1px solid #bdc3c7; }
        .th-optional { background-color: #7f8c8d; color: #ffffff; padding: 6px; text-align: center; border: 1px solid #bdc3c7; }
        .border-solid { border: 1px solid #dee2e6; padding: 5px; }
        .notes { margin-top: 25px; font-size: 12px; padding: 12px; border: 1px solid #bdc3c7; background-color: #f8f9fa; }
        .notes-table { width: 100%; margin-top: 8px; border-collapse: collapse; }
        .notes-table td { padding: 6px; border: 1px solid #dee2e6; text-align: left; }
        .badge-req { color: #e74c3c; font-weight: bold; }
        .badge-prod { color: #d35400; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $excelText = static function ($value) {
            $value = trim((string) $value);

            return $value !== '' ? '="'.$value.'"' : '';
        };
    @endphp

    <table id="table_items">
        <thead>
            <tr>
                <th class="th-common">tipo_item<br><small style="font-size: 9px;">[OBLIGATORIO: PRODUCTO o SERVICIO]</small></th>
                <th class="th-common">descripcion<br><small style="font-size: 9px;">[OBLIGATORIO]</small></th>
                <th class="th-common">categoria<br><small style="font-size: 9px;">[OBLIGATORIO]</small></th>
                <th class="th-common">unidad_codigo<br><small style="font-size: 9px;">[OBLIGATORIO: NIU, ZZ, KGM, etc.]</small></th>
                <th class="th-common">precio_compra<br><small style="font-size: 9px;">[Costo S/ - Defecto 0.00]</small></th>
                <th class="th-common">precio_venta<br><small style="font-size: 9px;">[Tarifa / Venta S/ - OBLIGATORIO]</small></th>
                <th class="th-product-only">almacen_destino<br><small style="font-size: 9px;">[OBLIGATORIO EN PRODUCTO / Vac&iacute;o en Servicio]</small></th>
                <th class="th-product-only">stock_inicial<br><small style="font-size: 9px;">[OBLIGATORIO EN PRODUCTO / Vac&iacute;o en Servicio]</small></th>
                <th class="th-product-only">stock_minimo<br><small style="font-size: 9px;">[Opcional en Producto / Defecto 10]</small></th>
                <th class="th-optional">alquilable<br><small style="font-size: 9px;">[SI / NO - Herramienta Barra]</small></th>
                <th class="th-optional">codigo_interno<br><small style="font-size: 9px;">[Opcional / SKU]</small></th>
                <th class="th-optional">codigo_barras<br><small style="font-size: 9px;">[Opcional]</small></th>
                <th class="th-optional">afectacion_igv<br><small style="font-size: 9px;">[10=Gravado 18%, 20=Exonerado]</small></th>
                <th class="th-optional">product_id<br><small style="font-size: 9px;">[Solo para actualizar existente]</small></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $item)
                @php
                    $isServ = (int) $item->opcion === 2;
                @endphp
                <tr>
                    <td class="border-solid" style="text-align:center;">{{ $isServ ? 'SERVICIO' : 'PRODUCTO' }}</td>
                    <td class="border-solid">{{ $item->descripcion }}</td>
                    <td class="border-solid" style="text-align:center;">{{ $item->categoria }}</td>
                    <td class="border-solid" style="text-align:center;">{{ $item->unidad_codigo }}</td>
                    <td class="border-solid" style="text-align:right;">{{ number_format((float) ($item->precio_compra ?? 0), 2, '.', '') }}</td>
                    <td class="border-solid" style="text-align:right;">{{ number_format((float) ($item->precio_venta ?? 0), 2, '.', '') }}</td>
                    <td class="border-solid" style="text-align:center;">{{ $isServ ? '' : ($item->almacen_descripcion ?? 'ALMACÉN PRINCIPAL') }}</td>
                    <td class="border-solid" style="text-align:center;">{{ $isServ ? '' : (is_numeric($item->stock_almacen ?? $item->stock_actual) ? (float) ($item->stock_almacen ?? $item->stock_actual) : 0) }}</td>
                    <td class="border-solid" style="text-align:center;">{{ $isServ ? '' : (is_numeric($item->stock_minimo_almacen ?? null) ? (float) $item->stock_minimo_almacen : 10) }}</td>
                    <td class="border-solid" style="text-align:center;">{{ (!empty($item->rentable) && (int) $item->rentable === 1) ? 'SI' : 'NO' }}</td>
                    <td class="border-solid" style="text-align:center; mso-number-format:'\@';">{{ $excelText($item->codigo_interno) }}</td>
                    <td class="border-solid" style="text-align:center; mso-number-format:'\@';">{{ $excelText($item->codigo_barras) }}</td>
                    <td class="border-solid" style="text-align:center;">{{ $item->afectacion_igv_codigo ?? '10' }}</td>
                    <td class="border-solid" style="text-align:center;">{{ $item->id }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="notes">
        <h4 style="margin: 0 0 8px 0; color: #2c3e50;">Instrucciones para Carga Masiva (Cat&aacute;logo Mixto):</h4>
        <table class="notes-table">
            <tr>
                <td style="width: 25%;"><strong>1. Distinci&oacute;n de Tipo de &Iacute;tem</strong></td>
                <td>
                    En la columna <strong>tipo_item</strong> indique <strong>PRODUCTO</strong> para bienes con inventario f&iacute;sico o activos/herramientas alquilables con stock (ej. hielo, gaseosas, barras m&oacute;viles, licuadoras), y <strong>SERVICIO</strong> para mano de obra o prestaciones intangibles (ej. labor de bartender, atenci&oacute;n de eventos).
                </td>
            </tr>
            <tr>
                <td><strong class="badge-prod">2. Columnas Naranjas (Solo PRODUCTO)</strong></td>
                <td>
                    Las columnas <strong>almacen_destino</strong> y <strong>stock_inicial</strong> son <span class="badge-req">OBLIGATORIAS</span> &uacute;nicamente cuando <code>tipo_item = PRODUCTO</code>. Si la fila es de tipo <code>SERVICIO</code>, déjelas vac&iacute;as (el sistema no valida stock f&iacute;sico para servicios). Admite cantidades decimales/fraccionarias (ej: 0.5, 1.5).
                </td>
            </tr>
            <tr>
                <td><strong>3. Validaci&oacute;n Fila por Fila</strong></td>
                <td>
                    Si una fila de tipo PRODUCTO carece de almac&eacute;n o stock inicial v&aacute;lido, el sistema registrar&aacute; una observaci&oacute;n detallada para esa fila espec&iacute;fica y continuar&aacute; importando las dem&aacute;s filas sin interrumpir el archivo.
                </td>
            </tr>
            <tr>
                <td><strong>4. Unidades de Medida SUNAT</strong></td>
                <td>
                    Para bienes f&iacute;sicos use com&uacute;nmente <strong>NIU</strong> (Unidad) o <strong>KGM</strong> (Kilogramos). Para servicios use <strong>ZZ</strong> (Servicios). Si la categor&iacute;a indicada no existe, el sistema la crear&aacute; autom&aacute;ticamente.
                </td>
            </tr>
            <tr>
                <td><strong>5. Registro Nuevo vs. Actualizaci&oacute;n</strong></td>
                <td>
                    Para ingresar nuevos productos o servicios, deje la columna <strong>product_id</strong> vac&iacute;a.
                </td>
            </tr>
            <tr>
                <td><strong>6. Herramientas de Barra / Alquiler</strong></td>
                <td>
                    En la columna <strong>alquilable</strong> coloque <strong>SI</strong> para utensilios, herramientas de coctelería y mobiliario (ej. licuadoras, pinzas, shakers, hieleras) que requieran control en el <strong>Checklist de Eventos</strong>.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
