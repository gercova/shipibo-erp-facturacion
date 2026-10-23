<?php

namespace App\Services\Contracts;

use App\Exceptions\Rule6ViolationException;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractInstallment;
use App\Models\IdentityDocumentType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class ContractAdjustmentService
{
    /**
     * Formalize a preliminary contract or draft contract into an active contract (CON-YYYY-XXXX).
     *
     * Validates client identification (DNI: 8 digits, RUC: 11 digits) and address,
     * assigns sequential formal contract number with lockForUpdate,
     * updates status to 'vigente', retains original PRE- code,
     * and sets up the default configurable 20% advance / 80% balance schedule.
     */
    public function formalizeContract(Contract $contract, array $clientData, ?array $installmentsData = null): Contract
    {
        $this->validateClientDataForFormalization($clientData);

        return DB::transaction(function () use ($contract, $clientData, $installmentsData) {
            // Lock contract row to prevent concurrent modification
            $lockedContract = Contract::where('id', $contract->id)->lockForUpdate()->firstOrFail();

            // 1. Update Client Record with required legal ID and address
            $client = Client::where('id', $lockedContract->idcliente)->lockForUpdate()->firstOrFail();
            $client->iddoc = (int) $clientData['iddoc'];
            $client->nro_documento = trim((string) $clientData['nro_documento']);
            $client->direccion = trim((string) $clientData['direccion']);

            if (! empty($clientData['nombres'])) {
                $client->nombres = mb_strtoupper(trim((string) $clientData['nombres']));
            }
            if (! empty($clientData['telefono'])) {
                $client->telefono = trim((string) $clientData['telefono']);
            }
            if (! empty($clientData['email'])) {
                $client->email = trim((string) $clientData['email']);
            }
            $client->save();

            // 2. Retain original PRE- code if applicable
            $currentNumber = (string) $lockedContract->contract_number;
            if (str_starts_with($currentNumber, 'PRE-') && empty($lockedContract->preliminary_number)) {
                $lockedContract->preliminary_number = $currentNumber;
            }

            // 3. Generate sequential CON-YYYY-XXXX number with lockForUpdate
            $newContractNumber = Contract::generateFormalContractNumber();

            $lockedContract->contract_number = $newContractNumber;
            $lockedContract->status = Contract::STATUS_STR_VIGENTE;
            $lockedContract->estado = Contract::STATUS_ACTIVE;
            $lockedContract->title = 'CONTRATO DE PRESTACIÓN DE SERVICIOS - '.$client->nombres;

            $obsNote = "Formalizado a Contrato Oficial {$newContractNumber}";
            if (! empty($lockedContract->preliminary_number)) {
                $obsNote .= " (código previo: {$lockedContract->preliminary_number})";
            }
            if (! empty($lockedContract->tracking_code)) {
                $obsNote .= " [Tracking: {$lockedContract->tracking_code}]";
            }
            $lockedContract->observaciones = trim($lockedContract->observaciones.' | '.$obsNote, ' | ');
            $lockedContract->save();

            // 4. Generate or synchronize payment schedule (Cuotas)
            $this->syncPaymentSchedule($lockedContract, $installmentsData);

            // 5. Validate Rule 6 of PDR: sum of installments = total contract value
            $this->validateRule6($lockedContract);

            return $lockedContract->fresh(['client', 'installments', 'adjustments']);
        });
    }

    /**
     * Apply a post-event adjustment (breakage, missing item, additional consumption, discount, manual).
     *
     * A positive adjustment increases the contract total and is added to the specified
     * unpaid installment (or defaults to the last unpaid installment) without altering
     * installments that have already been paid. Rule 6 is strictly validated.
     */
    public function applyAdjustment(Contract $contract, array $data, ?User $user = null): ContractAdjustment
    {
        $amount = round(floatval($data['amount'] ?? 0), 2);
        if ($amount == 0.0) {
            throw new InvalidArgumentException('El monto del ajuste no puede ser cero.');
        }

        $type = (string) ($data['type'] ?? ContractAdjustment::TYPE_MANUAL);
        $validTypes = [
            ContractAdjustment::TYPE_BREAKAGE,
            ContractAdjustment::TYPE_MISSING,
            ContractAdjustment::TYPE_ADDITIONAL,
            ContractAdjustment::TYPE_DISCOUNT,
            ContractAdjustment::TYPE_MANUAL,
        ];

        if (! in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Tipo de ajuste inválido: {$type}.");
        }

        $description = trim((string) ($data['description'] ?? ''));
        if (empty($description)) {
            throw new InvalidArgumentException('La descripción del ajuste es obligatoria.');
        }

        return DB::transaction(function () use ($contract, $amount, $type, $description, $data, $user) {
            // Lock contract
            $lockedContract = Contract::where('id', $contract->id)->lockForUpdate()->firstOrFail();

            // Find target installment
            $targetInstallment = null;
            if (! empty($data['installment_id'])) {
                $targetInstallment = ContractInstallment::where('id', $data['installment_id'])
                    ->where('contract_id', $lockedContract->id)
                    ->lockForUpdate()
                    ->first();

                if (! $targetInstallment) {
                    throw new InvalidArgumentException('La cuota especificada no pertenece a este contrato.');
                }

                if ($targetInstallment->isPaid()) {
                    throw new InvalidArgumentException('No se puede aplicar un ajuste a una cuota que ya ha sido pagada.');
                }
            } else {
                // Default to the last unpaid installment
                $targetInstallment = ContractInstallment::where('contract_id', $lockedContract->id)
                    ->where('estado', ContractInstallment::STATUS_PENDING)
                    ->orderBy('numero_cuota', 'desc')
                    ->lockForUpdate()
                    ->first();

                if (! $targetInstallment) {
                    throw new RuntimeException('No existen cuotas pendientes en el contrato para aplicar el ajuste.');
                }
            }

            // Calculate new installment amount
            $newInstallmentAmount = round($targetInstallment->monto + $amount, 2);
            if ($newInstallmentAmount < 0) {
                throw new InvalidArgumentException('El monto del ajuste excede el saldo restante de la cuota seleccionada.');
            }

            // 1. Update target installment amount
            $targetInstallment->monto = $newInstallmentAmount;
            $targetInstallment->save();

            // 2. Recalculate contract total
            $newContractTotal = round($lockedContract->total + $amount, 2);
            $lockedContract->total = $newContractTotal;
            $lockedContract->subtotal = round($newContractTotal / 1.18, 2);
            $lockedContract->igv = round($newContractTotal - $lockedContract->subtotal, 2);
            $lockedContract->save();

            // 3. Recalculate percentages of all installments based on new total
            $allInstallments = ContractInstallment::where('contract_id', $lockedContract->id)->get();
            foreach ($allInstallments as $inst) {
                $inst->porcentaje = $newContractTotal > 0 ? round(($inst->monto / $newContractTotal) * 100, 2) : 0;
                $inst->save();
            }

            // 4. Validate Rule 6 of PDR
            $this->validateRule6($lockedContract);

            // 5. Create immutable adjustment record
            $adjustment = ContractAdjustment::create([
                'contract_id' => $lockedContract->id,
                'installment_id' => $targetInstallment->id,
                'type' => $type,
                'description' => $description,
                'amount' => $amount,
                'product_id' => ! empty($data['product_id']) ? (int) $data['product_id'] : null,
                'checklist_item_id' => ! empty($data['checklist_item_id']) ? (int) $data['checklist_item_id'] : null,
                'user_id' => $user?->id ?: Auth::id(),
                'created_at' => now(),
            ]);

            return $adjustment;
        });
    }

    /**
     * Validate client data for formalization according to Peruvian tax/identity standards.
     * DNI must be 8 digits; RUC must be 11 digits. Address is mandatory.
     */
    protected function validateClientDataForFormalization(array $clientData): void
    {
        $docTypeId = (int) ($clientData['iddoc'] ?? 0);
        $docType = IdentityDocumentType::find($docTypeId);

        if (! $docType) {
            throw ValidationException::withMessages([
                'iddoc' => ['Debe seleccionar un tipo de documento válido.'],
            ]);
        }

        $docNumber = trim((string) ($clientData['nro_documento'] ?? ''));
        if (empty($docNumber)) {
            throw ValidationException::withMessages([
                'nro_documento' => ['El número de documento de identidad es obligatorio para formalizar el contrato.'],
            ]);
        }

        // Validate DNI (codigo '1'): exactly 8 digits
        if ($docType->codigo === '1') {
            if (! preg_match('/^\d{8}$/', $docNumber)) {
                throw ValidationException::withMessages([
                    'nro_documento' => ['El DNI debe contener exactamente 8 dígitos numéricos.'],
                ]);
            }
        }

        // Validate RUC (codigo '6'): exactly 11 digits
        if ($docType->codigo === '6') {
            if (! preg_match('/^\d{11}$/', $docNumber)) {
                throw ValidationException::withMessages([
                    'nro_documento' => ['El RUC debe contener exactamente 11 dígitos numéricos.'],
                ]);
            }
        }

        $address = trim((string) ($clientData['direccion'] ?? ''));
        if (empty($address) || mb_strlen($address) < 3) {
            throw ValidationException::withMessages([
                'direccion' => ['La dirección física del cliente es obligatoria para formalizar el contrato.'],
            ]);
        }
    }

    /**
     * Synchronize or generate default payment schedule.
     * Default: 20% advance / deposit + 80% final balance.
     */
    protected function syncPaymentSchedule(Contract $contract, ?array $installmentsData = null): void
    {
        $total = (float) $contract->total;
        $fechaEmision = $contract->fecha_emision ? $contract->fecha_emision->format('Y-m-d') : date('Y-m-d');
        $fechaEvento = $contract->fecha_evento ? $contract->fecha_evento->format('Y-m-d') : null;

        if (empty($installmentsData)) {
            $depositPct = (float) config('shipibo.default_deposit_percentage', 20.0);
            $balancePct = (float) config('shipibo.default_balance_percentage', 80.0);

            $monto1 = round($total * ($depositPct / 100), 2);
            $monto2 = round($total - $monto1, 2); // Exact subtraction prevents cent round-off discrepancy

            $dueDate2 = $fechaEvento ?: Carbon::parse($fechaEmision)->addDays(15)->format('Y-m-d');

            $installmentsData = [
                [
                    'numero_cuota' => 1,
                    'descripcion' => "Adelanto Inicial / Reserva ({$depositPct}%)",
                    'porcentaje' => $depositPct,
                    'monto' => $monto1,
                    'fecha_vencimiento' => $fechaEmision,
                    'estado' => ContractInstallment::STATUS_PENDING,
                ],
                [
                    'numero_cuota' => 2,
                    'descripcion' => "Saldo Final ({$balancePct}%)",
                    'porcentaje' => $balancePct,
                    'monto' => $monto2,
                    'fecha_vencimiento' => $dueDate2,
                    'estado' => ContractInstallment::STATUS_PENDING,
                ],
            ];
        }

        // Delete existing installments if any and recreate
        $contract->installments()->delete();

        $order = 1;
        foreach ($installmentsData as $inst) {
            $monto = round(floatval($inst['monto'] ?? 0), 2);
            if ($monto <= 0) {
                continue;
            }

            $porcentaje = ! empty($inst['porcentaje'])
                ? floatval($inst['porcentaje'])
                : ($total > 0 ? round(($monto / $total) * 100, 2) : 0);

            $fechaVenc = ! empty($inst['fecha_vencimiento']) ? $inst['fecha_vencimiento'] : $fechaEmision;
            $desc = ! empty($inst['descripcion']) ? trim($inst['descripcion']) : "Cuota {$order}";

            ContractInstallment::create([
                'contract_id' => $contract->id,
                'numero_cuota' => $order,
                'descripcion' => $desc,
                'monto' => $monto,
                'porcentaje' => $porcentaje,
                'fecha_vencimiento' => $fechaVenc,
                'estado' => intval($inst['estado'] ?? 0),
            ]);

            $order++;
        }
    }

    /**
     * Strictly validate Rule 6 of the PDR:
     * The sum of all contract installments must equal the total contract value.
     *
     * @throws Rule6ViolationException
     */
    public function validateRule6(Contract $contract): void
    {
        $sumInstallments = round((float) $contract->installments()->sum('monto'), 2);
        $contractTotal = round((float) $contract->total, 2);
        $tolerance = (float) config('shipibo.rule_6_tolerance', 0.01);

        if (abs($sumInstallments - $contractTotal) > $tolerance) {
            throw new Rule6ViolationException($sumInstallments, $contractTotal);
        }
    }
}
