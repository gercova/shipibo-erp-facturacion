<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\EventChecklist;
use App\Models\EventChecklistItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Events\EventChecklistReturnService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class EventChecklistReturnTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected Warehouse $warehouse;

    protected Contract $contract;

    protected EventChecklistReturnService $returnService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first() ?? User::factory()->create();
        if (! $this->adminUser->hasRole(['SUPERADMIN', 'ADMIN'])) {
            $this->adminUser->assignRole('ADMIN');
        }
        $this->returnService = app(EventChecklistReturnService::class);

        $this->warehouse = Warehouse::first() ?? Warehouse::create([
            'descripcion' => 'Almacén Principal Test',
            'direccion' => 'Jr. San Martín 123',
        ]);

        $client = Client::first() ?? Client::create([
            'iddoc' => 1,
            'nro_documento' => '12345678',
            'nombres' => 'CLIENTE TEST EVENTO',
            'telefono' => '999888777',
            'direccion' => 'Tarapoto',
            'estado' => 1,
        ]);

        $this->contract = Contract::create([
            'contract_number' => 'CON-'.date('Y').'-9999',
            'tracking_code' => 'TRK-TEST-9999',
            'idcliente' => $client->id,
            'provider_name' => 'MYTEMS E.I.R.L.',
            'provider_document' => '20600000000',
            'provider_representative' => 'Gerente General',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'fecha_evento' => now()->addDays(15)->toDateString(),
            'subtotal' => 2000.00,
            'igv' => 360.00,
            'total' => 2360.00,
            'status' => Contract::STATUS_STR_VIGENTE,
            'estado' => Contract::STATUS_ACTIVE,
        ]);
    }

    /**
     * Helper to create a checklist.
     */
    protected function createChecklist(): EventChecklist
    {
        return EventChecklist::create([
            'contract_id' => $this->contract->id,
            'code' => 'CHK-TEST-'.uniqid(),
            'codigo' => 'CHK-TEST-'.uniqid(),
            'titulo' => 'Checklist Retorno Evento Test',
            'fecha_evento' => now()->toDateString(),
            'hora_evento' => '19:00',
            'lugar_evento' => 'Salón Los Pinos',
            'estado' => EventChecklist::STATUS_DISPATCHED,
            'total_items' => 0,
            'items_llevados' => 0,
            'items_devueltos' => 0,
            'items_con_incidencia' => 0,
            'idusuario' => $this->adminUser->id,
            'idalmacen' => $this->warehouse->id,
        ]);
    }

    /**
     * Helper to create a product with stock.
     */
    protected function createProductWithStock(array $attributes = [], float $initialStock = 50.0): array
    {
        $product = Product::create(array_merge([
            'codigo_interno' => 'PRD-'.uniqid(),
            'codigo_barras' => 'BAR-'.uniqid(),
            'descripcion' => 'Producto Test '.uniqid(),
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 1,
            'idcodigo_igv' => 1,
            'precio_compra' => 10.00,
            'precio_venta' => 20.00,
            'replacement_cost' => 15.00,
            'opcion' => 1,
            'rentable' => true,
            'stock_actual' => $initialStock,
        ], $attributes));

        $stockProduct = StockProduct::create([
            'idproducto' => $product->id,
            'idalmacen' => $this->warehouse->id,
            'stock_minimo' => 5,
            'stock_actual' => $initialStock,
            'precio_compra' => $product->precio_compra,
            'precio_venta' => $product->precio_venta,
            'fecha_registro' => now()->toDateString(),
            'stock_entrada' => $initialStock,
        ]);

        return [$product, $stockProduct];
    }

    /**
     * Test 1: A broken glass reduces the count by 1 and triggers an adjustment.
     */
    public function test_broken_glass_reduces_stock_by_one_and_triggers_contract_adjustment(): void
    {
        [$glassProduct, $stockProduct] = $this->createProductWithStock([
            'descripcion' => 'Copa Cóctel Martini',
            'rentable' => true,
            'opcion' => 1,
            'replacement_cost' => 12.50,
        ], 50.0);

        $checklist = $this->createChecklist();

        $checklistItem = EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'idproducto' => $glassProduct->id,
            'producto_id' => $glassProduct->id,
            'descripcion' => 'Copa Cóctel Martini',
            'categoria' => 'Cristalería',
            'cantidad' => 20,
            'cantidad_planeada' => 20,
            'qty_enviada' => 20,
            'qty_ok' => 19,
            'qty_danada' => 0,
            'qty_quebrada' => 1,
            'qty_faltante' => 0,
            'qty_consumida' => 0,
            'costo_penalidad_estimado' => 12.50,
        ]);

        $this->assertEquals(20.0, $checklistItem->sumReturnQuantities());
        $this->assertTrue($checklistItem->isReturnBalanced());

        // Close return
        $result = $this->returnService->closeReturn($checklist, $this->adminUser);

        $this->assertTrue($result['success']);
        $this->assertNotNull($checklist->fresh()->retorno_cerrado_at);
        $this->assertNotNull($checklist->fresh()->stock_aplicado_at);

        // Assert stock reduced by 1 (50 -> 49)
        $stockProduct->refresh();
        $this->assertEquals(49.0, (float) $stockProduct->stock_actual);

        $glassProduct->refresh();
        $this->assertEquals(49.0, (float) $glassProduct->stock_actual);

        // Assert InventoryMovement recorded
        $movement = InventoryMovement::where('checklist_id', $checklist->id)
            ->where('product_id', $glassProduct->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovement::TYPE_RETORNO_EVENTO, $movement->type);
        $this->assertEquals(1.0, (float) $movement->quantity);

        // Assert ContractAdjustment created for broken glass
        $adjustment = ContractAdjustment::where('contract_id', $this->contract->id)
            ->where('checklist_item_id', $checklistItem->id)
            ->first();

        $this->assertNotNull($adjustment);
        $this->assertEquals(ContractAdjustment::TYPE_BREAKAGE, $adjustment->type);
        $this->assertEquals(12.50, (float) $adjustment->amount);
        $this->assertStringContainsString('Copa Cóctel Martini', $adjustment->description);
    }

    /**
     * Test 2: 0.5 units of leftover rum results in the correct stock level.
     */
    public function test_half_unit_leftover_rum_results_in_correct_stock_level(): void
    {
        [$rumProduct, $stockProduct] = $this->createProductWithStock([
            'descripcion' => 'Ron Añejo Botella 750ml',
            'rentable' => false, // Consumable
            'opcion' => 1,
            'precio_compra' => 45.00,
            'replacement_cost' => 45.00,
        ], 10.0);

        $checklist = $this->createChecklist();

        // Sent 1 bottle. Consumed 0.5 bottles, 0.5 bottles returned OK.
        $checklistItem = EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'idproducto' => $rumProduct->id,
            'producto_id' => $rumProduct->id,
            'descripcion' => 'Ron Añejo Botella 750ml',
            'categoria' => 'Insumos / Bebidas',
            'cantidad' => 1,
            'cantidad_planeada' => 1,
            'qty_enviada' => 1.0,
            'qty_ok' => 0.5,
            'qty_danada' => 0,
            'qty_quebrada' => 0,
            'qty_faltante' => 0,
            'qty_consumida' => 0.5,
        ]);

        $this->assertEquals(1.0, $checklistItem->sumReturnQuantities());
        $this->assertTrue($checklistItem->isReturnBalanced());

        // Close return
        $result = $this->returnService->closeReturn($checklist, $this->adminUser);

        $this->assertTrue($result['success']);

        // Assert stock reduced by 0.5 (10.0 -> 9.5)
        $stockProduct->refresh();
        $this->assertEquals(9.5, (float) $stockProduct->stock_actual);

        $rumProduct->refresh();
        $this->assertEquals(9.5, (float) $rumProduct->stock_actual);

        // Assert InventoryMovement recorded for 0.5 units
        $movement = InventoryMovement::where('checklist_id', $checklist->id)
            ->where('product_id', $rumProduct->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovement::TYPE_RETORNO_EVENTO, $movement->type);
        $this->assertEquals(0.5, (float) $movement->quantity);

        // No penalty adjustments should be created for normal consumption of open bar items
        $adjustment = ContractAdjustment::where('checklist_item_id', $checklistItem->id)->first();
        $this->assertNull($adjustment);
    }

    /**
     * Test 3: Closing the checklist twice does not duplicate entries (idempotency).
     */
    public function test_closing_checklist_twice_is_idempotent(): void
    {
        [$glassProduct, $stockProduct] = $this->createProductWithStock([
            'descripcion' => 'Vaso Highball',
            'rentable' => true,
            'opcion' => 1,
            'replacement_cost' => 8.00,
        ], 30.0);

        $checklist = $this->createChecklist();

        $checklistItem = EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'idproducto' => $glassProduct->id,
            'producto_id' => $glassProduct->id,
            'descripcion' => 'Vaso Highball',
            'categoria' => 'Cristalería',
            'cantidad' => 10,
            'cantidad_planeada' => 10,
            'qty_enviada' => 10,
            'qty_ok' => 8,
            'qty_danada' => 0,
            'qty_quebrada' => 2,
            'qty_faltante' => 0,
            'qty_consumida' => 0,
        ]);

        // First closure
        $result1 = $this->returnService->closeReturn($checklist, $this->adminUser);
        $this->assertTrue($result1['success']);
        $this->assertTrue($result1['stock_applied']);

        $stockProduct->refresh();
        $this->assertEquals(28.0, (float) $stockProduct->stock_actual);

        $movementsCount1 = InventoryMovement::where('checklist_id', $checklist->id)->count();
        $adjustmentsCount1 = ContractAdjustment::where('checklist_item_id', $checklistItem->id)->count();

        $this->assertEquals(1, $movementsCount1);
        $this->assertEquals(1, $adjustmentsCount1);

        // Second closure call (idempotency check)
        $result2 = $this->returnService->closeReturn($checklist->fresh(), $this->adminUser);
        $this->assertTrue($result2['success']);
        $this->assertFalse($result2['stock_applied']); // Idempotent: stock was not applied again

        // Stock must remain unchanged
        $stockProduct->refresh();
        $this->assertEquals(28.0, (float) $stockProduct->stock_actual);

        $movementsCount2 = InventoryMovement::where('checklist_id', $checklist->id)->count();
        $adjustmentsCount2 = ContractAdjustment::where('checklist_item_id', $checklistItem->id)->count();

        $this->assertEquals($movementsCount1, $movementsCount2);
        $this->assertEquals($adjustmentsCount1, $adjustmentsCount2);
    }

    /**
     * Test 4: Ignored service items (opcion = 2).
     */
    public function test_service_items_with_opcion_2_are_ignored_by_inventory(): void
    {
        [$serviceProduct, $stockProduct] = $this->createProductWithStock([
            'descripcion' => 'Servicio de Bartender Profesional',
            'rentable' => false,
            'opcion' => 2, // Service
            'precio_venta' => 150.00,
        ], 0.0);

        $checklist = $this->createChecklist();

        $checklistItem = EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'idproducto' => $serviceProduct->id,
            'producto_id' => $serviceProduct->id,
            'descripcion' => 'Servicio de Bartender Profesional',
            'categoria' => 'Servicios',
            'cantidad' => 2,
            'cantidad_planeada' => 2,
            'qty_enviada' => 2,
            'qty_ok' => 0,
            'qty_danada' => 0,
            'qty_quebrada' => 0,
            'qty_faltante' => 0,
            'qty_consumida' => 2,
        ]);

        $result = $this->returnService->closeReturn($checklist, $this->adminUser);
        $this->assertTrue($result['success']);

        // Stock must not be deducted
        $stockProduct->refresh();
        $this->assertEquals(0.0, (float) $stockProduct->stock_actual);

        // No inventory movement should exist for opcion=2
        $movements = InventoryMovement::where('checklist_id', $checklist->id)
            ->where('product_id', $serviceProduct->id)
            ->count();

        $this->assertEquals(0, $movements);
    }

    /**
     * Test 5: Preventing closure if the sum of categories does not match the submitted total.
     */
    public function test_prevents_closure_if_sum_of_categories_does_not_match_sent_total(): void
    {
        [$glassProduct] = $this->createProductWithStock([
            'descripcion' => 'Copa Huracán',
            'rentable' => true,
            'opcion' => 1,
        ], 40.0);

        $checklist = $this->createChecklist();

        // Sent 10, but sum of categories is 7 + 1 = 8 (!= 10)
        EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'idproducto' => $glassProduct->id,
            'producto_id' => $glassProduct->id,
            'descripcion' => 'Copa Huracán',
            'categoria' => 'Cristalería',
            'cantidad' => 10,
            'cantidad_planeada' => 10,
            'qty_enviada' => 10,
            'qty_ok' => 7,
            'qty_danada' => 0,
            'qty_quebrada' => 1,
            'qty_faltante' => 0,
            'qty_consumida' => 0, // Sum = 8
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no coinciden');

        $this->returnService->closeReturn($checklist, $this->adminUser);

        // Assert checklist is not closed
        $this->assertNull($checklist->fresh()->retorno_cerrado_at);
        $this->assertNull($checklist->fresh()->stock_aplicado_at);
    }

    /**
     * Test HTTP endpoint for closing return with validation and response.
     */
    public function test_http_endpoint_close_and_reopen_return_with_audit(): void
    {
        [$glassProduct, $stockProduct] = $this->createProductWithStock([
            'descripcion' => 'Copa Flauta Champagne',
            'rentable' => true,
            'opcion' => 1,
            'replacement_cost' => 10.00,
        ], 25.0);

        $checklist = $this->createChecklist();

        $item = EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'idproducto' => $glassProduct->id,
            'producto_id' => $glassProduct->id,
            'descripcion' => 'Copa Flauta Champagne',
            'categoria' => 'Cristalería',
            'cantidad' => 10,
            'cantidad_planeada' => 10,
            'qty_enviada' => 10,
            'qty_ok' => 9,
            'qty_danada' => 0,
            'qty_quebrada' => 1,
            'qty_faltante' => 0,
            'qty_consumida' => 0,
        ]);

        // 1. Close Return via HTTP POST
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.event_checklists.close_return', $checklist->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
        ]);

        $this->assertEquals(24.0, (float) $stockProduct->fresh()->stock_actual);

        // 2. Reopen Return via HTTP POST with audit reason
        $reopenResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.event_checklists.reopen_return', $checklist->id), [
                'motivo_reapertura' => 'Error en conteo: se encontró la copa extraviada en la barra de soporte.',
            ]);

        $reopenResponse->assertStatus(200);
        $reopenResponse->assertJson([
            'status' => true,
        ]);

        // Assert reversal of stock deduction
        $this->assertEquals(25.0, (float) $stockProduct->fresh()->stock_actual);

        $checklist->refresh();
        $this->assertNull($checklist->retorno_cerrado_at);
        $this->assertNull($checklist->stock_aplicado_at);
        $this->assertNotNull($checklist->reabierto_at);
        $this->assertEquals('Error en conteo: se encontró la copa extraviada en la barra de soporte.', $checklist->motivo_reapertura);
    }
}
