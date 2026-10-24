<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractInstallment;
use App\Models\ContractItem;
use App\Models\EventChecklist;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContractInventoryReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected Warehouse $warehouse;

    protected Contract $contract;

    protected Product $glassProduct;

    protected Product $plateProduct;

    protected Product $barProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first() ?? User::factory()->create();
        if (! $this->adminUser->hasRole(['SUPERADMIN', 'ADMIN'])) {
            $this->adminUser->assignRole('ADMIN');
        }

        $this->warehouse = Warehouse::first() ?? Warehouse::create([
            'descripcion' => 'Almacén Principal Test',
            'direccion' => 'Jr. San Martín 123',
        ]);

        $unit = Unit::first() ?? Unit::create(['descripcion' => 'UNIDAD']);

        $client = Client::first() ?? Client::create([
            'iddoc' => 1,
            'nro_documento' => '44556677',
            'nombres' => 'EMPRESA EVENTOS S.A.C.',
            'telefono' => '999888777',
            'direccion' => 'Av. Bolognesi 456',
            'estado' => 1,
        ]);

        // Create rentable glassware product
        $this->glassProduct = Product::create([
            'codigo_interno' => 'PRD-'.uniqid(),
            'codigo_barras' => 'BAR-'.uniqid(),
            'descripcion' => 'Copa Martini Cristal 250ml',
            'idcategoria' => 1,
            'idunidad' => $unit->id,
            'igv' => 1,
            'idcodigo_igv' => 1,
            'rentable' => true,
            'opcion' => 1,
            'precio_compra' => 10.00,
            'precio_venta' => 15.00,
            'replacement_cost' => 12.00,
            'stock_actual' => 100.00,
            'estado' => 1,
        ]);

        StockProduct::create([
            'idproducto' => $this->glassProduct->id,
            'idalmacen' => $this->warehouse->id,
            'stock_minimo' => 5,
            'stock_actual' => 100.00,
            'precio_compra' => $this->glassProduct->precio_compra,
            'precio_venta' => $this->glassProduct->precio_venta,
            'fecha_registro' => now()->toDateString(),
            'stock_entrada' => 100.00,
        ]);

        // Create rentable plates product
        $this->plateProduct = Product::create([
            'codigo_interno' => 'PRD-'.uniqid(),
            'codigo_barras' => 'BAR-'.uniqid(),
            'descripcion' => 'Platos Base Porcelana 30cm',
            'idcategoria' => 1,
            'idunidad' => $unit->id,
            'igv' => 1,
            'idcodigo_igv' => 1,
            'rentable' => true,
            'opcion' => 1,
            'precio_compra' => 20.00,
            'precio_venta' => 25.00,
            'replacement_cost' => 25.00,
            'stock_actual' => 80.00,
            'estado' => 1,
        ]);

        StockProduct::create([
            'idproducto' => $this->plateProduct->id,
            'idalmacen' => $this->warehouse->id,
            'stock_minimo' => 5,
            'stock_actual' => 80.00,
            'precio_compra' => $this->plateProduct->precio_compra,
            'precio_venta' => $this->plateProduct->precio_venta,
            'fecha_registro' => now()->toDateString(),
            'stock_entrada' => 80.00,
        ]);

        // Create mobile bar product
        $this->barProduct = Product::create([
            'codigo_interno' => 'PRD-'.uniqid(),
            'codigo_barras' => 'BAR-'.uniqid(),
            'descripcion' => 'Barra Móvil Vintage Iluminada',
            'idcategoria' => 1,
            'idunidad' => $unit->id,
            'igv' => 1,
            'idcodigo_igv' => 1,
            'rentable' => true,
            'opcion' => 1,
            'precio_compra' => 500.00,
            'precio_venta' => 800.00,
            'replacement_cost' => 600.00,
            'stock_actual' => 5.00,
            'estado' => 1,
        ]);

        StockProduct::create([
            'idproducto' => $this->barProduct->id,
            'idalmacen' => $this->warehouse->id,
            'stock_minimo' => 1,
            'stock_actual' => 5.00,
            'precio_compra' => $this->barProduct->precio_compra,
            'precio_venta' => $this->barProduct->precio_venta,
            'fecha_registro' => now()->toDateString(),
            'stock_entrada' => 5.00,
        ]);

        // Create Contract
        $this->contract = Contract::create([
            'contract_number' => 'CON-'.date('Y').'-8888',
            'tracking_code' => 'TRK-RECON-8888',
            'idcliente' => $client->id,
            'provider_name' => 'MYTEMS E.I.R.L.',
            'provider_document' => '20600000000',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'fecha_evento' => now()->addDays(15)->toDateString(),
            'subtotal' => 1000.00,
            'igv' => 180.00,
            'total' => 1180.00,
            'status' => Contract::STATUS_STR_VIGENTE,
            'estado' => Contract::STATUS_ACTIVE,
            'idalmacen' => $this->warehouse->id,
        ]);

        // Contract items
        ContractItem::create([
            'contract_id' => $this->contract->id,
            'idproducto' => $this->glassProduct->id,
            'descripcion' => $this->glassProduct->descripcion,
            'cantidad' => 20.00,
            'precio_unitario' => 15.00,
            'subtotal' => 300.00,
        ]);

        ContractItem::create([
            'contract_id' => $this->contract->id,
            'idproducto' => $this->plateProduct->id,
            'descripcion' => $this->plateProduct->descripcion,
            'cantidad' => 20.00,
            'precio_unitario' => 25.00,
            'subtotal' => 500.00,
        ]);

        ContractItem::create([
            'contract_id' => $this->contract->id,
            'idproducto' => $this->barProduct->id,
            'descripcion' => $this->barProduct->descripcion,
            'cantidad' => 1.00,
            'precio_unitario' => 380.00,
            'subtotal' => 380.00,
        ]);

        // Installments: 20% advance (paid) and 80% final balance (pending)
        ContractInstallment::create([
            'contract_id' => $this->contract->id,
            'numero_cuota' => 1,
            'descripcion' => 'Adelanto 20% Firma de Contrato',
            'porcentaje' => 20.00,
            'monto' => 236.00,
            'fecha_vencimiento' => now()->toDateString(),
            'estado' => ContractInstallment::STATUS_PAID,
            'idpago' => 1,
        ]);

        ContractInstallment::create([
            'contract_id' => $this->contract->id,
            'numero_cuota' => 2,
            'descripcion' => 'Saldo 80% Post-Evento y Liquidación',
            'porcentaje' => 80.00,
            'monto' => 944.00,
            'fecha_vencimiento' => now()->addDays(16)->toDateString(),
            'estado' => ContractInstallment::STATUS_PENDING,
        ]);
    }

    /**
     * Test getting reconciliation data via GET endpoint.
     */
    public function test_can_get_reconciliation_data_and_auto_generates_checklist(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.contracts.reconciliation', $this->contract->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'contract' => ['id', 'contract_number', 'total'],
            'client' => ['nombres'],
            'checklist' => ['id', 'codigo'],
            'items',
            'pending_installments',
            'total_estimated_penalty',
            'signo',
        ]);

        $this->assertTrue($response->json('status'));
        $this->assertCount(3, $response->json('items'));

        // Verify checklist was created
        $checklist = EventChecklist::where('contract_id', $this->contract->id)->first();
        $this->assertNotNull($checklist);
        $this->assertEquals(3, $checklist->items()->count());
    }

    /**
     * Test processing reconciliation when broken and missing items are reported,
     * verifying penalty calculation, contract adjustments, installment increase, and stock deductions.
     */
    public function test_can_process_reconciliation_with_penalties_for_broken_and_missing_items(): void
    {
        // 1. Initial GET to ensure checklist items exist
        $this->actingAs($this->adminUser)
            ->getJson(route('admin.contracts.reconciliation', $this->contract->id));

        $checklist = EventChecklist::where('contract_id', $this->contract->id)->first();
        $glassItem = $checklist->items()->where('idproducto', $this->glassProduct->id)->first();
        $plateItem = $checklist->items()->where('idproducto', $this->plateProduct->id)->first();
        $barItem = $checklist->items()->where('idproducto', $this->barProduct->id)->first();

        $targetInstallment = ContractInstallment::where('contract_id', $this->contract->id)
            ->where('estado', ContractInstallment::STATUS_PENDING)
            ->first();

        // 2. Report:
        // Glass: 20 sent -> 17 OK, 2 broken (roto), 1 missing (faltante). Unit penalty: 12.00 -> Penalty: (2+1)*12 = 36.00
        // Plate: 20 sent -> 19 OK, 1 missing (faltante). Unit penalty: 25.00 -> Penalty: 1*25 = 25.00
        // Bar: 1 sent -> 1 OK. Penalty: 0.00
        // Total penalty: 36.00 + 25.00 = 61.00
        $payload = [
            'installment_id' => $targetInstallment->id,
            'apply_stock' => 1,
            'apply_penalty' => 1,
            'items' => [
                $glassItem->id => [
                    'qty_enviada' => 20.00,
                    'qty_ok' => 17.00,
                    'qty_quebrada' => 2.00,
                    'qty_faltante' => 1.00,
                    'qty_danada' => 0.00,
                    'qty_consumida' => 0.00,
                    'unit_penalty' => 12.00,
                    'observaciones' => '2 copas rotas en brindis, 1 copa faltante',
                ],
                $plateItem->id => [
                    'qty_enviada' => 20.00,
                    'qty_ok' => 19.00,
                    'qty_quebrada' => 0.00,
                    'qty_faltante' => 1.00,
                    'qty_danada' => 0.00,
                    'qty_consumida' => 0.00,
                    'unit_penalty' => 25.00,
                    'observaciones' => '1 plato base extraviado en cocina',
                ],
                $barItem->id => [
                    'qty_enviada' => 1.00,
                    'qty_ok' => 1.00,
                    'qty_quebrada' => 0.00,
                    'qty_faltante' => 0.00,
                    'qty_danada' => 0.00,
                    'qty_consumida' => 0.00,
                    'unit_penalty' => 600.00,
                    'observaciones' => 'Barra devuelta en perfecto estado',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.contracts.process_reconciliation', $this->contract->id), $payload);

        $response->assertOk();
        $this->assertTrue($response->json('status'));
        $this->assertEquals(61.00, (float) $response->json('penalties'));

        // 3. Assert Contract Adjustments created
        $adjustments = ContractAdjustment::where('contract_id', $this->contract->id)->get();
        $this->assertCount(3, $adjustments); // Glass breakage, Glass missing, Plate missing

        $glassBreakAdj = $adjustments->where('type', ContractAdjustment::TYPE_BREAKAGE)->where('checklist_item_id', $glassItem->id)->first();
        $this->assertNotNull($glassBreakAdj);
        $this->assertEquals(24.00, (float) $glassBreakAdj->amount); // 2 * 12.00

        $glassMissingAdj = $adjustments->where('type', ContractAdjustment::TYPE_MISSING)->where('checklist_item_id', $glassItem->id)->first();
        $this->assertNotNull($glassMissingAdj);
        $this->assertEquals(12.00, (float) $glassMissingAdj->amount); // 1 * 12.00

        $plateMissingAdj = $adjustments->where('type', ContractAdjustment::TYPE_MISSING)->where('checklist_item_id', $plateItem->id)->first();
        $this->assertNotNull($plateMissingAdj);
        $this->assertEquals(25.00, (float) $plateMissingAdj->amount); // 1 * 25.00

        // 4. Assert Installment updated
        $targetInstallment->refresh();
        $this->assertEquals(944.00 + 61.00, (float) $targetInstallment->monto); // 1005.00

        // 5. Assert Contract Total updated
        $this->contract->refresh();
        $this->assertEquals(1180.00 + 61.00, (float) $this->contract->total); // 1241.00

        // 6. Assert Stock Product updated
        // Initial glass stock: 100 -> 2 broken + 1 missing = 3 deducted -> 97.00
        $glassStock = StockProduct::where('idproducto', $this->glassProduct->id)->where('idalmacen', $this->warehouse->id)->first();
        $this->assertEquals(97.00, (float) $glassStock->stock_actual);

        // Initial plate stock: 80 -> 1 missing = 1 deducted -> 79.00
        $plateStock = StockProduct::where('idproducto', $this->plateProduct->id)->where('idalmacen', $this->warehouse->id)->first();
        $this->assertEquals(79.00, (float) $plateStock->stock_actual);

        // 7. Assert Checklist Return is Closed
        $checklist->refresh();
        $this->assertNotNull($checklist->retorno_cerrado_at);
        $this->assertTrue($checklist->isReturnClosed());
    }
}
