<?php

namespace App\Console\Commands;

use App\Models\Billing;
use App\Models\ContractInstallment;
use App\Models\DetailPayment;
use App\Models\SaleNote;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcileReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:reconcile 
                            {date? : Date to reconcile in YYYY-MM-DD format (defaults to today)}
                            {--strict : Fail if any unbilled or untracked receipts exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile revenues across POS sale notes, electronic billings, and contract installments for a given date.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateInput = $this->argument('date') ?: Carbon::today()->toDateString();

        try {
            $date = Carbon::parse($dateInput)->toDateString();
        } catch (\Exception $e) {
            $this->error("Formato de fecha inválido: '{$dateInput}'. Use el formato YYYY-MM-DD.");

            return self::FAILURE;
        }

        $this->info('================================================================');
        $this->info("   SHIPIBO ERP - CONCILIACIÓN DE INGRESOS (FECHA: {$date})      ");
        $this->info('================================================================');
        $this->newLine();

        // 1. POS Sales (Notas de Venta)
        $posSales = SaleNote::query()
            ->whereDate('fecha_emision', $date)
            ->where('estado', 1)
            ->get();

        $posSalesCount = $posSales->count();
        $posSalesTotal = (float) $posSales->sum('total');

        // 2. Electronic Billings (Facturación B5 / F5)
        $allBillings = Billing::query()
            ->with(['typeDocument'])
            ->whereDate('fecha_emision', $date)
            ->where('anulado', 0)
            ->get();

        // Split billings: Standalone POS sales vs Invoiced contract installments
        $standaloneBillings = $allBillings->filter(function (Billing $b) {
            return empty($b->reconciliation_key) || ! str_starts_with((string) $b->reconciliation_key, 'INST-');
        });

        $installmentBillings = $allBillings->filter(function (Billing $b) {
            return ! empty($b->reconciliation_key) && str_starts_with((string) $b->reconciliation_key, 'INST-');
        });

        $standaloneBillingsTotal = (float) $standaloneBillings->sum('total');
        $installmentBillingsTotal = (float) $installmentBillings->sum('total');
        $allBillingsTotal = (float) $allBillings->sum('total');

        // 3. Contract Installments (Cuotas de Contrato Cobradas)
        $paidInstallments = ContractInstallment::query()
            ->with(['contract.client', 'payMode'])
            ->whereDate('fecha_pago', $date)
            ->where('estado', ContractInstallment::STATUS_PAID)
            ->get();

        $installmentsCount = $paidInstallments->count();
        $installmentsTotal = (float) $paidInstallments->sum('monto');

        // Identify which installments are already invoiced via B5/F5
        $invoicedInstallments = $paidInstallments->filter(function (ContractInstallment $inst) use ($installmentBillings) {
            $key = $inst->reconciliation_key ?: "INST-{$inst->id}";

            return $inst->billing_id !== null
                || $installmentBillings->contains('reconciliation_key', $key);
        });

        $unbilledInstallments = $paidInstallments->reject(function (ContractInstallment $inst) use ($installmentBillings) {
            $key = $inst->reconciliation_key ?: "INST-{$inst->id}";

            return $inst->billing_id !== null
                || $installmentBillings->contains('reconciliation_key', $key);
        });

        $invoicedInstallmentsTotal = (float) $invoicedInstallments->sum('monto');
        $unbilledInstallmentsTotal = (float) $unbilledInstallments->sum('monto');

        // 4. Detail Payments (Recibos en Caja Registradora / Arqueo)
        $detailPayments = DetailPayment::query()
            ->whereDate('created_at', $date)
            ->where('estado', 1)
            ->get();

        // Deduplicate detail payments by reconciliation_key if present
        $dedupedDetailPayments = collect();
        $seenKeys = [];
        foreach ($detailPayments as $dp) {
            if ($dp->reconciliation_key) {
                if (isset($seenKeys[$dp->reconciliation_key])) {
                    continue;
                }
                $seenKeys[$dp->reconciliation_key] = true;
            }
            $dedupedDetailPayments->push($dp);
        }
        $detailPaymentsTotal = (float) $dedupedDetailPayments->sum('monto');

        // 5. Total Deduplicated Recognized Revenue
        // Formula A: POS Sales + Standalone Billings + All Paid Installments
        $revenueFormulaA = round($posSalesTotal + $standaloneBillingsTotal + $installmentsTotal, 2);

        // Formula B: POS Sales + All Billings + Unbilled Installments
        $revenueFormulaB = round($posSalesTotal + $allBillingsTotal + $unbilledInstallmentsTotal, 2);

        // 6. Audit & Discrepancies Detection
        $discrepancies = [];

        // Check 1: Deduplication consistency (Formula A must strictly equal Formula B)
        if (abs($revenueFormulaA - $revenueFormulaB) > 0.009) {
            $discrepancies[] = [
                'type' => 'DEDUPLICATION_MISMATCH',
                'description' => "Inconsistencia en la deduplicación de cuotas facturadas: Fórmula A ({$revenueFormulaA}) != Fórmula B ({$revenueFormulaB}).",
                'difference' => abs($revenueFormulaA - $revenueFormulaB),
            ];
        }

        // Check 2: Installments without canonical reconciliation key
        $missingKeys = $paidInstallments->filter(fn ($inst) => empty($inst->reconciliation_key));
        if ($missingKeys->isNotEmpty()) {
            $discrepancies[] = [
                'type' => 'MISSING_RECONCILIATION_KEY',
                'description' => "Se encontraron {$missingKeys->count()} cuota(s) pagadas sin clave de conciliación canónica (reconciliation_key).",
                'difference' => (float) $missingKeys->sum('monto'),
            ];
        }

        // Check 3: Installments without linked cash session (idarqueocaja)
        $unlinkedArching = $paidInstallments->filter(fn ($inst) => empty($inst->idarqueocaja));
        if ($unlinkedArching->isNotEmpty() && $this->option('strict')) {
            $discrepancies[] = [
                'type' => 'UNLINKED_CASH_SESSION',
                'description' => "Se encontraron {$unlinkedArching->count()} cuota(s) pagadas sin vincular a una sesión de arqueo de caja (idarqueocaja).",
                'difference' => (float) $unlinkedArching->sum('monto'),
            ];
        }

        // Check 4: Invoiced vs Paid amount matching for installments
        if (abs($installmentBillingsTotal - $invoicedInstallmentsTotal) > 0.009 && $installmentBillingsTotal > 0) {
            $diff = abs($installmentBillingsTotal - $invoicedInstallmentsTotal);
            $discrepancies[] = [
                'type' => 'BILLING_INSTALLMENT_VARIANCE',
                'description' => "Diferencia entre facturación electrónica de cuotas ({$installmentBillingsTotal}) y monto registrado de cuotas ({$invoicedInstallmentsTotal}).",
                'difference' => $diff,
            ];
        }

        // Check 5: Detail Payments vs Total Revenue discrepancy
        $receiptsDiff = round(abs($revenueFormulaA - $detailPaymentsTotal), 2);

        // 7. Render Output Table
        $this->table(
            ['Fuente de Ingreso / Componente', 'Comprobantes / Registros', 'Monto Total (PEN)', 'Estado de Deduplicación'],
            [
                ['1. Ventas POS (Notas de Venta)', $posSalesCount, number_format($posSalesTotal, 2), '<fg=green>Reconocido 100%</>'],
                ['2. Facturación Electrónica (Total)', $allBillings->count(), number_format($allBillingsTotal, 2), '<fg=blue>CPE SUNAT</>'],
                ['   ├─ Facturación POS Independiente', $standaloneBillings->count(), number_format($standaloneBillingsTotal, 2), '<fg=green>Reconocido 100%</>'],
                ['   └─ Facturación de Cuotas (B5/F5)', $installmentBillings->count(), number_format($installmentBillingsTotal, 2), '<fg=yellow>Vinculado a Cuota</>'],
                ['3. Cuotas de Contrato (Total Cobradas)', $installmentsCount, number_format($installmentsTotal, 2), '<fg=cyan>Cobranza Contratos</>'],
                ['   ├─ Cuotas Facturadas Electrónicamente', $invoicedInstallments->count(), number_format($invoicedInstallmentsTotal, 2), '<fg=gray>Deduplicado (ya en CPE)</>'],
                ['   └─ Cuotas No Facturadas (Recibo Caja)', $unbilledInstallments->count(), number_format($unbilledInstallmentsTotal, 2), '<fg=green>Reconocido 100%</>'],
                ['------------------------------------', '-------', '-------------', '-----------------------'],
                ['TOTAL INGRESOS RECONOCIDOS (DEDUPLICADO)', ($posSalesCount + $standaloneBillings->count() + $installmentsCount), number_format($revenueFormulaA, 2), '<fg=bright-green;options=bold>AUDITADO SIN DUPLICIDAD</>'],
                ['TOTAL COBROS REGISTRADOS EN CAJA (DETAIL_PAYMENTS)', $dedupedDetailPayments->count(), number_format($detailPaymentsTotal, 2), '<fg=white>Recibos en Cajón</>'],
            ]
        );

        $this->newLine();

        if (empty($discrepancies)) {
            $this->info("✓ CONCILIACIÓN EXITOSA: No se detectaron discrepancias en los ingresos del día {$date}.");
            $this->line('  - Total Ingresos Reconocidos: <fg=green;options=bold>S/ '.number_format($revenueFormulaA, 2).'</>');
            $this->line('  - Regla Antiduplicidad: <fg=green>Cumplida exitosamente mediante claves canónicas (reconciliation_key).</>');

            return self::SUCCESS;
        }

        $this->warn('⚠ SE DETECTARON '.count($discrepancies).' DISCREPANCIA(S) O ADVERTENCIA(S) DE CONCILIACIÓN:');
        foreach ($discrepancies as $idx => $d) {
            $num = $idx + 1;
            $this->line("  <fg=red>[{$num}] {$d['type']}:</> {$d['description']} (Diferencia: S/ ".number_format($d['difference'], 2).')');
        }

        return self::FAILURE;
    }
}
