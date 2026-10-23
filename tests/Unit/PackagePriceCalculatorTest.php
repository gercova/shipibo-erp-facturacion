<?php

namespace Tests\Unit;

use App\Models\IgvTypeAffection;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackagePriceTier;
use App\Models\Product;
use App\Services\Packages\Exceptions\GuestOutOfRangeException;
use App\Services\Packages\Exceptions\TierOverlapException;
use App\Services\Packages\PackagePriceCalculator;
use PHPUnit\Framework\TestCase;

class PackagePriceCalculatorTest extends TestCase
{
    protected PackagePriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PackagePriceCalculator;
    }

    /**
     * Test successful calculation within defined guest range.
     */
    public function test_calculates_price_for_guests_within_valid_range(): void
    {
        $package = new Package([
            'name' => 'Paquete SIGNATURE',
            'min_guests' => 100,
            'max_guests' => 180,
            'price_mode' => 'tiered',
            'base_price' => 4200.00,
            'active' => true,
        ]);
        $package->id = 1;

        $tier = new PackagePriceTier([
            'package_id' => 1,
            'from_guests' => 100,
            'to_guests' => 180,
            'price' => 4200.00,
            'price_type' => 'total',
        ]);
        $tier->id = 1;

        $package->setRelation('priceTiers', collect([$tier]));
        $package->setRelation('items', collect([]));

        $result = $this->calculator->calculate($package, 150);

        $this->assertEquals(150, $result['guests']);
        $this->assertEquals(4200.00, $result['total']);
        $this->assertEquals(round(4200.00 / 1.18, 2), $result['subtotal']);
        $this->assertEquals(round(4200.00 - round(4200.00 / 1.18, 2), 2), $result['igv']);
    }

    /**
     * Test that guests below minimum throws GuestOutOfRangeException.
     */
    public function test_throws_exception_when_guests_below_minimum(): void
    {
        $package = new Package([
            'name' => 'Paquete SIGNATURE',
            'min_guests' => 100,
            'max_guests' => 180,
            'price_mode' => 'tiered',
            'base_price' => 4200.00,
        ]);
        $package->setRelation('priceTiers', collect([
            new PackagePriceTier(['from_guests' => 100, 'to_guests' => 180, 'price' => 4200.00, 'price_type' => 'total']),
        ]));
        $package->setRelation('items', collect([]));

        $this->expectException(GuestOutOfRangeException::class);
        $this->calculator->calculate($package, 80);
    }

    /**
     * Test that guests above maximum throws GuestOutOfRangeException.
     */
    public function test_throws_exception_when_guests_above_maximum(): void
    {
        $package = new Package([
            'name' => 'Paquete SIGNATURE',
            'min_guests' => 100,
            'max_guests' => 180,
            'price_mode' => 'tiered',
            'base_price' => 4200.00,
        ]);
        $package->setRelation('priceTiers', collect([
            new PackagePriceTier(['from_guests' => 100, 'to_guests' => 180, 'price' => 4200.00, 'price_type' => 'total']),
        ]));
        $package->setRelation('items', collect([]));

        $this->expectException(GuestOutOfRangeException::class);
        $this->calculator->calculate($package, 220);
    }

    /**
     * Test detection of overlapping tiers.
     */
    public function test_throws_exception_when_tiers_overlap(): void
    {
        $package = new Package([
            'name' => 'Paquete Con Tramos Solapados',
            'min_guests' => 50,
            'max_guests' => 200,
            'price_mode' => 'tiered',
            'base_price' => 0.00,
        ]);

        $tier1 = new PackagePriceTier(['from_guests' => 50, 'to_guests' => 120, 'price' => 2000.00, 'price_type' => 'total']);
        $tier2 = new PackagePriceTier(['from_guests' => 110, 'to_guests' => 200, 'price' => 3500.00, 'price_type' => 'total']); // Overlaps 110-120

        $package->setRelation('priceTiers', collect([$tier1, $tier2]));
        $package->setRelation('items', collect([]));

        $this->expectException(TierOverlapException::class);
        $this->calculator->calculate($package, 115);
    }

    /**
     * Test mixed VAT / tax treatments (Gravado 18% vs Exonerado 0%).
     */
    public function test_mixed_vat_tax_treatment_calculation(): void
    {
        $package = new Package([
            'name' => 'Paquete Mixto',
            'min_guests' => 100,
            'max_guests' => 100,
            'price_mode' => 'fixed',
            'base_price' => 3000.00,
        ]);
        $package->id = 10;

        // Item 1: Gravado 18% (e.g. Cocktail Service)
        $prodGravado = new Product([
            'descripcion' => 'SERVICIO BARTENDER',
            'opcion' => 2,
            'igv' => 18,
            'precio_venta' => 2000.00,
        ]);
        $afGravado = new IgvTypeAffection(['codigo' => '10', 'descripcion' => 'Gravado']);
        $prodGravado->setRelation('igvTypeAffection', $afGravado);

        $item1 = new PackageItem([
            'package_id' => 10,
            'quantity' => 1,
            'is_mandatory' => true,
            'unit_price' => 2000.00,
        ]);
        $item1->setRelation('product', $prodGravado);

        // Item 2: Exonerado 0% (e.g. Amazonian raw agriculture input/fruit)
        $prodExonerado = new Product([
            'descripcion' => 'FRUTAS REGIONALES EXONERADAS LEY AMAZONÍA',
            'opcion' => 1,
            'igv' => 0,
            'precio_venta' => 1000.00,
        ]);
        $afExonerado = new IgvTypeAffection(['codigo' => '20', 'descripcion' => 'Exonerado']);
        $prodExonerado->setRelation('igvTypeAffection', $afExonerado);

        $item2 = new PackageItem([
            'package_id' => 10,
            'quantity' => 1,
            'is_mandatory' => true,
            'unit_price' => 1000.00,
        ]);
        $item2->setRelation('product', $prodExonerado);

        $package->setRelation('items', collect([$item1, $item2]));
        $package->setRelation('priceTiers', collect([]));

        $result = $this->calculator->calculate($package, 100);

        // Package total is 3000:
        // Item 1 (weight 2000/3000 = 2/3): allocated total = 2000.00 (Gravado 18%)
        // Base = round(2000 / 1.18, 2) = 1694.92, IGV = 305.08
        // Item 2 (weight 1000/3000 = 1/3): allocated total = 1000.00 (Exonerado 0%)
        // Base = 1000.00, IGV = 0.00
        // Expected Subtotal = 1694.92 + 1000.00 = 2694.92
        // Expected IGV = 305.08
        // Expected Total = 3000.00

        $this->assertEquals(3000.00, $result['total']);
        $this->assertEquals(2694.92, $result['subtotal']);
        $this->assertEquals(305.08, $result['igv']);
        $this->assertCount(2, $result['breakdown']);

        $line1 = $result['breakdown'][0];
        $this->assertEquals(18, $line1['igv_percent']);
        $this->assertEquals('10', $line1['tax_affection']);
        $this->assertEquals(1694.92, $line1['subtotal']);
        $this->assertEquals(305.08, $line1['igv']);
        $this->assertEquals(2000.00, $line1['total']);

        $line2 = $result['breakdown'][1];
        $this->assertEquals(0, $line2['igv_percent']);
        $this->assertEquals('20', $line2['tax_affection']);
        $this->assertEquals(1000.00, $line2['subtotal']);
        $this->assertEquals(0.00, $line2['igv']);
        $this->assertEquals(1000.00, $line2['total']);
    }

    /**
     * Test strict 2-decimal rounding without floating point drift.
     */
    public function test_strict_two_decimal_rounding(): void
    {
        $breakdown = $this->calculator->createLineBreakdown(
            name: 'Servicio con redondeo impar',
            type: 'package_included',
            quantity: 3,
            total: 333.33,
            igvPercent: 18,
            taxAffection: '10',
            isService: true,
            isMandatory: true
        );

        $this->assertEquals(282.48, $breakdown['subtotal']);
        $this->assertEquals(50.85, $breakdown['igv']);
        $this->assertEquals(333.33, $breakdown['total']);
        $this->assertEquals(round($breakdown['subtotal'] + $breakdown['igv'], 2), $breakdown['total']);
    }

    /**
     * Test services with option=2 are correctly identified as services without stock requirements.
     */
    public function test_services_with_option_two_identified_as_services_without_stock(): void
    {
        $serviceProduct = new Product([
            'descripcion' => 'TRANSPORTE Y LOGÍSTICA DE BARRA',
            'opcion' => 2, // Service
            'stock_actual' => null, // Intangible services have no physical stock
            'rentable' => false,
        ]);

        $item = new PackageItem([
            'quantity' => 1,
            'is_mandatory' => true,
        ]);
        $item->setRelation('product', $serviceProduct);

        $this->assertTrue($this->calculator->resolveIsService($item));
        $this->assertEquals(2, $serviceProduct->opcion);
        $this->assertNull($serviceProduct->stock_actual);
    }
}
