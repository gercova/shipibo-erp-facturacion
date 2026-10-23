<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractInstallment;
use App\Models\EventChecklist;
use App\Models\EventChecklistItem;
use App\Models\IdentityDocumentType;
use App\Models\Product;
use App\Models\ShipmentGuide;
use App\Models\User;
use App\Services\Ebilling\ContractEbillingService;
use App\Services\Ebilling\Payload\BillingPayloadBuilder;
use App\Services\Ebilling\SunatGreDispatchService;
use App\Services\Events\EventChecklistShipmentGuideService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class ContractBillingAndGreTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected IdentityDocumentType $dniDocType;

    protected IdentityDocumentType $rucDocType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first() ?: User::factory()->create();
        $this->dniDocType = IdentityDocumentType::where('codigo', '1')->firstOrFail();
        $this->rucDocType = IdentityDocumentType::where('codigo', '6')->firstOrFail();
    }

    protected function createTestContract(bool $withRuc = false, float $total = 2000.00): Contract
    {
        $client = Client::create([
            'iddoc' => $withRuc ? $this->rucDocType->id : $this->dniDocType->id,
            'nro_documento' => $withRuc ? '20601234567' : '45678912',
            'nombres' => $withRuc ? 'EMPRESA EVENTOS S.A.C.' : 'JUAN PEREZ MENDOZA',
            'direccion' => 'JR. SAN MARTIN 123, TARAPOTO',
            'telefono' => '942000111',
            'ubigeo' => '220901',
            'estado' => 1,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CON-'.date('Y').'-'.str_pad((string) rand(100, 999), 4, '0', STR_PAD_LEFT),
            'idcliente' => $client->id,
            'provider_name' => 'MYTEMS E.I.R.L.',
            'provider_document' => '20600000001',
            'provider_representative' => 'Gerencia General',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'fecha_evento' => now()->addDays(5)->toDateString(),
            'hora_evento' => '20:00:00',
            'lugar_evento' => 'HOTEL RIO SHILCAYO, TARAPOTO',
            'subtotal' => round($total / 1.18, 2),
            'igv' => round($total - round($total / 1.18, 2), 2),
            'total' => $total,
            'estado' => Contract::STATUS_SIGNED,
            'status' => Contract::STATUS_STR_VIGENTE,
            'source' => Contract::SOURCE_INTERNO,
            'idusuario' => $this->adminUser->id,
            'idalmacen' => 1,
        ]);

        // Create 2 default installments (20% advance, 80% balance)
        ContractInstallment::create([
            'contract_id' => $contract->id,
            'numero_cuota' => 1,
            'descripcion' => 'Adelanto / Seña (20%)',
            'monto' => round($total * 0.20, 2),
            'porcentaje' => 20.00,
            'fecha_vencimiento' => now()->toDateString(),
            'estado' => ContractInstallment::STATUS_PENDING,
        ]);

        ContractInstallment::create([
            'contract_id' => $contract->id,
            'numero_cuota' => 2,
            'descripcion' => 'Saldo Final (80%)',
            'monto' => round($total * 0.80, 2),
            'porcentaje' => 80.00,
            'fecha_vencimiento' => now()->addDays(5)->toDateString(),
            'estado' => ContractInstallment::STATUS_PENDING,
        ]);

        return $contract;
    }

    /**
     * Scope 1: Verify service catalog items have proper SUNAT code, unit of measure ZZ, and opcion=2.
     */
    public function test_services_in_catalog_exist_with_sunat_code_unit_and_opcion_2(): void
    {
        $mozo = Product::where('descripcion', 'LIKE', '%SERVICIO DE MOZO%')->first();
        $this->assertNotNull($mozo, 'El servicio de mozo debe existir en el catálogo.');
        $this->assertEquals(2, $mozo->opcion, 'El servicio de mozo debe tener opcion=2.');
        $this->assertEquals(61, $mozo->idunidad, 'El servicio de mozo debe tener unidad ZZ (61).');
        $this->assertNotEmpty($mozo->codigo_sunat, 'El servicio de mozo debe tener código SUNAT.');

        $transporte = Product::where('descripcion', 'LIKE', '%TRANSPORTE%')->first();
        $this->assertNotNull($transporte, 'El servicio de transporte/flete debe existir.');
        $this->assertEquals(2, $transporte->opcion, 'El servicio de transporte debe tener opcion=2.');
        $this->assertEquals(61, $transporte->idunidad, 'El servicio de transporte debe tener unidad ZZ (61).');

        $barman = Product::where('descripcion', 'LIKE', '%BARMAN%')->orWhere('descripcion', 'LIKE', '%BARTENDER%')->first();
        $this->assertNotNull($barman, 'El servicio de bartender/barman debe existir.');
        $this->assertEquals(2, $barman->opcion, 'El servicio de barman debe tener opcion=2.');
    }

    /**
     * Scope 2: GRE covers TRANSPORT of physical goods only, strictly excluding services (opcion=2).
     */
    public function test_generate_gre_from_checklist_transports_only_physical_goods_excluding_services(): void
    {
        $contract = $this->createTestContract();

        // Ensure physical product exists (opcion = 1)
        $physicalProduct = Product::where('opcion', 1)->first() ?: Product::create([
            'codigo_interno' => 'P-RON-01',
            'codigo_sunat' => '50202206',
            'descripcion' => 'BOTELLA DE RON CARTAVIO BLACK 750ML',
            'idunidad' => 1, // NIU
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => 1,
            'opcion' => 1,
            'precio_venta' => 45.00,
        ]);

        // Service product (opcion = 2)
        $serviceProduct = Product::where('opcion', 2)->first();

        // Create checklist with both physical items and service items
        $checklist = EventChecklist::create([
            'contract_id' => $contract->id,
            'idusuario' => $this->adminUser->id,
            'codigo' => 'CHK-TEST-0001',
            'titulo' => 'Checklist Prueba GRE',
            'fecha_evento' => now()->addDays(2),
            'lugar_evento' => 'RESORT SAUCE LAGOON, TARAPOTO',
            'estado' => EventChecklist::STATUS_PLANNED,
            'total_items' => 2,
            'items_llevados' => 0,
            'items_devueltos' => 0,
            'items_con_incidencia' => 0,
            'idalmacen' => 1,
        ]);

        // Physical item (should be included in GRE)
        EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'product_id' => $physicalProduct->id,
            'descripcion' => $physicalProduct->descripcion,
            'cantidad_planeada' => 12,
            'cantidad_llevada' => 12,
            'qty_enviada' => 12,
            'llevado' => true,
        ]);

        // Service item (waitstaff / mozo - MUST be excluded from GRE!)
        EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'product_id' => $serviceProduct->id,
            'descripcion' => $serviceProduct->descripcion,
            'cantidad_planeada' => 2,
            'cantidad_llevada' => 2,
            'qty_enviada' => 2,
            'llevado' => true,
        ]);

        /** @var EventChecklistShipmentGuideService $greService */
        $greService = app(EventChecklistShipmentGuideService::class);
        $guide = $greService->generateGuideFromChecklist($checklist, [
            'placa_vehiculo' => 'T1X-888',
            'llegada_ubigeo' => '220901', // Tarapoto
            'mock' => true,
        ]);

        $this->assertInstanceOf(ShipmentGuide::class, $guide);
        $this->assertEquals('T001', $guide->serie);
        $this->assertEquals('02', $guide->modo_transporte, 'El modo de transporte debe ser privado (02).');
        $this->assertEquals('T1X-888', $guide->placa_vehiculo);
        $this->assertEquals('220901', $guide->llegada_ubigeo);
        $this->assertEquals($contract->id, $guide->contract_id, 'La GRE debe estar vinculada al contrato.');
        $this->assertEquals($checklist->id, $guide->checklist_id, 'La GRE debe estar vinculada al checklist.');

        // Assert bidirectional link on checklist
        $checklist->refresh();
        $this->assertTrue($checklist->hasShipmentGuide());
        $this->assertEquals($guide->id, $checklist->shipment_guide_id);

        // Verify items in GRE: MUST have 1 physical item, and NO service items
        $this->assertCount(1, $guide->items);
        $guideItem = $guide->items->first();
        $this->assertEquals($physicalProduct->id, $guideItem->product_id);
        $this->assertEquals(12.00, (float) $guideItem->cantidad);
    }

    /**
     * Scope 2: GRE Destination ubigeo validation.
     */
    public function test_generate_gre_validates_destination_ubigeo_fails_on_invalid_code(): void
    {
        $contract = $this->createTestContract();

        $checklist = EventChecklist::create([
            'contract_id' => $contract->id,
            'idusuario' => $this->adminUser->id,
            'codigo' => 'CHK-UBIGEO-01',
            'titulo' => 'Checklist Ubigeo Test',
            'fecha_evento' => now()->addDays(2),
            'lugar_evento' => 'LUGAR TEST',
            'estado' => EventChecklist::STATUS_PLANNED,
            'total_items' => 1,
            'items_llevados' => 0,
            'items_devueltos' => 0,
            'items_con_incidencia' => 0,
        ]);

        $physicalProduct = Product::where('opcion', 1)->first();
        EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'product_id' => $physicalProduct->id,
            'descripcion' => $physicalProduct->descripcion,
            'cantidad_planeada' => 5,
            'qty_enviada' => 5,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no es un ubigeo válido');

        /** @var EventChecklistShipmentGuideService $greService */
        $greService = app(EventChecklistShipmentGuideService::class);
        $greService->generateGuideFromChecklist($checklist, [
            'llegada_ubigeo' => '999999', // Non-existent ubigeo code
        ]);
    }

    /**
     * Scope 3: Invoicing installment - Factura requires client RUC (11 digits, type 6).
     */
    public function test_invoice_installment_requires_ruc_for_factura(): void
    {
        // Client with DNI (8 digits)
        $contract = $this->createTestContract(withRuc: false);
        $installment = $contract->installments()->first();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('debe poseer RUC válido de 11 dígitos');

        /** @var ContractEbillingService $ebillingService */
        $ebillingService = app(ContractEbillingService::class);
        $ebillingService->invoiceInstallment($installment, [
            'tipo_documento' => '01', // Requesting Factura
        ]);
    }

    /**
     * Scope 3: Invoicing installment succeeds with Boleta (for DNI) and Factura (for RUC).
     */
    public function test_invoice_installment_succeeds_with_boleta_or_ruc_for_factura(): void
    {
        /** @var ContractEbillingService $ebillingService */
        $ebillingService = app(ContractEbillingService::class);

        // 1. Issue Boleta (03) for DNI client
        $dniContract = $this->createTestContract(withRuc: false, total: 1000.00);
        $dniInstallment = $dniContract->installments()->first();

        $boletaResult = $ebillingService->invoiceInstallment($dniInstallment, [
            'tipo_documento' => '03',
        ]);

        /** @var Billing $boleta */
        $boleta = $boletaResult['billing'];
        $this->assertEquals('B001', $boleta->serie);
        $this->assertEquals(200.00, (float) $boleta->total);
        $this->assertEquals($dniContract->id, $boleta->contract_id);
        $this->assertEquals($dniInstallment->id, $boleta->installment_id);

        $dniInstallment->refresh();
        $this->assertTrue($dniInstallment->isBilled());
        $this->assertEquals($boleta->id, $dniInstallment->billing_id);
        $this->assertEquals("{$boleta->serie}-{$boleta->correlativo}", $dniInstallment->factura_numero);

        // 2. Issue Factura (01) for RUC client
        $rucContract = $this->createTestContract(withRuc: true, total: 3000.00);
        $rucInstallment = $rucContract->installments()->first();

        $facturaResult = $ebillingService->invoiceInstallment($rucInstallment, [
            'tipo_documento' => '01',
        ]);

        /** @var Billing $factura */
        $factura = $facturaResult['billing'];
        $this->assertEquals('F001', $factura->serie);
        $this->assertEquals(600.00, (float) $factura->total);
        $this->assertEquals($rucContract->id, $factura->contract_id);

        $rucInstallment->refresh();
        $this->assertTrue($rucInstallment->isBilled());
        $this->assertEquals($factura->id, $rucInstallment->billing_id);
    }

    /**
     * Scope 3: Duplicate invoicing prevention for the same installment.
     */
    public function test_duplicate_invoicing_prevention_on_same_installment(): void
    {
        $contract = $this->createTestContract(withRuc: false);
        $installment = $contract->installments()->first();

        /** @var ContractEbillingService $ebillingService */
        $ebillingService = app(ContractEbillingService::class);

        // First invoice succeeds
        $firstResult = $ebillingService->invoiceInstallment($installment, [
            'tipo_documento' => '03',
        ]);
        $this->assertNotNull($firstResult['billing']);

        $installment->refresh();
        $this->assertTrue($installment->isBilled());

        // Second invoice on same installment MUST throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ya se encuentra facturada');

        $ebillingService->invoiceInstallment($installment, [
            'tipo_documento' => '03',
        ]);
    }

    /**
     * Scope 3 & 5: Mixed IGV/VAT scenario and BillingPayloadBuilder output verification.
     */
    public function test_mixed_igv_vat_scenario_and_billing_payload_builder_compliance(): void
    {
        $contract = $this->createTestContract(withRuc: true, total: 1000.00);
        $installment = $contract->installments()->first();

        $serviceGravado = Product::where('opcion', 2)->where('idcodigo_igv', 1)->first();
        $serviceExonerado = Product::where('opcion', 2)->where('idcodigo_igv', 2)->first() ?: Product::create([
            'codigo_interno' => 'SRV-EXO-01',
            'codigo_sunat' => '80141600',
            'descripcion' => 'SERVICIO EXONERADO EN REGION SELVA',
            'idunidad' => 61,
            'idcategoria' => 1,
            'igv' => 0,
            'idcodigo_igv' => 2,
            'opcion' => 2,
            'precio_venta' => 200.00,
        ]);

        /** @var ContractEbillingService $ebillingService */
        $ebillingService = app(ContractEbillingService::class);

        // Custom mixed items: S/ 118.00 Gravado (100 base + 18 IGV) and S/ 200.00 Exonerado (200 base + 0 IGV)
        $mixedResult = $ebillingService->invoiceInstallment($installment, [
            'tipo_documento' => '01',
            'items' => [
                [
                    'product_id' => $serviceGravado->id,
                    'descripcion' => 'SERVICIO DE MOZOS Y ATENCION (GRAVADO)',
                    'cantidad' => 1,
                    'precio_unitario' => 118.00,
                    'id_afectacion_igv' => 1, // 10 Gravado
                ],
                [
                    'product_id' => $serviceExonerado->id,
                    'descripcion' => 'SERVICIO DE COCTELERIA EVENTO (EXONERADO LEY AMAZONIA)',
                    'cantidad' => 1,
                    'precio_unitario' => 200.00,
                    'id_afectacion_igv' => 2, // 20 Exonerado
                ],
            ],
        ]);

        /** @var Billing $billing */
        $billing = $mixedResult['billing'];
        $this->assertEquals(100.00, (float) $billing->gravada);
        $this->assertEquals(200.00, (float) $billing->exonerada);
        $this->assertEquals(18.00, (float) $billing->igv);
        $this->assertEquals(318.00, (float) $billing->total);

        // Verify BillingPayloadBuilder generated output
        $payload = $mixedResult['payload'];
        $this->assertEquals('01', $payload['documento']['tipo']);
        $this->assertEquals('F001', $payload['documento']['serie']);
        $this->assertEquals(100.00, $payload['totales']['gravadas']);
        $this->assertEquals(200.00, $payload['totales']['exoneradas']);
        $this->assertEquals(18.00, $payload['totales']['igv']);
        $this->assertEquals(318.00, $payload['totales']['importe_total']);

        $this->assertCount(2, $payload['items']);
        $this->assertEquals('10', $payload['items'][0]['afectacion_igv']['codigo']);
        $this->assertEquals('20', $payload['items'][1]['afectacion_igv']['codigo']);
    }

    /**
     * Scope 4: Breakages/damages (B4) included as line item in second installment.
     */
    public function test_breakage_damages_as_line_item_in_second_installment(): void
    {
        $contract = $this->createTestContract(withRuc: false, total: 1000.00);
        $installment2 = $contract->installments()->where('numero_cuota', 2)->first();

        // Record breakage adjustment (B4)
        $glassProduct = Product::where('opcion', 2)->where('descripcion', 'LIKE', '%VASO%')->first() ?: Product::where('opcion', 2)->first();
        ContractAdjustment::create([
            'contract_id' => $contract->id,
            'installment_id' => $installment2->id,
            'type' => ContractAdjustment::TYPE_BREAKAGE,
            'description' => 'Rotura de 2 vasos de cristal en evento',
            'amount' => 50.00,
            'product_id' => $glassProduct->id,
            'user_id' => $this->adminUser->id,
        ]);

        /** @var ContractEbillingService $ebillingService */
        $ebillingService = app(ContractEbillingService::class);
        $result = $ebillingService->invoiceInstallment($installment2, [
            'tipo_documento' => '03',
            'include_breakages' => true,
        ]);

        /** @var Billing $billing */
        $billing = $result['billing'];
        $this->assertEquals($installment2->id, $billing->installment_id);

        // Has 2 items: base service + breakage line item
        $this->assertCount(2, $billing->details);
        $breakageDetail = $billing->details->last();
        $this->assertStringContainsString('REPOSICIÓN / DAÑO', (string) ($breakageDetail->descripcion_custom ?: $breakageDetail->product?->descripcion));
        $this->assertEquals(50.00, (float) $breakageDetail->precio_total);
    }

    /**
     * Scope 4: Breakages/damages (B4) issued as Debit Note (08) referencing parent invoice.
     */
    public function test_breakage_damages_issued_as_debit_note_referencing_parent_billing(): void
    {
        $contract = $this->createTestContract(withRuc: true, total: 1000.00);
        $installment1 = $contract->installments()->first();

        /** @var ContractEbillingService $ebillingService */
        $ebillingService = app(ContractEbillingService::class);

        // 1. Invoice installment 1 as Factura
        $invoiceResult = $ebillingService->invoiceInstallment($installment1, [
            'tipo_documento' => '01',
        ]);
        /** @var Billing $parentBilling */
        $parentBilling = $invoiceResult['billing'];
        $this->assertEquals('F001', $parentBilling->serie);

        // 2. Event closes with damage adjustments
        $cupProduct = Product::where('opcion', 2)->first();
        ContractAdjustment::create([
            'contract_id' => $contract->id,
            'type' => ContractAdjustment::TYPE_BREAKAGE,
            'description' => 'Rotura de 3 copas de coctelería',
            'amount' => 75.00,
            'product_id' => $cupProduct->id,
            'user_id' => $this->adminUser->id,
        ]);

        // 3. Issue Debit Note (08) referencing parent billing
        $debitNoteResult = $ebillingService->issueDebitNoteForAdjustments($contract, $parentBilling, [
            'motivo_codigo' => '03', // PENALIDADES / OTROS CONCEPTOS
            'motivo' => 'PENALIDAD POR ROTURA DE COPAS SEGUN ACTA DE RETORNO',
        ]);

        /** @var Billing $debitNote */
        $debitNote = $debitNoteResult['debit_note'];
        $this->assertEquals('FD01', $debitNote->serie, 'La serie para nota de débito de factura debe ser FD01.');
        $this->assertEquals($parentBilling->id, $debitNote->idfactura_anular, 'Debe referenciar el ID del comprobante padre.');
        $this->assertEquals(75.00, (float) $debitNote->total);

        // Verify SUNAT payload generated for Debit Note
        $payload = $debitNoteResult['payload'];
        $this->assertEquals('08', $payload['documento']['tipo']);
        $this->assertEquals('FD01', $payload['documento']['serie']);
        $this->assertEquals('01', $payload['documento_referencia']['tipo']);
        $this->assertEquals('F001', $payload['documento_referencia']['serie']);
        $this->assertEquals($parentBilling->correlativo, $payload['documento_referencia']['correlativo']);
        $this->assertEquals('03', $payload['motivo']['codigo']);
    }

    /**
     * Scope 2: Consignment Note (GRE) Sunat Beta mode output verification.
     */
    public function test_gre_sunat_beta_mode_payload_output(): void
    {
        $contract = $this->createTestContract();
        $checklist = EventChecklist::create([
            'contract_id' => $contract->id,
            'idusuario' => $this->adminUser->id,
            'codigo' => 'CHK-GRE-BETA-01',
            'titulo' => 'Checklist Prueba GRE Beta',
            'fecha_evento' => now()->addDays(3),
            'lugar_evento' => 'LOCAL DE EVENTOS TARAPOTO',
            'estado' => EventChecklist::STATUS_PLANNED,
            'total_items' => 1,
            'items_llevados' => 0,
            'items_devueltos' => 0,
            'items_con_incidencia' => 0,
        ]);

        $physicalProduct = Product::where('opcion', 1)->first();
        EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'product_id' => $physicalProduct->id,
            'descripcion' => $physicalProduct->descripcion,
            'cantidad_planeada' => 10,
            'qty_enviada' => 10,
        ]);

        /** @var EventChecklistShipmentGuideService $greService */
        $greService = app(EventChecklistShipmentGuideService::class);
        $guide = $greService->generateGuideFromChecklist($checklist, [
            'placa_vehiculo' => 'X1Y-999',
            'llegada_ubigeo' => '220901',
            'send_to_sunat' => false, // We test dispatch separately below
        ]);

        /** @var SunatGreDispatchService $dispatchService */
        $dispatchService = app(SunatGreDispatchService::class);
        $dispatchResult = $dispatchService->dispatch($guide, ['mock' => true]);

        $this->assertTrue($dispatchResult['ok']);
        $this->assertEquals('ACEPTADO', $dispatchResult['status']);
        $this->assertNotEmpty($dispatchResult['ticket']);

        $payload = $dispatchResult['payload'];
        $this->assertEquals('09', $payload['tipoDoc']);
        $this->assertEquals('T001', $payload['serie']);
        $this->assertEquals('02', $payload['datosEnvio']['modTraslado']);
        $this->assertEquals('X1Y-999', $payload['datosEnvio']['vehiculo']['placa']);
        $this->assertEquals('220901', $payload['datosEnvio']['llegada']['ubigeo']);
        $this->assertCount(1, $payload['detalles']);
        $this->assertEquals(10.00, $payload['detalles'][0]['cantidad']);
    }

    /**
     * HTTP Endpoints test via POST routes.
     */
    public function test_http_routes_for_gre_generation_and_installment_invoicing(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Test POST generate-gre route
        $contract = $this->createTestContract();
        $checklist = EventChecklist::create([
            'contract_id' => $contract->id,
            'idusuario' => $this->adminUser->id,
            'codigo' => 'CHK-HTTP-01',
            'titulo' => 'Checklist HTTP Test',
            'fecha_evento' => now()->addDays(2),
            'lugar_evento' => 'CENTRO DE CONVENCIONES TARAPOTO',
            'estado' => EventChecklist::STATUS_PLANNED,
            'total_items' => 1,
            'items_llevados' => 0,
            'items_devueltos' => 0,
            'items_con_incidencia' => 0,
        ]);

        $physicalProduct = Product::where('opcion', 1)->first();
        EventChecklistItem::create([
            'checklist_id' => $checklist->id,
            'product_id' => $physicalProduct->id,
            'descripcion' => $physicalProduct->descripcion,
            'cantidad_planeada' => 6,
            'qty_enviada' => 6,
        ]);

        $responseGre = $this->postJson(route('admin.event_checklists.generate_gre', $checklist->id), [
            'placa_vehiculo' => 'M1-9999',
            'llegada_ubigeo' => '220901',
        ]);

        $responseGre->assertOk();
        $responseGre->assertJson(['status' => true]);
        $this->assertNotEmpty($responseGre->json('guide_id'));
        $this->assertNotEmpty($responseGre->json('numero'));

        // 2. Test POST invoice installment route
        $installment = $contract->installments()->first();
        $responseInvoice = $this->postJson(route('admin.contracts.invoice_installment', [
            'id' => $contract->id,
            'installmentId' => $installment->id,
        ]), [
            'tipo_documento' => '03', // Boleta
        ]);

        $responseInvoice->assertOk();
        $responseInvoice->assertJson(['status' => true]);
        $this->assertNotEmpty($responseInvoice->json('billing_id'));
        $this->assertNotEmpty($responseInvoice->json('factura_numero'));
    }
}
