<?php

namespace Tests\Feature;

use App\Models\ArchingCash;
use App\Models\Cash;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\DetailPayment;
use App\Models\Expense;
use App\Models\IdentityDocumentType;
use App\Models\PayMode;
use App\Models\SaleNote;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Ebilling\ContractEbillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RevenueReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Cash $cash;

    protected Warehouse $warehouse;

    protected PayMode $cashPayMode;

    protected PayMode $yapePayMode;

    protected IdentityDocumentType $dniDocType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?: User::factory()->create();
        $this->actingAs($this->user);

        $this->cash = Cash::first() ?: Cash::create(['descripcion' => 'Caja Principal 01', 'estado' => 1]);
        $this->warehouse = Warehouse::first() ?: Warehouse::create(['descripcion' => 'Almacén Central', 'estado' => 1]);

        $this->user->idcaja = $this->cash->id;
        $this->user->idalmacen = $this->warehouse->id;
        $this->user->save();

        $this->cashPayMode = PayMode::firstOrCreate(['id' => 1], ['descripcion' => 'Efectivo', 'estado' => 1]);
        $this->yapePayMode = PayMode::firstOrCreate(['id' => 2], ['descripcion' => 'Yape', 'estado' => 1]);
        $this->dniDocType = IdentityDocumentType::where('codigo', '1')->first()
            ?: IdentityDocumentType::create(['codigo' => '1', 'descripcion' => 'DNI']);
    }

    protected function createOpenArching(float $initial = 200.00): ArchingCash
    {
        // Close any lingering open arching for this user/caja to guarantee clean test state
        ArchingCash::where('idusuario', $this->user->id)->where('estado', 1)->update(['estado' => 2]);
        ArchingCash::where('idcaja', $this->cash->id)->where('estado', 1)->update(['estado' => 2]);

        return ArchingCash::create([
            'idcaja' => $this->cash->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => Carbon::today()->toDateString(),
            'hora_inicio' => date('H:i:s'),
            'monto_inicial' => $initial,
            'estado' => 1,
            'total_ventas' => 0,
        ]);
    }

    protected function createTestContract(float $total = 2000.00): Contract
    {
        $client = Client::create([
            'iddoc' => $this->dniDocType->id,
            'nro_documento' => '45892147',
            'nombres' => 'MARIA DEL CARMEN RIOS',
            'direccion' => 'AV. MANCO CAPAC 450, LIMA',
            'estado' => 1,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CON-'.date('Y').'-'.rand(1000, 9999),
            'idcliente' => $client->id,
            'provider_name' => 'SHIPIBO EVENTOS',
            'provider_document' => '20600000001',
            'fecha_emision' => Carbon::today()->toDateString(),
            'fecha_vencimiento' => Carbon::today()->addDays(30)->toDateString(),
            'fecha_evento' => Carbon::today()->addDays(15)->toDateString(),
            'subtotal' => round($total / 1.18, 2),
            'igv' => round($total - ($total / 1.18), 2),
            'total' => $total,
            'estado' => Contract::STATUS_SIGNED,
            'status' => Contract::STATUS_STR_VIGENTE,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        // Installment 1: S/ 500.00 (25%)
        ContractInstallment::create([
            'contract_id' => $contract->id,
            'numero_cuota' => 1,
            'descripcion' => 'Cuota Inicial / Reserva',
            'monto' => 500.00,
            'porcentaje' => 25.00,
            'fecha_vencimiento' => Carbon::today()->toDateString(),
            'estado' => ContractInstallment::STATUS_PENDING,
        ]);

        // Installment 2: S/ 1500.00 (75%)
        ContractInstallment::create([
            'contract_id' => $contract->id,
            'numero_cuota' => 2,
            'descripcion' => 'Saldo Final',
            'monto' => 1500.00,
            'porcentaje' => 75.00,
            'fecha_vencimiento' => Carbon::today()->addDays(15)->toDateString(),
            'estado' => ContractInstallment::STATUS_PENDING,
        ]);

        return $contract;
    }

    /**
     * 1. Single Cash Session Rule: Only one cash session open per user/caja.
     */
    public function test_cannot_open_multiple_cash_sessions_simultaneously(): void
    {
        $this->createOpenArching(150.00);

        // Attempt to open another arching cash session for the same cash drawer
        $response = $this->postJson(route('arching_cash.save'), [
            'idcaja' => $this->cash->id,
            'monto_inicial' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
            ]);

        $this->assertStringContainsString('ya cuenta con una sesión de arqueo abierta', (string) $response->json('msg'));
    }

    /**
     * 2. Installment payment recording in active cash register and detail_payments.
     */
    public function test_installment_payment_is_recorded_in_cash_register_and_detail_payments(): void
    {
        $arching = $this->createOpenArching(200.00);
        $contract = $this->createTestContract(2000.00);
        $installment = $contract->installments()->first();

        $response = $this->postJson(route('admin.contracts.pay_installment', ['id' => $installment->id]), [
            'fecha_pago' => Carbon::today()->toDateString(),
            'metodo_pago' => 'Efectivo',
            'idpago' => $this->cashPayMode->id,
            'referencia_pago' => 'REC-001',
            'observaciones' => 'Pago en efectivo de cuota 1 en mostrador',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        // Verify installment updated
        $installment->refresh();
        $this->assertEquals(ContractInstallment::STATUS_PAID, $installment->estado);
        $this->assertEquals($arching->id, $installment->idarqueocaja);
        $this->assertEquals("INST-{$installment->id}", $installment->reconciliation_key);
        $this->assertEquals($this->cashPayMode->id, $installment->idpago);

        // Verify detail_payments entry
        $detailPayment = DetailPayment::where('installment_id', $installment->id)->first();
        $this->assertNotNull($detailPayment, 'A detail_payment record must be generated for installment collections.');
        $this->assertEquals($arching->id, $detailPayment->idarqueocaja);
        $this->assertEquals("INST-{$installment->id}", $detailPayment->reconciliation_key);
        $this->assertEquals(500.00, (float) $detailPayment->monto);
        $this->assertEquals(1, $detailPayment->estado);
    }

    /**
     * 3. End-to-End Double-Counting Prevention Rule:
     * When an installment is paid in cash and subsequently invoiced electronically (B5),
     * it must be recognized ONLY ONCE in the daily revenue summary and reconciliation report.
     */
    public function test_installment_paid_and_subsequently_invoiced_is_counted_only_once(): void
    {
        $arching = $this->createOpenArching(100.00);
        $contract = $this->createTestContract(1000.00);
        $installment = $contract->installments()->first(); // S/ 500.00

        // Step 1: Pay the installment in cash register
        $this->postJson(route('admin.contracts.pay_installment', ['id' => $installment->id]), [
            'fecha_pago' => Carbon::today()->toDateString(),
            'idpago' => $this->cashPayMode->id,
            'metodo_pago' => 'Efectivo',
        ])->assertStatus(200);

        $installment->refresh();
        $this->assertEquals("INST-{$installment->id}", $installment->reconciliation_key);

        // Step 2: Invoice the installment electronically (B5 / Boleta)
        /** @var ContractEbillingService $ebillingService */
        $ebillingService = app(ContractEbillingService::class);
        $invoiceResult = $ebillingService->invoiceInstallment($installment, [
            'document_type_code' => '03',
            'serie' => 'B001',
            'fecha_emision' => Carbon::today()->toDateString(),
        ]);

        $billing = $invoiceResult['billing'];
        $this->assertNotNull($billing);
        $this->assertEquals("INST-{$installment->id}", $billing->reconciliation_key);
        $this->assertEquals(500.00, (float) $billing->total);

        // Step 3: Call Daily Closing Summary
        $response = $this->get(route('admin.daily_closing'));
        $response->assertStatus(200);

        $summary = $response->viewData('summary');

        // Total invoiced installments must reflect S/ 500.00
        $this->assertEquals(500.00, $summary['installments_invoiced_total']);
        // Unbilled installments must be 0.00 because it has been invoiced!
        $this->assertEquals(0.00, $summary['installments_unbilled_total']);

        // Recognized sales paid total must equal 500.00 (from billing), NOT 1000.00 (double counting prevented!)
        $this->assertEquals(500.00, $summary['sales_paid_total']);

        // Step 4: Verify sales_data endpoint returns the invoice and does not duplicate with the installment
        $salesDataResponse = $this->getJson(route('admin.daily_closing.sales_data'));
        $salesDataResponse->assertStatus(200);
        $rows = $salesDataResponse->json('data') ?? [];

        $matchingOriginTypes = collect($rows)->pluck('origin_type')->all();
        // Since it was invoiced, it appears as 'billing' and not duplicated as 'installment'
        $this->assertContains('billing', $matchingOriginTypes);
        $this->assertNotContains('installment', $matchingOriginTypes);
    }

    /**
     * 4. Daily Closing balancing formula verification:
     * Expected = Initial Balance + Cash + Deposits − Withdrawals − Expenses
     */
    public function test_daily_closing_report_balances_formula(): void
    {
        $arching = $this->createOpenArching(200.00); // Initial: 200.00

        // 1. POS Sale Note in cash: S/ 100.00
        SaleNote::create([
            'idtipo_comprobante' => 6,
            'serie' => 'NV01',
            'correlativo' => (string) rand(10000, 99999),
            'fecha_emision' => Carbon::today()->toDateString(),
            'fecha_vencimiento' => Carbon::today()->toDateString(),
            'hora' => '10:00:00',
            'idcliente' => 1,
            'idusuario' => $this->user->id,
            'idmoneda' => 1,
            'modo_pago' => 1,
            'subtotal' => 100.00,
            'igv' => 0.00,
            'total' => 100.00,
            'estado' => 1,
            'idarqueocaja' => $arching->id,
            'payment_breakdown' => json_encode([['method' => 'Efectivo', 'amount' => 100.00]]),
        ]);

        // 2. Contract Installment in cash: S/ 300.00
        $contract = $this->createTestContract(1000.00);
        $installment = $contract->installments()->first();
        $installment->monto = 300.00;
        $installment->save();

        $this->postJson(route('admin.contracts.pay_installment', ['id' => $installment->id]), [
            'fecha_pago' => Carbon::today()->toDateString(),
            'idpago' => $this->cashPayMode->id,
            'metodo_pago' => 'Efectivo',
        ])->assertStatus(200);

        // 3. Cash Deposit: S/ 50.00
        $this->postJson(route('admin.save_deposit'), [
            'id' => $arching->id,
            'monto' => 50.00,
            'motivo' => 'Inyección para cambio / sencillo',
        ])->assertStatus(200);

        // 4. Cash Withdrawal: S/ 30.00
        $this->postJson(route('admin.save_withdrawal'), [
            'id' => $arching->id,
            'monto' => 30.00,
            'motivo' => 'Retiro de seguridad a caja fuerte',
        ])->assertStatus(200);

        // 5. Cash Expense: S/ 20.00
        Expense::create([
            'idarqueocaja' => $arching->id,
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehouse->id,
            'idusuario' => $this->user->id,
            'fecha' => Carbon::today()->toDateString(),
            'hora' => '14:00:00',
            'tipo_comprobante' => 'RECIBO',
            'motivo' => 'Compra de hielo y servilletas',
            'monto' => 20.00,
            'metodo_pago' => 'Efectivo',
            'estado' => 1,
        ]);

        // Expected = Initial (200) + Cash (100 + 300 = 400) + Deposits (50) - Withdrawals (30) - Expenses (20)
        // Expected = 200 + 400 + 50 - 30 - 20 = 600.00
        $response = $this->get(route('admin.daily_closing'));
        $response->assertStatus(200);
        $summary = $response->viewData('summary');

        $this->assertEquals(200.00, $summary['opening_amount']);
        $this->assertEquals(50.00, $summary['deposits_total']);
        $this->assertEquals(30.00, $summary['withdrawals_total']);
        $this->assertEquals(20.00, $summary['expenses_cash_total']);
        $this->assertEquals(600.00, $summary['expected_cash_in_box']);

        // Check installment breakdown by payment method
        $this->assertArrayHasKey('installment_payment_methods', $summary);
        $this->assertEquals(300.00, $summary['installment_payment_methods']['Efectivo']['total']);
    }

    /**
     * 5. Reconciliation Artisan Command: `reports:reconcile {date}`
     */
    public function test_reports_reconcile_command_runs_cleanly(): void
    {
        $today = Carbon::today()->toDateString();

        $this->artisan('reports:reconcile', ['date' => $today])
            ->assertExitCode(0);
    }

    /**
     * 6. New Contract Reports endpoints return 200 with proper JSON structures.
     */
    public function test_new_contract_reports_endpoints_return_successful_data(): void
    {
        $this->getJson(route('admin.reports.contracts.status_data'))->assertStatus(200);
        $this->getJson(route('admin.reports.contracts.installments_due_data'))->assertStatus(200);
        $this->getJson(route('admin.reports.contracts.guarantees_data'))->assertStatus(200);
        $this->getJson(route('admin.reports.contracts.adjustments_data'))->assertStatus(200);
        $this->getJson(route('admin.reports.contracts.revenue_payment_data'))->assertStatus(200);
        $this->getJson(route('admin.reports.contracts.preliminaries_data'))->assertStatus(200);
    }

    /**
     * 7. Contract Reports endpoints require admin.reports.contracts permission.
     */
    public function test_contract_reports_endpoints_require_permission(): void
    {
        $unauthorizedUser = User::create([
            'nombres' => 'Usuario No Autorizado',
            'user' => 'noauth_'.\Illuminate\Support\Str::random(6),
            'password' => bcrypt('password123'),
            'estado' => 1,
            'idcaja' => 1,
            'idalmacen' => 1,
        ]);

        $this->actingAs($unauthorizedUser);
        $this->get(route('admin.reports.contracts.index'))->assertStatus(403);
        $this->getJson(route('admin.reports.contracts.status_data'))->assertStatus(403);
    }
}
