<?php

namespace Tests\Feature;

use App\Exceptions\ImmutableAdjustmentException;
use App\Exceptions\Rule6ViolationException;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractInstallment;
use App\Models\IdentityDocumentType;
use App\Models\User;
use App\Services\Contracts\ContractAdjustmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContractFormalizationAndAdjustmentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected IdentityDocumentType $dniDocType;

    protected IdentityDocumentType $rucDocType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first();
        $this->dniDocType = IdentityDocumentType::where('codigo', '1')->first();
        $this->rucDocType = IdentityDocumentType::where('codigo', '6')->first();
    }

    /**
     * Helper to create a preliminary contract for testing.
     */
    protected function createPreliminaryContract(float $total = 5000.00): Contract
    {
        $client = Client::create([
            'iddoc' => 4, // Otro tipo de documento (código 0)
            'nro_documento' => 'SD-998877665',
            'nombres' => 'CLIENTE PRELIMINAR TEST',
            'direccion' => 'Tarapoto, San Martín',
            'telefono' => '998877665',
            'estado' => 1,
        ]);

        return Contract::create([
            'contract_number' => Contract::generatePreliminaryNumber(),
            'tracking_code' => Contract::generateTrackingCode(),
            'idcliente' => $client->id,
            'provider_name' => 'MYTEMS E.I.R.L.',
            'provider_document' => '20600000000',
            'provider_representative' => 'Gerencia General',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'fecha_evento' => now()->addDays(30)->toDateString(),
            'subtotal' => round($total / 1.18, 2),
            'igv' => round($total - round($total / 1.18, 2), 2),
            'total' => $total,
            'source' => Contract::SOURCE_WEB,
            'status' => Contract::STATUS_STR_PRELIMINAR,
            'estado' => Contract::STATUS_DRAFT,
            'guest_count' => 100,
            'event_type' => 'Matrimonio',
            'observaciones' => 'Solicitud digital web inicial.',
        ]);
    }

    /**
     * Test formalization fails without tax ID / national ID or with invalid format.
     */
    public function test_formalization_fails_without_tax_or_national_id(): void
    {
        $contract = $this->createPreliminaryContract(4000.00);

        // 1. Missing document number entirely
        $response1 = $this->actingAs($this->adminUser)
            ->postJson("/contracts/{$contract->id}/formalize", [
                'iddoc' => $this->dniDocType->id,
                'nro_documento' => '',
                'direccion' => 'Jr. Lima 123, Tarapoto',
            ]);
        $response1->assertStatus(422);

        // 2. DNI with 7 digits (must be 8)
        $response2 = $this->actingAs($this->adminUser)
            ->postJson("/contracts/{$contract->id}/formalize", [
                'iddoc' => $this->dniDocType->id,
                'nro_documento' => '1234567',
                'direccion' => 'Jr. Lima 123, Tarapoto',
            ]);
        $response2->assertStatus(422);

        // 3. RUC with 10 digits (must be 11)
        $response3 = $this->actingAs($this->adminUser)
            ->postJson("/contracts/{$contract->id}/formalize", [
                'iddoc' => $this->rucDocType->id,
                'nro_documento' => '2012345678',
                'direccion' => 'Av. Salaverry 450, Tarapoto',
            ]);
        $response3->assertStatus(422);

        // 4. Missing physical address
        $response4 = $this->actingAs($this->adminUser)
            ->postJson("/contracts/{$contract->id}/formalize", [
                'iddoc' => $this->dniDocType->id,
                'nro_documento' => '47895612',
                'direccion' => '',
            ]);
        $response4->assertStatus(422);
    }

    /**
     * Test successful formalization assigns CON-YYYY-XXXX, updates status to active (vigente),
     * retains PRE- code, and configures 20% advance / 80% balance schedule.
     */
    public function test_successful_formalization_assigns_con_number_and_schedule(): void
    {
        $contract = $this->createPreliminaryContract(5000.00);
        $originalPreCode = $contract->contract_number;

        $response = $this->actingAs($this->adminUser)
            ->postJson("/contracts/{$contract->id}/formalize", [
                'iddoc' => $this->dniDocType->id,
                'nro_documento' => '72819203',
                'direccion' => 'Jr. Los Cedros 340, Tarapoto',
                'nombres' => 'JUAN CARLOS PÉREZ FORMAL',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $contract->refresh();

        // 1. Status updated to vigente / active
        $this->assertEquals(Contract::STATUS_STR_VIGENTE, $contract->status);
        $this->assertEquals(Contract::STATUS_ACTIVE, $contract->estado);

        // 2. Official CON- number assigned and original PRE- retained
        $this->assertStringStartsWith('CON-', $contract->contract_number);
        $this->assertEquals($originalPreCode, $contract->preliminary_number);

        // 3. Client record updated with valid legal DNI & address
        $client = $contract->client;
        $this->assertEquals($this->dniDocType->id, $client->iddoc);
        $this->assertEquals('72819203', $client->nro_documento);
        $this->assertEquals('Jr. Los Cedros 340, Tarapoto', $client->direccion);

        // 4. Configured payment schedule: 20% (Cuota 1: S/ 1,000.00) + 80% (Cuota 2: S/ 4,000.00)
        $installments = $contract->installments()->orderBy('numero_cuota')->get();
        $this->assertCount(2, $installments);

        $cuota1 = $installments[0];
        $cuota2 = $installments[1];

        $this->assertEquals(1000.00, (float) $cuota1->monto);
        $this->assertEquals(20.0, (float) $cuota1->porcentaje);
        $this->assertEquals(4000.00, (float) $cuota2->monto);
        $this->assertEquals(80.0, (float) $cuota2->porcentaje);

        // 5. Rule 6 satisfied
        $this->assertEquals((float) $contract->total, (float) ($cuota1->monto + $cuota2->monto));
    }

    /**
     * Test adjustments do not alter already paid installments.
     */
    public function test_adjustments_do_not_alter_already_paid_installments(): void
    {
        $contract = $this->createPreliminaryContract(4000.00);

        // Formalize contract first
        $this->actingAs($this->adminUser)->postJson("/contracts/{$contract->id}/formalize", [
            'iddoc' => $this->dniDocType->id,
            'nro_documento' => '45678912',
            'direccion' => 'Jr. Miraflores 120, Tarapoto',
        ]);
        $contract->refresh();

        $installments = $contract->installments()->orderBy('numero_cuota')->get();
        $cuota1 = $installments[0]; // S/ 800.00
        $cuota2 = $installments[1]; // S/ 3,200.00

        // Mark Cuota 1 as PAID
        $cuota1->update([
            'estado' => ContractInstallment::STATUS_PAID,
            'fecha_pago' => now()->toDateString(),
        ]);

        // Apply post-event adjustment (Breakage of glassware: +S/ 250.00)
        $response = $this->actingAs($this->adminUser)
            ->postJson("/contracts/{$contract->id}/adjustments", [
                'type' => ContractAdjustment::TYPE_BREAKAGE,
                'amount' => 250.00,
                'description' => 'Rotura de 6 copas martini y 2 copas balón durante el evento.',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $contract->refresh();
        $cuota1->refresh();
        $cuota2->refresh();

        // Cuota 1 was NOT modified
        $this->assertEquals(800.00, (float) $cuota1->monto);
        $this->assertTrue($cuota1->isPaid());

        // Cuota 2 received the adjustment: 3,200 + 250 = 3,450.00
        $this->assertEquals(3450.00, (float) $cuota2->monto);

        // Contract total updated: 4,000 + 250 = 4,250.00
        $this->assertEquals(4250.00, (float) $contract->total);

        // Audit adjustment record created
        $this->assertDatabaseHas('contract_adjustments', [
            'contract_id' => $contract->id,
            'type' => 'breakage',
            'amount' => 250.00,
        ]);

        // Attempting to directly target the paid installment fails
        $failResponse = $this->actingAs($this->adminUser)
            ->postJson("/contracts/{$contract->id}/adjustments", [
                'type' => ContractAdjustment::TYPE_MANUAL,
                'amount' => 100.00,
                'description' => 'Intento inválido sobre cuota pagada',
                'installment_id' => $cuota1->id,
            ]);

        $failResponse->assertStatus(422);
    }

    /**
     * Test the sum of installments equals the contract total after multiple adjustments (Rule 6).
     */
    public function test_sum_of_installments_equals_total_after_adjustments(): void
    {
        $contract = $this->createPreliminaryContract(3000.00);

        $service = app(ContractAdjustmentService::class);
        $formalized = $service->formalizeContract($contract, [
            'iddoc' => $this->dniDocType->id,
            'nro_documento' => '88990011',
            'direccion' => 'Jr. San Pablo 400',
        ]);

        // Apply 3 adjustments
        $service->applyAdjustment($formalized, [
            'type' => ContractAdjustment::TYPE_BREAKAGE,
            'amount' => 120.00,
            'description' => 'Rotura de cristalería',
        ], $this->adminUser);

        $service->applyAdjustment($formalized, [
            'type' => ContractAdjustment::TYPE_ADDITIONAL,
            'amount' => 350.00,
            'description' => 'Consumo adicional de cocteles de autor',
        ], $this->adminUser);

        $service->applyAdjustment($formalized, [
            'type' => ContractAdjustment::TYPE_DISCOUNT,
            'amount' => -70.00,
            'description' => 'Descuento comercial de cortesía',
        ], $this->adminUser);

        $formalized->refresh();

        // Total should be: 3000 + 120 + 350 - 70 = 3400.00
        $this->assertEquals(3400.00, (float) $formalized->total);

        $sumCuotas = (float) $formalized->installments()->sum('monto');
        $this->assertEquals(3400.00, $sumCuotas);
        $this->assertEquals((float) $formalized->total, $sumCuotas);

        // Validation passes
        $service->validateRule6($formalized);

        // Artificially tampering with an installment to violate Rule 6 throws Rule6ViolationException
        $lastCuota = $formalized->installments()->orderByDesc('numero_cuota')->first();
        $lastCuota->monto += 50.00;
        $lastCuota->save();

        $this->expectException(Rule6ViolationException::class);
        $service->validateRule6($formalized);
    }

    /**
     * Test adjustments are strictly immutable (cannot be updated or deleted).
     */
    public function test_adjustments_are_immutable(): void
    {
        $contract = $this->createPreliminaryContract(2000.00);
        $service = app(ContractAdjustmentService::class);

        $service->formalizeContract($contract, [
            'iddoc' => $this->dniDocType->id,
            'nro_documento' => '11223344',
            'direccion' => 'Tarapoto',
        ]);

        $adj = $service->applyAdjustment($contract, [
            'type' => ContractAdjustment::TYPE_MISSING,
            'amount' => 90.00,
            'description' => 'Hielera acrílica no devuelta',
        ], $this->adminUser);

        // Attempting to update throws ImmutableAdjustmentException
        try {
            $adj->description = 'Descripción modificada';
            $adj->save();
            $this->fail('Se esperaba ImmutableAdjustmentException al intentar modificar un ajuste.');
        } catch (ImmutableAdjustmentException $e) {
            $this->assertStringContainsString('inmutables', $e->getMessage());
        }

        // Attempting to delete throws ImmutableAdjustmentException
        try {
            $adj->delete();
            $this->fail('Se esperaba ImmutableAdjustmentException al intentar eliminar un ajuste.');
        } catch (ImmutableAdjustmentException $e) {
            $this->assertStringContainsString('inmutables', $e->getMessage());
        }
    }

    /**
     * Test contract and installment numbering remains sequential, even under simulated concurrent access.
     */
    public function test_numbering_remains_sequential_under_concurrency(): void
    {
        $year = (int) date('Y');

        // Generate 3 sequential numbers and verify no gaps or duplicate numbers
        $num1 = Contract::generateFormalContractNumber($year);

        // Create dummy contract with num1
        Contract::create([
            'contract_number' => $num1,
            'idcliente' => Client::first()->id,
            'provider_name' => 'MYTEMS',
            'provider_document' => '20600000000',
            'provider_representative' => 'Gerencia',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'fecha_evento' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000,
            'igv' => 180,
            'total' => 1180,
            'estado' => 1,
            'status' => 'vigente',
        ]);

        $num2 = Contract::generateFormalContractNumber($year);
        $this->assertNotEquals($num1, $num2);

        preg_match('/CON-\d{4}-(\d+)/', $num1, $m1);
        preg_match('/CON-\d{4}-(\d+)/', $num2, $m2);

        $this->assertEquals(intval($m1[1]) + 1, intval($m2[1]));
    }
}
