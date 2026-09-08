<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\IgvTypeAffection;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsCatalogImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected array $errors = [];

    public function collection(Collection $rows)
    {
        // En Maatwebsite Excel con WithHeadingRow, la fila 1 es el encabezado.
        // La primera fila de datos corresponde a la fila 2 de la hoja de cálculo.
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            // Extraer descripción y tipo bruto
            $description = trim((string) ($row['descripcion'] ?? $row['nombre'] ?? $row['producto'] ?? $row['item'] ?? ''));
            $rawType = trim((string) ($row['tipo_item'] ?? $row['tipo'] ?? $row['opcion'] ?? ''));
            $rawSalePrice = $row['precio_venta'] ?? $row['precio'] ?? $row['tarifa'] ?? null;

            // Ignorar filas completamente vacías o sin datos comerciales
            if ($description === '' && ($rawSalePrice === null || trim((string) $rawSalePrice) === '')) {
                continue;
            }

            // Ignorar leyendas, notas o instrucciones explicativas
            $descUpper = mb_strtoupper($description);
            $typeUpper = mb_strtoupper($rawType);
            if (Str::startsWith($descUpper, ['NOTAS:', 'NOTA:', 'INSTRUCCIONES:', 'INSTRUCCIONES', 'NO MODIFIQUE', 'LEYENDA:']) ||
                Str::startsWith($typeUpper, ['NOTAS:', 'NOTA:', 'INSTRUCCIONES:', 'INSTRUCCIONES', 'NO MODIFIQUE', 'LEYENDA:', '1.', '2.', '3.', '4.', '5.', '6.', '7.', '8.', '9.']) ||
                Str::contains($descUpper, ['PUEDE MODIFICAR', 'LOS REGISTROS QUE NO TIENEN', 'FILAS MIXTAS'])) {
                continue;
            }

            // Validación: Descripción obligatoria
            if ($description === '') {
                $this->addError($rowNumber, '(Sin descripción)', 'La descripción o nombre del producto/servicio es obligatoria.');
                continue;
            }

            // Determinar tipo de ítem: 1 = Producto físico, 2 = Servicio
            $rawType = $row['tipo_item'] ?? $row['tipo'] ?? $row['opcion'] ?? null;
            $itemType = $this->resolveItemType($rawType);
            $isService = ($itemType === 2);

            // Validación: Precio de venta obligatorio y numérico >= 0
            $rawSalePrice = $row['precio_venta'] ?? $row['precio'] ?? $row['tarifa'] ?? null;
            if ($rawSalePrice === null || trim((string) $rawSalePrice) === '' || ! is_numeric($rawSalePrice) || (float) $rawSalePrice < 0) {
                $this->addError($rowNumber, $description, 'El precio de venta es obligatorio y debe ser un valor numérico mayor o igual a 0.');
                continue;
            }
            $precioVenta = round((float) $rawSalePrice, 2);

            // Precio de compra (costo) opcional, por defecto 0.00
            $rawBuyPrice = $row['precio_compra'] ?? $row['costo'] ?? 0;
            $precioCompra = is_numeric($rawBuyPrice) && (float) $rawBuyPrice >= 0 ? round((float) $rawBuyPrice, 2) : 0.00;

            // Validaciones específicas para PRODUCTOS FÍSICOS (opcion = 1)
            $warehouseId = null;
            $stockActual = null;
            $stockMinimo = null;

            if (! $isService) {
                // Almacén obligatorio para productos físicos
                $rawWarehouse = $row['almacen_destino'] ?? $row['almacen'] ?? $row['idalmacen'] ?? null;
                $warehouseId = $this->resolveWarehouseId($rawWarehouse);
                if ($warehouseId === null) {
                    $this->addError(
                        $rowNumber,
                        $description,
                        'Para productos físicos es obligatorio indicar un almacén de destino válido (ej: "ALMACÉN PRINCIPAL" o ID existente).'
                    );
                    continue;
                }

                // Stock inicial obligatorio para productos físicos
                $rawStock = $row['stock_inicial'] ?? $row['stock_actual'] ?? $row['stock'] ?? null;
                if ($rawStock === null || trim((string) $rawStock) === '' || ! is_numeric($rawStock) || (float) $rawStock < 0) {
                    $this->addError(
                        $rowNumber,
                        $description,
                        'Para productos físicos es obligatorio indicar el stock inicial (número mayor o igual a 0).'
                    );
                    continue;
                }
                $stockActual = round((float) $rawStock, 4);

                // Stock mínimo opcional, por defecto 10
                $rawMinStock = $row['stock_minimo'] ?? $row['minimo'] ?? null;
                $stockMinimo = (is_numeric($rawMinStock) && (float) $rawMinStock >= 0) ? round((float) $rawMinStock, 4) : 10.0;
            }

            // Resolución de categoría, unidad y afectación IGV
            $categoryName = $row['categoria'] ?? $row['rubro'] ?? null;
            $categoryId = $this->resolveCategoryId($categoryName, $itemType);

            $unitCode = $row['unidad_codigo'] ?? $row['unidad'] ?? $row['unidad_de_medida'] ?? null;
            $unitId = $this->resolveUnitId($unitCode, $itemType);

            $igvCode = $row['afectacion_igv_codigo'] ?? $row['afectacion_igv'] ?? null;
            $igvAffectionId = $this->resolveIgvAffectionId($igvCode);
            $igvPercent = $this->resolveIgvPercent($igvAffectionId);

            $codigoInterno = $this->nullableText($row['codigo_interno'] ?? $row['codigo'] ?? $row['sku'] ?? null);
            $codigoBarras  = $this->nullableText($row['codigo_barras'] ?? $row['barcode'] ?? null);
            $codigoSunat   = $this->nullableText($row['codigo_sunat'] ?? null) ?? '00000000';

            // Detección de herramienta/alquilable para eventos
            $rawRentable = $row['alquilable'] ?? $row['rentable'] ?? $row['es_alquilable'] ?? $row['herramienta'] ?? null;
            $isRentable = false;
            if (! $isService) {
                if ($rawRentable !== null && trim((string) $rawRentable) !== '') {
                    $cleanRent = mb_strtoupper(trim((string) $rawRentable));
                    $isRentable = in_array($cleanRent, ['SI', 'SÍ', '1', 'TRUE', 'YES', 'X', 'ALQUILER']);
                } elseif (Str::contains(mb_strtoupper((string) $categoryName), ['HERRAMIENTA', 'ALQUILER', 'MENAJE', 'EQUIPO'])) {
                    $isRentable = true;
                }
            }

            // Guardar producto y stock de manera transaccional por fila
            try {
                DB::transaction(function () use (
                    $row,
                    $description,
                    $codigoInterno,
                    $codigoBarras,
                    $codigoSunat,
                    $unitId,
                    $categoryId,
                    $igvAffectionId,
                    $igvPercent,
                    $precioCompra,
                    $precioVenta,
                    $itemType,
                    $isService,
                    $isRentable,
                    $warehouseId,
                    $stockActual,
                    $stockMinimo
                ) {
                    // Buscar si ya existe por product_id, código interno o descripción exacta
                    $product = null;
                    $productId = (int) ($row['product_id'] ?? $row['id'] ?? 0);

                    if ($productId > 0) {
                        $product = Product::find($productId);
                    }

                    if (! $product && $codigoInterno !== null) {
                        $product = Product::where('codigo_interno', $codigoInterno)->first();
                    }

                    if (! $product) {
                        $product = Product::whereRaw('UPPER(descripcion) = ?', [mb_strtoupper($description)])->first();
                    }

                    $productData = [
                        'codigo_interno' => $codigoInterno,
                        'codigo_barras'  => $codigoBarras,
                        'codigo_sunat'   => $codigoSunat,
                        'descripcion'    => mb_strtoupper($description),
                        'idunidad'       => $unitId,
                        'idcategoria'    => $categoryId,
                        'idcodigo_igv'   => $igvAffectionId,
                        'igv'            => $igvPercent,
                        'precio_compra'  => $precioCompra,
                        'precio_venta'   => $precioVenta,
                        'opcion'         => $itemType,
                        'rentable'       => $isRentable,
                        'stock_actual'   => $isService ? null : $stockActual,
                    ];

                    if ($product) {
                        $product->update($productData);
                    } else {
                        $product = Product::create($productData);
                    }

                    // Gestión de tabla stock_products
                    if (! $isService && $warehouseId !== null) {
                        $stock = StockProduct::firstOrNew([
                            'idproducto' => $product->id,
                            'idalmacen'  => $warehouseId,
                        ]);

                        $stock->precio_compra = $precioCompra;
                        $stock->precio_venta  = $precioVenta;
                        $stock->stock_actual  = $stockActual;
                        $stock->stock_minimo  = $stockMinimo;

                        if (! $stock->exists) {
                            $stock->fecha_registro = now()->toDateString();
                            $stock->stock_entrada  = $stockActual;
                        }

                        $stock->save();
                    } elseif ($isService) {
                        // En servicios, asegurar que si existe registro de stock no tenga valores físicos
                        $defaultWarehouseId = Warehouse::query()->orderBy('id')->value('id');
                        if ($defaultWarehouseId) {
                            $stock = StockProduct::firstOrNew([
                                'idproducto' => $product->id,
                                'idalmacen'  => $defaultWarehouseId,
                            ]);

                            $stock->precio_compra = $precioCompra;
                            $stock->precio_venta  = $precioVenta;
                            $stock->stock_actual  = null;
                            $stock->stock_minimo  = null;
                            $stock->save();
                        }
                    }

                    $this->importedCount++;
                });
            } catch (\Throwable $e) {
                $this->addError($rowNumber, $description, 'Error de base de datos al guardar: ' . $e->getMessage());
            }
        }
    }

    private function addError(int $row, string $description, string $reason): void
    {
        $this->errors[] = [
            'fila'        => $row,
            'descripcion' => $description,
            'motivo'      => $reason,
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSummary(): array
    {
        return [
            'imported_count' => $this->importedCount,
            'error_count'    => count($this->errors),
            'errors'         => $this->errors,
        ];
    }

    private function resolveWarehouseId(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        // Si es número, buscar por ID
        if (is_numeric($raw)) {
            $id = (int) $raw;
            if (Warehouse::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        $clean = mb_strtoupper($raw);
        $warehouse = Warehouse::query()
            ->whereRaw('UPPER(descripcion) = ?', [$clean])
            ->first();

        if ($warehouse) {
            return (int) $warehouse->id;
        }

        // Búsqueda flexible sin tildes
        $normalizedRaw = strtr($clean, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);
        $warehouses = Warehouse::all(['id', 'descripcion']);
        foreach ($warehouses as $w) {
            $wNorm = strtr(mb_strtoupper($w->descripcion), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);
            if ($wNorm === $normalizedRaw || str_contains($wNorm, $normalizedRaw) || str_contains($normalizedRaw, $wNorm)) {
                return (int) $w->id;
            }
        }

        return null;
    }

    private function resolveCategoryId(?string $name, int $fallbackOpcion): int
    {
        $name = trim((string) $name);
        if ($name === '') {
            $fallbackName = $fallbackOpcion === 2 ? 'SERVICIOS' : 'GENERAL';
            $cat = Category::query()->whereRaw('UPPER(descripcion) = ?', [$fallbackName])->first();
            if ($cat) {
                return (int) $cat->id;
            }

            return (int) (Category::query()->orderBy('id')->value('id') ?? 1);
        }

        $clean = mb_strtoupper($name);
        $category = Category::query()->whereRaw('UPPER(descripcion) = ?', [$clean])->first();
        if (! $category) {
            $category = Category::create([
                'descripcion' => $clean,
            ]);
        }

        return (int) $category->id;
    }

    private function resolveUnitId(?string $codeOrName, int $opcion): int
    {
        $raw = trim((string) $codeOrName);
        if ($raw !== '') {
            $clean = mb_strtoupper($raw);
            $unit = Unit::query()
                ->where('estado', 1)
                ->where(function ($q) use ($clean) {
                    $q->whereRaw('UPPER(codigo) = ?', [$clean])
                      ->orWhereRaw('UPPER(descripcion) = ?', [$clean]);
                })
                ->first();

            if ($unit) {
                return (int) $unit->id;
            }
        }

        // Fallback estándar SUNAT: ZZ para servicios (opción 2), NIU para bienes/productos (opción 1)
        $defaultCode = ($opcion === 2) ? 'ZZ' : 'NIU';
        $fallbackUnit = Unit::query()->where('codigo', $defaultCode)->first();
        if ($fallbackUnit) {
            return (int) $fallbackUnit->id;
        }

        return (int) (Unit::query()->where('estado', 1)->orderBy('id')->value('id') ?? 1);
    }

    private function resolveIgvAffectionId(?string $code): int
    {
        $raw = trim((string) $code);
        if ($raw !== '') {
            $affection = IgvTypeAffection::query()
                ->where('estado', 1)
                ->whereRaw('UPPER(codigo) = ?', [mb_strtoupper($raw)])
                ->first();

            if ($affection) {
                return (int) $affection->id;
            }
        }

        $defaultAffection = IgvTypeAffection::query()
            ->where('estado', 1)
            ->where('codigo', '10')
            ->first();

        return $defaultAffection ? (int) $defaultAffection->id : (int) (IgvTypeAffection::query()->value('id') ?? 1);
    }

    private function resolveIgvPercent(int $affectionId): int
    {
        $code = (string) IgvTypeAffection::query()->whereKey($affectionId)->value('codigo');

        return $code === '10' ? 18 : 0;
    }

    private function resolveItemType(mixed $value): int
    {
        $normalized = mb_strtoupper(trim((string) $value));

        return in_array($normalized, ['2', 'SERVICIO', 'SERVICES', 'SERV', 'S'], true) ? 2 : 1;
    }

    private function nullableText($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
