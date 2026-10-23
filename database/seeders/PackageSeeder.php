<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CocktailMenuCategory;
use App\Models\CocktailMenuItem;
use App\Models\IgvTypeAffection;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackagePriceTier;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Resolve auxiliary references
        $unitService = Unit::where('codigo', 'ZZ')->first() ?? Unit::first();
        $unitItem = Unit::where('codigo', 'NIU')->first() ?? Unit::first();
        $catService = Category::where('descripcion', 'LIKE', '%SERVICIO%')->first() ?? Category::first();
        $catEquipment = Category::where('descripcion', 'LIKE', '%ALQUILER%')->first()
            ?? Category::where('descripcion', 'LIKE', '%EQUIPO%')->first()
            ?? Category::first();
        $igvGravado = IgvTypeAffection::where('codigo', '10')->first() ?? IgvTypeAffection::first();

        // 2. Ensure Service Products (opcion = 2) exist for package components
        $srvBartender = Product::firstOrCreate(
            ['codigo_interno' => 'SRV-BARTENDER'],
            [
                'codigo_sunat' => '80141600',
                'descripcion' => 'SERVICIO DE 1 BARTENDER + 1 ASISTENTE (OPEN BAR)',
                'idunidad' => $unitService ? $unitService->id : 1,
                'idcategoria' => $catService ? $catService->id : 1,
                'igv' => 18,
                'idcodigo_igv' => $igvGravado ? $igvGravado->id : 1,
                'precio_compra' => 0.00,
                'precio_venta' => 600.00,
                'opcion' => 2,
                'rentable' => false,
                'stock_actual' => null,
            ]
        );

        $srvBarraMovil = Product::firstOrCreate(
            ['codigo_interno' => 'ALQ-BARRA-MOVIL'],
            [
                'codigo_sunat' => '72154000',
                'descripcion' => 'ALQUILER E INSTALACIÓN DE BARRA MÓVIL SHIPIBO',
                'idunidad' => $unitService ? $unitService->id : 1,
                'idcategoria' => $catEquipment ? $catEquipment->id : 1,
                'igv' => 18,
                'idcodigo_igv' => $igvGravado ? $igvGravado->id : 1,
                'precio_compra' => 1500.00,
                'precio_venta' => 450.00,
                'opcion' => 2,
                'rentable' => true,
                'stock_actual' => null,
            ]
        );

        $srvCristaleria = Product::firstOrCreate(
            ['codigo_interno' => 'ALQ-CRISTALERIA-VAR'],
            [
                'codigo_sunat' => '52151500',
                'descripcion' => 'CRISTALERÍA VARIADA Y HERRAMIENTAS DE COCTELERÍA',
                'idunidad' => $unitService ? $unitService->id : 1,
                'idcategoria' => $catEquipment ? $catEquipment->id : 1,
                'igv' => 18,
                'idcodigo_igv' => $igvGravado ? $igvGravado->id : 1,
                'precio_compra' => 800.00,
                'precio_venta' => 300.00,
                'opcion' => 2,
                'rentable' => true,
                'stock_actual' => null,
            ]
        );

        $insHielo = Product::firstOrCreate(
            ['codigo_interno' => 'INS-HIELO-GOURMET'],
            [
                'codigo_sunat' => '50202300',
                'descripcion' => 'HIELO GOURMET EN CUBOS Y COOLER TÉRMICO',
                'idunidad' => $unitItem ? $unitItem->id : 1,
                'idcategoria' => $catService ? $catService->id : 1,
                'igv' => 18,
                'idcodigo_igv' => $igvGravado ? $igvGravado->id : 1,
                'precio_compra' => 60.00,
                'precio_venta' => 150.00,
                'opcion' => 1,
                'rentable' => false,
                'stock_actual' => 100.00,
            ]
        );

        // 3. Register "Machu Picchu" in Cocktail Menu (under category Pisco)
        $piscoCategory = CocktailMenuCategory::firstOrCreate(
            ['nombre' => 'PISCO'],
            [
                'descripcion' => 'Cócteles a base de Pisco peruano tradicional',
                'icono' => 'ri-goblet-line',
                'orden' => 3,
                'activo' => true,
            ]
        );

        $cocktailMachuPicchu = CocktailMenuItem::firstOrCreate(
            ['nombre' => 'MACHU PICCHU'],
            [
                'menu_category_id' => $piscoCategory->id,
                'descripcion_corta' => 'Cóctel emblemático tricolor a base de Pisco, jarabe de granadina, jugo de naranja y licor de menta.',
                'cristaleria' => 'Vaso Pera / Huracán',
                'garnish' => 'Cereza y rodaja de naranja',
                'precio' => 25.00,
                'es_autor' => false,
                'destacado' => true,
                'activo' => true,
                'orden' => 12,
            ]
        );

        // 4. Sample Package 1: Paquete SIGNATURE (500 vasos, 100 - 180 invitados, S/ 4,200)
        $pkgSignature = Package::firstOrCreate(
            ['name' => 'Paquete SIGNATURE'],
            [
                'description' => '500 vasos. Cócteles que elevan tu evento a otro nivel. Incluye 1 Bartender + 1 asistente, Barra móvil, Cristalería variada, Hielo Gourmet, Insumos de primera calidad, Utensilios de bar y selección de 5 a 6 opciones de cocteles.',
                'min_guests' => 100,
                'max_guests' => 180,
                'price_mode' => 'tiered',
                'base_price' => 4200.00,
                'active' => true,
            ]
        );

        PackagePriceTier::firstOrCreate(
            [
                'package_id' => $pkgSignature->id,
                'from_guests' => 100,
                'to_guests' => 180,
            ],
            [
                'price' => 4200.00,
                'price_type' => 'total',
            ]
        );

        // Attach Items to Signature Package
        $this->attachDefaultPackageItems($pkgSignature, [
            ['product_id' => $srvBartender->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => '1 Bartender + 1 asistente de bar'],
            ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => 'Barra móvil profesional'],
            ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => 'Cristalería variada y herramientas'],
            ['product_id' => $insHielo->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => 'Hielo Gourmet y logística fría'],
            ['cocktail_id' => $cocktailMachuPicchu->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => false, 'notes' => 'Opción de carta: Machu Picchu'],
        ]);

        // 5. Sample Package 2: Paquete LUXURY (600 vasos, 100 - 250 invitados, S/ 4,800)
        $pkgLuxury = Package::firstOrCreate(
            ['name' => 'Paquete LUXURY'],
            [
                'description' => '600 vasos. La máxima experiencia en coctelería para eventos inolvidables. Incluye 1 Bartender + 1 asistente, Barra móvil de lujo, Cristalería variada, Hielo Gourmet, Insumos premium, Utensilios de bar y selección de 5 a 6 opciones de cócteles.',
                'min_guests' => 100,
                'max_guests' => 250,
                'price_mode' => 'tiered',
                'base_price' => 4800.00,
                'active' => true,
            ]
        );

        PackagePriceTier::firstOrCreate(
            [
                'package_id' => $pkgLuxury->id,
                'from_guests' => 100,
                'to_guests' => 180,
            ],
            [
                'price' => 4800.00,
                'price_type' => 'total',
            ]
        );

        PackagePriceTier::firstOrCreate(
            [
                'package_id' => $pkgLuxury->id,
                'from_guests' => 181,
                'to_guests' => 250,
            ],
            [
                'price' => 5400.00,
                'price_type' => 'total',
            ]
        );

        // Attach Items to Luxury Package
        $this->attachDefaultPackageItems($pkgLuxury, [
            ['product_id' => $srvBartender->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => '1 Bartender + 1 asistente de bar'],
            ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => 'Barra móvil de lujo'],
            ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => 'Cristalería fina y cristales especializados'],
            ['product_id' => $insHielo->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => true, 'notes' => 'Hielo Gourmet y logística fría'],
            ['cocktail_id' => $cocktailMachuPicchu->id, 'quantity' => 1, 'is_per_guest' => false, 'is_mandatory' => false, 'notes' => 'Opción de carta: Machu Picchu'],
        ]);
    }

    /**
     * Helper to safely attach items to package.
     */
    protected function attachDefaultPackageItems(Package $package, array $items): void
    {
        foreach ($items as $itemData) {
            PackageItem::firstOrCreate(
                [
                    'package_id' => $package->id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'cocktail_id' => $itemData['cocktail_id'] ?? null,
                ],
                [
                    'quantity' => $itemData['quantity'] ?? 1.00,
                    'is_per_guest' => $itemData['is_per_guest'] ?? false,
                    'is_mandatory' => $itemData['is_mandatory'] ?? true,
                    'notes' => $itemData['notes'] ?? null,
                ]
            );
        }
    }
}
