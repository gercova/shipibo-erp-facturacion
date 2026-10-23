<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CatalogAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:audit 
                            {--csv= : Optional custom path to save the CSV report}
                            {--summary-only : Display only the summary metrics in console}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit the imported products and services catalog for tax compliance, stock integrity, and consistency.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('================================================================');
        $this->info('         SHIPIBO ERP - AUDITORÍA INTEGRAL DE CATÁLOGO           ');
        $this->info('================================================================');
        $this->newLine();

        $allProducts = Product::with(['unit', 'category', 'igvTypeAffection', 'stockProducts'])->get();

        if ($allProducts->isEmpty()) {
            $this->warn('No se encontraron productos o servicios registrados en la base de datos.');

            return self::SUCCESS;
        }

        $totalCatalog = $allProducts->count();
        $this->line("Total de registros evaluados: <fg=cyan>{$totalCatalog}</>");
        $this->newLine();

        $issues = [];

        // 1. Missing SUNAT Code
        $missingSunat = $allProducts->filter(function (Product $p) {
            return blank($p->codigo_sunat);
        });

        foreach ($missingSunat as $p) {
            $issues[] = [
                'id' => $p->id,
                'codigo_interno' => $p->codigo_interno ?: '-',
                'codigo_barras' => $p->codigo_barras ?: '-',
                'descripcion' => $p->descripcion,
                'tipo' => (int) $p->opcion === 2 ? 'Servicio' : 'Producto',
                'categoria' => optional($p->category)->descripcion ?: 'Sin Categoría',
                'tipo_problema' => 'Falta Código SUNAT',
                'gravedad' => 'Alta',
                'recomendacion' => 'Asignar código SUNAT oficial de 8 dígitos según catálogo tributario.',
            ];
        }

        // 2. Missing Unit of Measurement
        $missingUnit = $allProducts->filter(function (Product $p) {
            return blank($p->idunidad) || ! $p->unit;
        });

        foreach ($missingUnit as $p) {
            $issues[] = [
                'id' => $p->id,
                'codigo_interno' => $p->codigo_interno ?: '-',
                'codigo_barras' => $p->codigo_barras ?: '-',
                'descripcion' => $p->descripcion,
                'tipo' => (int) $p->opcion === 2 ? 'Servicio' : 'Producto',
                'categoria' => optional($p->category)->descripcion ?: 'Sin Categoría',
                'tipo_problema' => 'Falta Unidad de Medida',
                'gravedad' => 'Alta',
                'recomendacion' => 'Vincular unidad oficial SUNAT (NIU para bienes, ZZ para servicios).',
            ];
        }

        // 3. Missing IGV Tax Treatment
        $missingIgv = $allProducts->filter(function (Product $p) {
            return blank($p->idcodigo_igv) || ! $p->igvTypeAffection || $p->igv === null;
        });

        foreach ($missingIgv as $p) {
            $issues[] = [
                'id' => $p->id,
                'codigo_interno' => $p->codigo_interno ?: '-',
                'codigo_barras' => $p->codigo_barras ?: '-',
                'descripcion' => $p->descripcion,
                'tipo' => (int) $p->opcion === 2 ? 'Servicio' : 'Producto',
                'categoria' => optional($p->category)->descripcion ?: 'Sin Categoría',
                'tipo_problema' => 'Falta Afectación IGV',
                'gravedad' => 'Alta',
                'recomendacion' => 'Definir tipo de afectación Catálogo 07 (10: Gravado, 20: Exonerado, 30: Inafecto).',
            ];
        }

        // 4. Services with stock
        $servicesWithStock = $allProducts->filter(function (Product $p) {
            if ((int) $p->opcion !== 2) {
                return false;
            }
            $stockActual = (float) $p->stock_actual;
            $stockInWarehouses = $p->stockProducts->sum('stock_actual');

            return $stockActual > 0 || $stockInWarehouses > 0;
        });

        foreach ($servicesWithStock as $p) {
            $stockInWarehouses = $p->stockProducts->sum('stock_actual');
            $issues[] = [
                'id' => $p->id,
                'codigo_interno' => $p->codigo_interno ?: '-',
                'codigo_barras' => $p->codigo_barras ?: '-',
                'descripcion' => $p->descripcion,
                'tipo' => 'Servicio',
                'categoria' => optional($p->category)->descripcion ?: 'Sin Categoría',
                'tipo_problema' => 'Servicio con Stock Registrado',
                'gravedad' => 'Crítica',
                'recomendacion' => "Los servicios intangibles no deben acumular existencias (Stock actual: {$p->stock_actual}, en almacenes: {$stockInWarehouses}). Resetear a 0/null.",
            ];
        }

        // 5. Profitable/Rentable items lacking replacement cost
        $rentableNoCost = $allProducts->filter(function (Product $p) {
            return (bool) $p->rentable && ((float) $p->precio_compra <= 0.00);
        });

        foreach ($rentableNoCost as $p) {
            $issues[] = [
                'id' => $p->id,
                'codigo_interno' => $p->codigo_interno ?: '-',
                'codigo_barras' => $p->codigo_barras ?: '-',
                'descripcion' => $p->descripcion,
                'tipo' => (int) $p->opcion === 2 ? 'Servicio/Alquiler' : 'Producto Físico',
                'categoria' => optional($p->category)->descripcion ?: 'Sin Categoría',
                'tipo_problema' => 'Activo Alquilable sin Costo de Reposición',
                'gravedad' => 'Media',
                'recomendacion' => 'Registrar precio_compra > 0 para cálculo de penalidades por rotura en checklists de eventos.',
            ];
        }

        // 6. Duplicate barcodes
        $barcodes = $allProducts->filter(fn (Product $p) => filled($p->codigo_barras))->groupBy('codigo_barras');
        $duplicateBarcodes = $barcodes->filter(fn ($group) => $group->count() > 1);

        foreach ($duplicateBarcodes as $barcode => $group) {
            foreach ($group as $p) {
                $issues[] = [
                    'id' => $p->id,
                    'codigo_interno' => $p->codigo_interno ?: '-',
                    'codigo_barras' => $barcode,
                    'descripcion' => $p->descripcion,
                    'tipo' => (int) $p->opcion === 2 ? 'Servicio' : 'Producto',
                    'categoria' => optional($p->category)->descripcion ?: 'Sin Categoría',
                    'tipo_problema' => 'Código de Barras Duplicado',
                    'gravedad' => 'Alta',
                    'recomendacion' => "El código de barras {$barcode} se repite en {$group->count()} registros. Asignar código único.",
                ];
            }
        }

        // 7. Suspected services classified as option=1 (physical products)
        $serviceKeywords = ['transporte', 'flete', 'mozo', 'barman', 'bartender', 'alquiler', 'servicio', 'asistente', 'degustacion'];
        $suspectedServices = $allProducts->filter(function (Product $p) use ($serviceKeywords) {
            if ((int) $p->opcion !== 1) {
                return false;
            }
            $desc = mb_strtolower((string) $p->descripcion, 'UTF-8');
            $cat = mb_strtolower((string) optional($p->category)->descripcion, 'UTF-8');
            foreach ($serviceKeywords as $keyword) {
                if (str_contains($desc, $keyword) || str_contains($cat, $keyword)) {
                    return true;
                }
            }

            return false;
        });

        foreach ($suspectedServices as $p) {
            $issues[] = [
                'id' => $p->id,
                'codigo_interno' => $p->codigo_interno ?: '-',
                'codigo_barras' => $p->codigo_barras ?: '-',
                'descripcion' => $p->descripcion,
                'tipo' => 'Producto (Erróneo)',
                'categoria' => optional($p->category)->descripcion ?: 'Sin Categoría',
                'tipo_problema' => 'Servicio/Alquiler clasificado como Producto (opcion=1)',
                'gravedad' => 'Media',
                'recomendacion' => 'Cambiar a opcion=2 (Servicio intangible) para evitar validaciones y descuentos erróneos de stock.',
            ];
        }

        // Summary Statistics
        $summary = [
            ['Falta Código SUNAT', $missingSunat->count(), 'Alta'],
            ['Falta Unidad de Medida', $missingUnit->count(), 'Alta'],
            ['Falta Afectación IGV', $missingIgv->count(), 'Alta'],
            ['Servicios con Stock Físico', $servicesWithStock->count(), 'Crítica'],
            ['Ítems Alquilables sin Costo Reposición', $rentableNoCost->count(), 'Media'],
            ['Códigos de Barras Duplicados', $duplicateBarcodes->flatten()->count(), 'Alta'],
            ['Servicios Mal Clasificados (opcion=1)', $suspectedServices->count(), 'Media'],
        ];

        $this->table(['Categoría de Hallazgo', 'Cantidad de Ítems', 'Nivel de Severidad'], $summary);
        $this->newLine();

        $totalIssues = count($issues);
        if ($totalIssues === 0) {
            $this->info('✔ ¡Felicidades! No se detectaron inconsistencias en el catálogo.');
        } else {
            $this->warn("⚠ Se detectaron {$totalIssues} observaciones que requieren atención.");

            if (! $this->option('summary-only')) {
                $tableRows = array_map(function ($issue) {
                    return [
                        $issue['id'],
                        $issue['codigo_barras'],
                        \Illuminate\Support\Str::limit($issue['descripcion'], 35),
                        $issue['tipo'],
                        $issue['tipo_problema'],
                        $issue['gravedad'],
                    ];
                }, array_slice($issues, 0, 50));

                $this->table(['ID', 'Código Barras', 'Descripción', 'Tipo', 'Problema', 'Gravedad'], $tableRows);

                if ($totalIssues > 50) {
                    $this->comment('... y '.($totalIssues - 50).' observaciones más en el reporte CSV.');
                }
            }
        }

        // Generate CSV file
        $csvPath = $this->generateCsvReport($issues);
        $this->newLine();
        $this->info('✔ Reporte CSV generado exitosamente en:');
        $this->line("  <fg=green>{$csvPath}</>");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Generate CSV report file.
     */
    protected function generateCsvReport(array $issues): string
    {
        $customPath = $this->option('csv');
        if ($customPath) {
            $filePath = $customPath;
            $dir = dirname($filePath);
        } else {
            $dir = storage_path('app/reports');
            $filename = 'catalog_audit_'.date('Ymd_His').'.csv';
            $filePath = $dir.DIRECTORY_SEPARATOR.$filename;
        }

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $handle = fopen($filePath, 'w');

        // Add UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Headers
        fputcsv($handle, [
            'ID',
            'Codigo Interno',
            'Codigo Barras',
            'Descripcion',
            'Tipo Item',
            'Categoria',
            'Problema Detectado',
            'Nivel Gravedad',
            'Accion Recomendada',
        ]);

        foreach ($issues as $issue) {
            fputcsv($handle, [
                $issue['id'],
                $issue['codigo_interno'],
                $issue['codigo_barras'],
                $issue['descripcion'],
                $issue['tipo'],
                $issue['categoria'],
                $issue['tipo_problema'],
                $issue['gravedad'],
                $issue['recomendacion'],
            ]);
        }

        fclose($handle);

        return $filePath;
    }
}
