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

        // 2. Ensure Service Products exist
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
                'descripcion' => 'ALQUILER E INSTALACIÓN DE BARRA MÓVIL PROFESIONAL',
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

        $srvApoyo = Product::firstOrCreate(
            ['codigo_interno' => 'SRV-PERSONAL-APOYO'],
            [
                'codigo_sunat' => '80141600',
                'descripcion' => 'PERSONAL ADICIONAL DE APOYO PARA EVENTOS',
                'idunidad' => $unitService ? $unitService->id : 1,
                'idcategoria' => $catService ? $catService->id : 1,
                'igv' => 18,
                'idcodigo_igv' => $igvGravado ? $igvGravado->id : 1,
                'precio_compra' => 0.00,
                'precio_venta' => 200.00,
                'opcion' => 2,
                'rentable' => false,
                'stock_actual' => null,
            ]
        );

        // 3. Register Cocktail Menu Categories and Cocktails from Flyer
        $cocktailCategoriesData = [
            'RON' => [
                'icono' => 'ri-goblet-line',
                'orden' => 1,
                'descripcion' => 'Cócteles a base de Ron caribeño e infusiones tropicales',
                'cocktails' => [
                    'Cuba Libre', 'Piña Colada', 'Daiquiri de Durazno', 'Daiquiri de Maracuyá',
                    'Mojito Clásico', 'Mojito de Maracuyá', 'Mojito de Fresa', 'Hawaian blue',
                    'Huracán', 'Tropical green', 'Tropical punch', 'Flor punch', 'Malibu Sunset', 'Jungle bird',
                ],
            ],
            'PISCO' => [
                'icono' => 'ri-goblet-fill',
                'orden' => 2,
                'descripcion' => 'Cócteles a base de auténtico Pisco peruano tradicional',
                'cocktails' => [
                    'Tinto de verano', 'Chilcano Clásico', 'Chilcano de Maracuyá', 'Chilcano de Camu camu',
                    'Chilcano de Uvachado', 'Chilcano de Marabuena', 'Chilcano de Maracumango',
                    'Chilcano de fresa', 'Pisco punch', 'Primavera', 'Chicherito', 'Perú Libre', 'Machu Picchu',
                ],
            ],
            'VODKA' => [
                'icono' => 'ri-cup-line',
                'orden' => 3,
                'descripcion' => 'Cócteles refrescantes a base de Vodka premium',
                'cocktails' => [
                    'Destornillador', 'Vodka Sunrise', 'Caipiroshka', 'Pantera rosa',
                    'Vodka Collins', 'Vivaracho',
                ],
            ],
            'GIN' => [
                'icono' => 'ri-drinks-line',
                'orden' => 4,
                'descripcion' => 'Gin tonics botánicos y combinaciones herbales',
                'cocktails' => [
                    'Gin & tonic cidra regional', 'Gin & tonic frutos rojos', 'Gin & tonic herbal',
                    'Gin & tonic con especias', 'Gin & tonic de piña', 'Gin & tonic Selva (piña con jengibre)',
                    'Gin Basil Smash',
                ],
            ],
            'TEQUILA' => [
                'icono' => 'ri-contrast-drop-line',
                'orden' => 5,
                'descripcion' => 'Margaritas y cócteles vibrantes a base de Tequila',
                'cocktails' => [
                    'Margarita Clásica', 'Margarita de Maracuyá', 'Margarita de Fresa',
                    'Margarita de Mango', 'Margarita de Piña', 'Margarita de Sandia y Albahaca',
                    'Margarita Blue', 'Margarita Picante', 'Paloma', 'Tequila Sunrise', 'Tequila Sour',
                ],
            ],
        ];

        foreach ($cocktailCategoriesData as $catName => $catInfo) {
            $category = CocktailMenuCategory::updateOrCreate(
                ['nombre' => $catName],
                [
                    'descripcion' => $catInfo['descripcion'],
                    'icono' => $catInfo['icono'],
                    'orden' => $catInfo['orden'],
                    'activo' => true,
                ]
            );

            foreach ($catInfo['cocktails'] as $order => $cocktailName) {
                CocktailMenuItem::updateOrCreate(
                    [
                        'menu_category_id' => $category->id,
                        'nombre' => $cocktailName,
                    ],
                    [
                        'descripcion_corta' => 'Cóctel de alta calidad de la carta Shipibo.',
                        'cristaleria' => 'Cristalería especializada',
                        'garnish' => 'Garnish fresco y cítricos',
                        'precio' => 25.00,
                        'es_autor' => false,
                        'destacado' => ($order < 3),
                        'orden' => $order + 1,
                        'activo' => true,
                    ]
                );
            }
        }

        // 4. Seed the 7 Packages from the Flyer + Barman Service
        $flyerPackages = [
            [
                'name' => 'Paquete ESENCIAL',
                'glasses' => '100 vasos',
                'description' => '100 vasos. Ideal para reuniones íntimas y celebraciones pequeñas. Incluye 1 Bartender + 1 asistente, Barra móvil, Cristalería, Hielo, Insumos, Utensilios de bar y 4 opciones de cócteles.',
                'image' => 'img/packages/pkg-01.png',
                'min_guests' => 20,
                'max_guests' => 50,
                'base_price' => 1200.00,
                'tiers' => [
                    ['from' => 20, 'to' => 50, 'price' => 1200.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil'],
                    ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'notes' => 'Cristalería'],
                    ['product_id' => $insHielo->id, 'quantity' => 1, 'notes' => 'Hielo e insumos'],
                ],
            ],
            [
                'name' => 'Paquete TROPICAL',
                'glasses' => '200 vasos',
                'description' => '200 vasos. Sabores frescos que transportan a los mejores destinos. Incluye 1 Bartender + 1 asistente, Barra móvil, Cristalería, Hielo, Insumos, Utensilios de bar y 5 opciones de cócteles.',
                'image' => 'img/packages/pkg-02.png',
                'min_guests' => 50,
                'max_guests' => 80,
                'base_price' => 2000.00,
                'tiers' => [
                    ['from' => 50, 'to' => 80, 'price' => 2000.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil'],
                    ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'notes' => 'Cristalería'],
                    ['product_id' => $insHielo->id, 'quantity' => 1, 'notes' => 'Hielo e insumos'],
                ],
            ],
            [
                'name' => 'Paquete PREMIUM',
                'glasses' => '300 vasos',
                'description' => '300 vasos. Una experiencia sofisticada con cocteles de alta calidad. Incluye 1 Bartender + 1 asistente, Barra móvil, Cristalería variada, Hielo Gourmet, Insumos, Utensilios de bar y 5 opciones de cócteles.',
                'image' => 'img/packages/pkg-03.png',
                'min_guests' => 50,
                'max_guests' => 120,
                'base_price' => 2900.00,
                'tiers' => [
                    ['from' => 50, 'to' => 120, 'price' => 2900.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil'],
                    ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'notes' => 'Cristalería variada'],
                    ['product_id' => $insHielo->id, 'quantity' => 1, 'notes' => 'Hielo Gourmet e insumos'],
                ],
            ],
            [
                'name' => 'Paquete GOLD',
                'glasses' => '400 vasos',
                'description' => '400 vasos. Cocteles selectos para eventos que merecen algo más. Incluye 1 Bartender + 1 asistente, Barra móvil, Cristalería variada, Hielo Gourmet, Insumos, Utensilios de bar y 5 opciones de cócteles.',
                'image' => 'img/packages/pkg-04.png',
                'min_guests' => 80,
                'max_guests' => 150,
                'base_price' => 3600.00,
                'tiers' => [
                    ['from' => 80, 'to' => 150, 'price' => 3600.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil'],
                    ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'notes' => 'Cristalería variada'],
                    ['product_id' => $insHielo->id, 'quantity' => 1, 'notes' => 'Hielo Gourmet e insumos'],
                ],
            ],
            [
                'name' => 'Paquete SIGNATURE',
                'glasses' => '500 vasos',
                'description' => '500 vasos. Cocteles que elevan tu evento a otro nivel. Incluye 1 Bartender + 1 asistente, Barra móvil, Cristalería variada, Hielo Gourmet, Insumos de primera calidad, Utensilios de bar y selección de 5 a 6 opciones.',
                'image' => 'img/packages/pkg-05.png',
                'min_guests' => 100,
                'max_guests' => 180,
                'base_price' => 4200.00,
                'tiers' => [
                    ['from' => 100, 'to' => 180, 'price' => 4200.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil'],
                    ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'notes' => 'Cristalería variada'],
                    ['product_id' => $insHielo->id, 'quantity' => 1, 'notes' => 'Hielo Gourmet e insumos'],
                ],
            ],
            [
                'name' => 'Paquete LUXURY',
                'glasses' => '600 vasos',
                'description' => '600 vasos. La máxima experiencia en coctelería para eventos inolvidables. Incluye 1 Bartender + 1 asistente, Barra móvil de lujo, Cristalería variada, Hielo Gourmet, Insumos premium, Utensilios de bar y selección de 5 a 6 opciones.',
                'image' => 'img/packages/pkg-06.png',
                'min_guests' => 100,
                'max_guests' => 250,
                'base_price' => 4800.00,
                'tiers' => [
                    ['from' => 100, 'to' => 250, 'price' => 4800.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil de lujo'],
                    ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'notes' => 'Cristalería variada'],
                    ['product_id' => $insHielo->id, 'quantity' => 1, 'notes' => 'Hielo Gourmet e insumos'],
                ],
            ],
            [
                'name' => 'Paquete EXPERIENCE',
                'glasses' => '700 vasos',
                'description' => '700 vasos. Más que un open bar, una experiencia completa. Incluye 1 Bartender + 1 asistente, Barra móvil de lujo, Cristalería variada, Hielo Gourmet, Insumos de autor, Utensilios de bar, 5 a 6 opciones y personal adicional de apoyo.',
                'image' => 'img/packages/pkg-07.png',
                'min_guests' => 200,
                'max_guests' => 350,
                'base_price' => 5300.00,
                'tiers' => [
                    ['from' => 200, 'to' => 350, 'price' => 5300.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil de lujo'],
                    ['product_id' => $srvCristaleria->id, 'quantity' => 1, 'notes' => 'Cristalería variada'],
                    ['product_id' => $insHielo->id, 'quantity' => 1, 'notes' => 'Hielo Gourmet e insumos'],
                    ['product_id' => $srvApoyo->id, 'quantity' => 1, 'notes' => 'Personal adicional de apoyo'],
                ],
            ],
            [
                'name' => 'Servicio de Barman',
                'glasses' => 'Solo Servicio',
                'description' => 'Tu pones los insumos y nosotros nos encargamos de lo demás. Incluye 1 Bartender + 1 asistente de bar, Barra móvil y herramientas de bar. 5 horas de servicio. Capacidad de atención hasta 100 personas. (No cristalería, no insumos).',
                'image' => 'img/packages/pkg-01.png',
                'min_guests' => 10,
                'max_guests' => 100,
                'base_price' => 600.00,
                'price_mode' => 'fixed',
                'tiers' => [
                    ['from' => 10, 'to' => 100, 'price' => 600.00],
                ],
                'items' => [
                    ['product_id' => $srvBartender->id, 'quantity' => 1, 'notes' => '1 Bartender + 1 asistente de bar'],
                    ['product_id' => $srvBarraMovil->id, 'quantity' => 1, 'notes' => 'Barra móvil y herramientas'],
                ],
            ],
        ];

        // Deactivate old demo packages so only the 8 real packages appear in public view
        Package::where('name', 'LIKE', '%(Demo)%')->update(['active' => false]);

        foreach ($flyerPackages as $pkgData) {
            $pkg = Package::updateOrCreate(
                ['name' => $pkgData['name']],
                [
                    'description' => $pkgData['description'],
                    'image' => $pkgData['image'],
                    'min_guests' => $pkgData['min_guests'],
                    'max_guests' => $pkgData['max_guests'],
                    'price_mode' => $pkgData['price_mode'] ?? 'tiered',
                    'base_price' => $pkgData['base_price'],
                    'active' => true,
                ]
            );

            // Price tiers
            foreach ($pkgData['tiers'] as $tierData) {
                PackagePriceTier::updateOrCreate(
                    [
                        'package_id' => $pkg->id,
                        'from_guests' => $tierData['from'],
                        'to_guests' => $tierData['to'],
                    ],
                    [
                        'price' => $tierData['price'],
                        'price_type' => 'total',
                    ]
                );
            }

            // Package Items
            $this->attachDefaultPackageItems($pkg, $pkgData['items']);
        }
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
