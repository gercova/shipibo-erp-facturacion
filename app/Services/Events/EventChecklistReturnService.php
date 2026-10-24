<?php

namespace App\Services\Events;

use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\EventChecklist;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventChecklistReturnService
{
    /**
     * Close the return process for an event checklist.
     * Validates that sum(categories) == qty_enviada,
     * updates stock idempotently using lockForUpdate,
     * records inventory movements, and generates contract adjustments.
     *
     * @param  array  $itemsData  [itemId => ['qty_ok' => ..., 'qty_danada' => ..., 'qty_quebrada' => ..., 'qty_faltante' => ..., 'qty_consumida' => ..., 'observaciones' => ...]]
     * @return array Summary of processed return and charges
     *
     * @throws ValidationException
     */
    public function closeReturn(
        EventChecklist $checklist,
        $itemsDataOrUser = [],
        ?User $user = null,
        bool $applyStock = true
    ): array {
        if ($itemsDataOrUser instanceof User) {
            $user = $itemsDataOrUser;
            $itemsData = [];
        } else {
            $itemsData = is_array($itemsDataOrUser) ? $itemsDataOrUser : [];
        }

        $user = $user ?? Auth::user() ?? User::find($checklist->idusuario) ?? User::first();

        // Idempotency: if stock was already applied and return was closed, return immediately
        if (! is_null($checklist->stock_aplicado_at)) {
            return [
                'success' => true,
                'already_closed' => true,
                'stock_applied' => false,
                'message' => 'El retorno ya fue cerrado y el stock ya fue aplicado previamente.',
                'checklist' => $checklist,
                'additional_charges' => 0.00,
                'adjustments' => [],
                'movements_count' => 0,
            ];
        }

        // 1. Pre-validation: ensure each item return sum equals qty_enviada
        $items = $checklist->items()->get()->keyBy('id');
        $errors = [];

        foreach ($items as $itemId => $item) {
            $input = $itemsData[$itemId] ?? null;

            // If input is provided, use input; otherwise use item's existing attributes or default to qty_enviada as ok
            $qtyEnviada = (float) ($input['qty_enviada'] ?? ($item->qty_enviada > 0 ? $item->qty_enviada : ($item->cantidad_llevada > 0 ? $item->cantidad_llevada : $item->cantidad_planeada)));
            $qtyOk = (float) ($input['qty_ok'] ?? $item->qty_ok);
            $qtyDanada = (float) ($input['qty_danada'] ?? $item->qty_danada);
            $qtyQuebrada = (float) ($input['qty_quebrada'] ?? $item->qty_quebrada);
            $qtyFaltante = (float) ($input['qty_faltante'] ?? $item->qty_faltante);
            $qtyConsumida = (float) ($input['qty_consumida'] ?? $item->qty_consumida);

            $sumCategories = round($qtyOk + $qtyDanada + $qtyQuebrada + $qtyFaltante + $qtyConsumida, 4);

            if (abs($sumCategories - $qtyEnviada) > 0.0001) {
                $diff = round($qtyEnviada - $sumCategories, 4);
                $errors["items.{$itemId}"] = "El ítem '{$item->descripcion}' no cuadra: enviadas {$qtyEnviada}, suma retorno {$sumCategories} (diferencia: {$diff}).";
            }
        }

        if (! empty($errors)) {
            $msg = implode(' | ', $errors);
            throw new \InvalidArgumentException("Las cantidades de retorno no coinciden con la cantidad enviada: {$msg}");
        }

        // 2. Database transaction
        return DB::transaction(function () use ($checklist, $items, $itemsData, $user, $applyStock) {
            $now = Carbon::now();
            $warehouseId = (int) ($checklist->idalmacen ?: 1);
            $totalAdditionalCharges = 0.00;
            $adjustmentsCreated = [];
            $stockMovementsCount = 0;

            foreach ($items as $itemId => $item) {
                $input = $itemsData[$itemId] ?? [];

                $qtyEnviada = (float) ($input['qty_enviada'] ?? ($item->qty_enviada > 0 ? $item->qty_enviada : ($item->cantidad_llevada > 0 ? $item->cantidad_llevada : $item->cantidad_planeada)));
                $qtyOk = (float) ($input['qty_ok'] ?? $item->qty_ok);
                $qtyDanada = (float) ($input['qty_danada'] ?? $item->qty_danada);
                $qtyQuebrada = (float) ($input['qty_quebrada'] ?? $item->qty_quebrada);
                $qtyFaltante = (float) ($input['qty_faltante'] ?? $item->qty_faltante);
                $qtyConsumida = (float) ($input['qty_consumida'] ?? $item->qty_consumida);
                $observaciones = $input['observaciones'] ?? $item->observaciones;

                $product = $item->product;
                $replacementCost = isset($input['unit_penalty']) && (float) $input['unit_penalty'] >= 0
                    ? (float) $input['unit_penalty']
                    : ($product ? $product->effective_replacement_cost : (float) $item->costo_penalidad_estimado);

                $itemIncidentsCost = round(($qtyQuebrada + $qtyFaltante + $qtyDanada) * $replacementCost, 2);
                $hasIncident = ($qtyQuebrada > 0 || $qtyFaltante > 0 || $qtyDanada > 0);

                // Update item attributes
                $item->qty_enviada = $qtyEnviada;
                $item->qty_ok = $qtyOk;
                $item->qty_danada = $qtyDanada;
                $item->qty_quebrada = $qtyQuebrada;
                $item->qty_faltante = $qtyFaltante;
                $item->qty_consumida = $qtyConsumida;
                $item->cantidad_devuelta = $qtyOk;
                $item->devuelto = ($qtyOk + $qtyDanada >= $qtyEnviada);
                $item->tiene_incidencia = $hasIncident;
                $item->observaciones = $observaciones;
                $item->costo_penalidad_estimado = $itemIncidentsCost;

                if ($hasIncident) {
                    $types = [];
                    if ($qtyQuebrada > 0) {
                        $types[] = 'roto';
                    }
                    if ($qtyFaltante > 0) {
                        $types[] = 'extraviado';
                    }
                    if ($qtyDanada > 0) {
                        $types[] = 'danado';
                    }
                    $item->tipo_incidencia = implode(', ', $types);
                } else {
                    $item->tipo_incidencia = null;
                }

                $item->idusuario_devuelto = $user->id;
                $item->fecha_devuelto = $now;
                $item->save();

                // 3. Generate Contract Adjustments (B3) if contract exists and item has broken/missing/damaged
                if ($checklist->contract_id && $hasIncident) {
                    $contract = $checklist->contract;
                    $targetInstallment = null;
                    if (! empty($input['installment_id'])) {
                        $targetInstallment = \App\Models\ContractInstallment::where('id', $input['installment_id'])
                            ->where('contract_id', $contract->id)
                            ->where('estado', \App\Models\ContractInstallment::STATUS_PENDING)
                            ->first();
                    }
                    if (! $targetInstallment) {
                        $targetInstallment = \App\Models\ContractInstallment::where('contract_id', $contract->id)
                            ->where('estado', \App\Models\ContractInstallment::STATUS_PENDING)
                            ->orderBy('numero_cuota', 'desc')
                            ->first();
                    }

                    // Broken items adjustment
                    if ($qtyQuebrada > 0) {
                        $chargeAmount = round($qtyQuebrada * $replacementCost, 2);
                        $adj = ContractAdjustment::create([
                            'contract_id' => $contract->id,
                            'installment_id' => $targetInstallment?->id,
                            'type' => ContractAdjustment::TYPE_BREAKAGE,
                            'description' => "Rotura [{$checklist->codigo}]: {$item->descripcion} ({$qtyQuebrada} unid.)",
                            'amount' => $chargeAmount,
                            'product_id' => $item->idproducto,
                            'checklist_item_id' => $item->id,
                            'user_id' => $user->id,
                        ]);
                        $adjustmentsCreated[] = $adj;
                        $totalAdditionalCharges += $chargeAmount;
                    }

                    // Missing items adjustment
                    if ($qtyFaltante > 0) {
                        $chargeAmount = round($qtyFaltante * $replacementCost, 2);
                        $adj = ContractAdjustment::create([
                            'contract_id' => $contract->id,
                            'installment_id' => $targetInstallment?->id,
                            'type' => ContractAdjustment::TYPE_MISSING,
                            'description' => "Faltante / Extravío [{$checklist->codigo}]: {$item->descripcion} ({$qtyFaltante} unid.)",
                            'amount' => $chargeAmount,
                            'product_id' => $item->idproducto,
                            'checklist_item_id' => $item->id,
                            'user_id' => $user->id,
                        ]);
                        $adjustmentsCreated[] = $adj;
                        $totalAdditionalCharges += $chargeAmount;
                    }

                    // Damaged items adjustment (repair fee)
                    if ($qtyDanada > 0) {
                        $chargeAmount = round($qtyDanada * $replacementCost, 2);
                        $adj = ContractAdjustment::create([
                            'contract_id' => $contract->id,
                            'installment_id' => $targetInstallment?->id,
                            'type' => ContractAdjustment::TYPE_BREAKAGE,
                            'description' => "Daño / Deterioro [{$checklist->codigo}]: {$item->descripcion} ({$qtyDanada} unid.)",
                            'amount' => $chargeAmount,
                            'product_id' => $item->idproducto,
                            'checklist_item_id' => $item->id,
                            'user_id' => $user->id,
                        ]);
                        $adjustmentsCreated[] = $adj;
                        $totalAdditionalCharges += $chargeAmount;
                    }
                }

                // 4. Inventory stock deduction (Idempotent: only if stock_aplicado_at is null)
                if ($applyStock && is_null($checklist->stock_aplicado_at) && $product) {
                    // Rule: Products with opcion = 2 (services, staff, transport) are ALWAYS ignored
                    if ((int) $product->opcion === 2) {
                        continue;
                    }

                    $stockDeduction = 0.0000;
                    $concept = '';

                    if ($product->rentable) {
                        // Rentables: Only broken and missing items are permanently deducted from stock.
                        // OK and Damaged remain in the system.
                        $stockDeduction = (float) ($qtyQuebrada + $qtyFaltante);
                        $concept = 'quebrada_faltante';
                    } else {
                        // Consumables: Consumed amount is deducted, broken/missing are also deducted.
                        // The rest (qty_ok) remains in stock.
                        $stockDeduction = (float) ($qtyConsumida + $qtyQuebrada + $qtyFaltante);
                        $concept = 'consumo_evento';
                    }

                    if ($stockDeduction > 0) {
                        $stockRecord = StockProduct::where('idproducto', $product->id)
                            ->where('idalmacen', $warehouseId)
                            ->lockForUpdate()
                            ->first();

                        if (! $stockRecord) {
                            $stockRecord = StockProduct::create([
                                'idproducto' => $product->id,
                                'idalmacen' => $warehouseId,
                                'stock_actual' => 0.0000,
                                'stock_minimo' => 0.0000,
                            ]);
                        }

                        $newStock = round(max(0, (float) $stockRecord->stock_actual - $stockDeduction), 4);
                        $stockRecord->update(['stock_actual' => $newStock]);

                        // Also keep products.stock_actual synchronized
                        $product->update(['stock_actual' => $newStock]);

                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'type' => InventoryMovement::TYPE_RETORNO_EVENTO,
                            'concept' => $concept,
                            'quantity' => $stockDeduction,
                            'movement_sign' => -1,
                            'checklist_id' => $checklist->id,
                            'checklist_item_id' => $item->id,
                            'contract_id' => $checklist->contract_id,
                            'user_id' => $user->id,
                            'notes' => "Deducción retorno checklist {$checklist->codigo} ({$concept})",
                        ]);

                        $stockMovementsCount++;
                    }
                }
            }

            // 4b. Update contract pending installment and total if adjustments were generated
            if ($checklist->contract_id && $totalAdditionalCharges > 0) {
                $contract = $checklist->contract;
                $firstItemInput = reset($itemsData);
                $specifiedInstId = is_array($firstItemInput) ? ($firstItemInput['installment_id'] ?? null) : null;

                $targetInst = null;
                if (! empty($specifiedInstId)) {
                    $targetInst = \App\Models\ContractInstallment::where('id', $specifiedInstId)
                        ->where('contract_id', $contract->id)
                        ->where('estado', \App\Models\ContractInstallment::STATUS_PENDING)
                        ->first();
                }
                if (! $targetInst) {
                    $targetInst = \App\Models\ContractInstallment::where('contract_id', $contract->id)
                        ->where('estado', \App\Models\ContractInstallment::STATUS_PENDING)
                        ->orderBy('numero_cuota', 'desc')
                        ->first();
                }

                if ($targetInst) {
                    $targetInst->monto = round($targetInst->monto + $totalAdditionalCharges, 2);
                    $targetInst->save();

                    $contract->total = round($contract->total + $totalAdditionalCharges, 2);
                    $contract->subtotal = round($contract->total / 1.18, 2);
                    $contract->igv = round($contract->total - $contract->subtotal, 2);
                    $contract->save();

                    $allInstallments = \App\Models\ContractInstallment::where('contract_id', $contract->id)->get();
                    foreach ($allInstallments as $inst) {
                        $inst->porcentaje = $contract->total > 0 ? round(($inst->monto / $contract->total) * 100, 2) : 0;
                        $inst->save();
                    }
                }
            }

            // 5. Update checklist status and flags
            if ($applyStock && is_null($checklist->stock_aplicado_at)) {
                $checklist->stock_aplicado_at = $now;
            }

            $checklist->retorno_cerrado_at = $now;
            $checklist->retorno_cerrado_by = $user->id;
            $checklist->recalculateCounters();

            return [
                'success' => true,
                'checklist_id' => $checklist->id,
                'codigo' => $checklist->codigo,
                'retorno_cerrado_at' => $now->toDateTimeString(),
                'stock_applied' => ! is_null($checklist->stock_aplicado_at),
                'stock_movements' => $stockMovementsCount,
                'adjustments_count' => count($adjustmentsCreated),
                'total_charges' => round($totalAdditionalCharges, 2),
            ];
        });
    }

    /**
     * Reopen a closed return checklist.
     * Authorized users only. Creates audit trail and safely reverses
     * applied stock movements so they can be re-calculated upon re-closing.
     */
    public function reopenReturn(EventChecklist $checklist, User $user, string $reason): bool
    {
        return DB::transaction(function () use ($checklist, $user, $reason) {
            $now = Carbon::now();

            // Reverse inventory movements if stock was applied
            if (! is_null($checklist->stock_aplicado_at)) {
                $movements = InventoryMovement::where('checklist_id', $checklist->id)
                    ->where('movement_sign', -1)
                    ->get();

                foreach ($movements as $mov) {
                    $stockRecord = StockProduct::where('idproducto', $mov->product_id)
                        ->where('idalmacen', $mov->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stockRecord) {
                        $restoredStock = round((float) $stockRecord->stock_actual + (float) $mov->quantity, 4);
                        $stockRecord->update(['stock_actual' => $restoredStock]);

                        Product::where('id', $mov->product_id)->update(['stock_actual' => $restoredStock]);
                    }

                    // Record reversal movement for transparent audit
                    InventoryMovement::create([
                        'product_id' => $mov->product_id,
                        'warehouse_id' => $mov->warehouse_id,
                        'type' => InventoryMovement::TYPE_RETORNO_EVENTO,
                        'concept' => InventoryMovement::CONCEPT_REVERSION,
                        'quantity' => $mov->quantity,
                        'movement_sign' => 1,
                        'checklist_id' => $checklist->id,
                        'checklist_item_id' => $mov->checklist_item_id,
                        'contract_id' => $checklist->contract_id,
                        'user_id' => $user->id,
                        'notes' => 'Reversión por reapertura: '.$reason,
                    ]);
                }

                // Safely offset contract adjustments if any were generated
                $itemIds = $checklist->items()->pluck('id');
                $adjustments = ContractAdjustment::whereIn('checklist_item_id', $itemIds)->get();
                foreach ($adjustments as $adj) {
                    if ((float) $adj->amount > 0) {
                        ContractAdjustment::create([
                            'contract_id' => $adj->contract_id,
                            'type' => ContractAdjustment::TYPE_DISCOUNT,
                            'description' => "Reversión por reapertura retorno [{$checklist->codigo}]: {$adj->description}",
                            'amount' => -abs((float) $adj->amount),
                            'product_id' => $adj->product_id,
                            'checklist_item_id' => $adj->checklist_item_id,
                            'user_id' => $user->id,
                        ]);
                    }
                }

                $checklist->stock_aplicado_at = null;
            }

            // Register audit of reopening
            $checklist->reabierto_at = $now;
            $checklist->reabierto_by = $user->id;
            $checklist->motivo_reapertura = $reason;
            $checklist->retorno_cerrado_at = null;
            $checklist->retorno_cerrado_by = null;
            $checklist->estado = EventChecklist::STATUS_DISPATCHED;
            $checklist->save();

            return true;
        });
    }
}
