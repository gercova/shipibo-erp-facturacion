<?php

namespace App\Services\Ebilling;

use App\Models\Billing;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractInstallment;
use App\Models\DebitNoteType;
use App\Models\DetailBilling;
use App\Models\IgvTypeAffection;
use App\Models\Product;
use App\Models\Serie;
use App\Models\TypeDocument;
use App\Services\Ebilling\Payload\BillingPayloadBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContractEbillingService
{
    public function __construct(
        private readonly BillingPayloadBuilder $payloadBuilder
    ) {}

    /**
     * Invoices a contract installment with full validation, duplicate prevention, and tax treatment.
     *
     * @param  array  $options  [
     *                          'idtipo_comprobante' => int|null,
     *                          'tipo_documento' => '01'|'03'|null,
     *                          'id_afectacion_igv' => 1|2|null, // 1=Gravado (10), 2=Exonerado (20)
     *                          'items' => array|null, // Custom breakdown of service lines
     *                          'include_breakages' => bool,
     *                          ]
     * @return array ['billing' => Billing, 'payload' => array]
     */
    public function invoiceInstallment(ContractInstallment $installment, array $options = []): array
    {
        $installment->loadMissing([
            'contract.client.tipoDocumento',
            'contract.adjustments',
            'billing',
        ]);

        $contract = $installment->contract;
        if (! $contract) {
            throw new InvalidArgumentException('La cuota no pertenece a ningún contrato.');
        }

        // Duplicate Invoicing Prevention
        if ($installment->isBilled()) {
            throw new InvalidArgumentException(
                "La cuota #{$installment->numero_cuota} ya se encuentra facturada con el comprobante {$installment->factura_numero} (Billing ID: {$installment->billing_id})."
            );
        }

        $client = $contract->client;
        if (! $client) {
            throw new InvalidArgumentException('El contrato no tiene un cliente asignado para la facturación.');
        }

        // Resolve Document Type (01 = Factura, 03 = Boleta)
        $documentType = $this->resolveDocumentType($client, $options);
        $docTypeCode = trim((string) $documentType->codigo);

        // Validation Rules: Factura strictly requires RUC (11 digits, type '6')
        $this->validateClientDocumentForEbilling($client, $docTypeCode);

        // Build service items for this installment
        $rawItems = $this->prepareInstallmentItems($installment, $options);

        // Calculate Totals and build Detail lines
        $totals = $this->calculateTotalsAndDetails($rawItems);

        $billing = DB::transaction(function () use ($installment, $contract, $client, $documentType, $totals, $options) {
            [$serie, $correlativo, $serieModel] = $this->resolveSerieAndCorrelative($documentType, $contract->idalmacen ?: 1);

            $billing = Billing::query()->create([
                'idtipo_comprobante' => (int) $documentType->id,
                'serie' => $serie,
                'correlativo' => $correlativo,
                'fecha_emision' => $options['fecha_emision'] ?? now()->toDateString(),
                'fecha_vencimiento' => $options['fecha_vencimiento'] ?? (
                    $installment->fecha_vencimiento ? $installment->fecha_vencimiento->toDateString() : now()->toDateString()
                ),
                'hora' => now()->format('H:i:s'),
                'idcliente' => (int) $client->id,
                'idmoneda' => 1, // PEN
                'idpago' => 1,
                'modo_pago' => 1,
                'sunat_forma_pago' => 'Contado',
                'exonerada' => $totals['exonerada'],
                'inafecta' => 0,
                'gravada' => $totals['gravada'],
                'anticipo' => 0,
                'igv' => $totals['igv'],
                'icbper' => 0,
                'gratuita' => 0,
                'otros_cargos' => 0,
                'total' => $totals['total'],
                'monto_credito' => 0,
                'cuotas' => null,
                'observaciones' => trim((string) ($options['observaciones'] ?? "Facturación de cuota #{$installment->numero_cuota} de contrato {$contract->contract_number}")),
                'contract_id' => $contract->id,
                'installment_id' => $installment->id,
                'idusuario' => auth()->id() ?: 1,
                'idalmacen' => $contract->idalmacen ?: 1,
                'estado_cpe' => null,
            ]);

            foreach ($totals['details'] as $det) {
                DetailBilling::query()->create([
                    'idfacturacion' => $billing->id,
                    'idproducto' => (int) $det['product_id'],
                    'descripcion_custom' => $det['descripcion'] ?? null,
                    'cantidad' => (float) $det['cantidad'],
                    'descuento' => 0,
                    'igv' => (float) $det['igv'],
                    'icbper' => 0,
                    'factor_icbper' => null,
                    'cantidad_bolsas' => 0,
                    'id_afectacion_igv' => (int) $det['id_afectacion_igv'],
                    'precio_unitario' => (float) $det['precio_unitario'],
                    'valor_unitario' => (float) $det['valor_unitario'],
                    'valor_total' => (float) $det['valor_total'],
                    'precio_total' => (float) $det['precio_total'],
                ]);
            }

            if ($serieModel) {
                $serieModel->update(['correlativo' => (int) $correlativo]);
            }

            // Flag installment as billed with direct reference
            $installment->update([
                'billing_id' => $billing->id,
                'facturado_at' => now(),
                'factura_numero' => "{$serie}-{$correlativo}",
            ]);

            return $billing;
        });

        // Generate SUNAT CPE Payload via BillingPayloadBuilder
        $payload = $this->payloadBuilder->build($billing);

        return [
            'billing' => $billing,
            'payload' => $payload,
        ];
    }

    /**
     * Issues an electronic Debit Note (08) for contract breakage / damage penalties (B4),
     * referencing the parent invoice or receipt.
     */
    public function issueDebitNoteForAdjustments(Contract $contract, Billing $parentBilling, array $options = []): array
    {
        $contract->loadMissing(['client.tipoDocumento', 'adjustments']);
        $parentBilling->loadMissing(['typeDocument', 'customer.tipoDocumento']);

        $debitNoteDocumentType = TypeDocument::query()->where('codigo', '08')->firstOrFail();

        // SUNAT motive: 03 = PENALIDADES / OTROS CONCEPTOS, or 02 = AUMENTO EN EL VALOR
        $debitNoteMotiveCode = $options['motivo_codigo'] ?? '03';
        $debitNoteType = DebitNoteType::query()->where('codigo', $debitNoteMotiveCode)->first()
            ?: DebitNoteType::query()->first();

        // Find adjustments to bill
        $adjustments = ! empty($options['adjustments'])
            ? collect($options['adjustments'])
            : $contract->adjustments->filter(fn (ContractAdjustment $adj) => in_array($adj->type, [
                ContractAdjustment::TYPE_BREAKAGE,
                ContractAdjustment::TYPE_MISSING,
                ContractAdjustment::TYPE_ADDITIONAL,
                ContractAdjustment::TYPE_MANUAL,
            ]) && (float) $adj->amount > 0);

        if ($adjustments->isEmpty()) {
            throw new InvalidArgumentException('No existen penalidades o daños registrados pendientes de facturar en este contrato.');
        }

        // Penalty / service product
        $penaltyProduct = Product::query()
            ->where('opcion', 2)
            ->where(function ($q) {
                $q->where('descripcion', 'LIKE', '%TRANSPORTE%')
                    ->orWhere('descripcion', 'LIKE', '%BAR%')
                    ->orWhere('descripcion', 'LIKE', '%SERVICIO%');
            })
            ->first() ?: Product::query()->where('opcion', 2)->first() ?: Product::query()->first();

        $items = [];
        foreach ($adjustments as $adj) {
            $amount = (float) (is_array($adj) ? $adj['amount'] : $adj->amount);
            $desc = is_array($adj) ? $adj['description'] : ($adj->description ?: 'PENALIDAD POR ROTURA / DAÑO EN EVENTO');
            $prodId = is_array($adj) ? ($adj['product_id'] ?? null) : $adj->product_id;

            $items[] = [
                'product_id' => $prodId ?: $penaltyProduct->id,
                'descripcion' => mb_strtoupper($desc),
                'cantidad' => 1,
                'precio_unitario' => $amount,
                'id_afectacion_igv' => $options['id_afectacion_igv'] ?? 1, // Gravado (10) default
            ];
        }

        $totals = $this->calculateTotalsAndDetails($items);

        $debitNote = DB::transaction(function () use (
            $contract,
            $parentBilling,
            $debitNoteDocumentType,
            $debitNoteType,
            $totals,
            $options
        ) {
            $isParentInvoice = optional($parentBilling->typeDocument)->codigo === '01';
            $serieCode = $isParentInvoice ? 'FD01' : 'BD01';

            $lastCorrelativo = (int) Billing::query()
                ->where('idtipo_comprobante', $debitNoteDocumentType->id)
                ->where('serie', $serieCode)
                ->max('correlativo');
            $correlativo = str_pad((string) ($lastCorrelativo + 1), 8, '0', STR_PAD_LEFT);

            $reason = trim((string) ($options['motivo'] ?? 'PENALIDAD POR ROTURAS Y DAÑOS EN EVENTO'));

            $debitNote = Billing::query()->create([
                'idtipo_comprobante' => (int) $debitNoteDocumentType->id,
                'serie' => $serieCode,
                'correlativo' => $correlativo,
                'fecha_emision' => $options['fecha_emision'] ?? now()->toDateString(),
                'fecha_vencimiento' => null,
                'hora' => now()->format('H:i:s'),
                'idcliente' => (int) $parentBilling->idcliente,
                'idmoneda' => 1,
                'idpago' => 1,
                'modo_pago' => 1,
                'sunat_forma_pago' => 'Contado',
                'exonerada' => $totals['exonerada'],
                'inafecta' => 0,
                'gravada' => $totals['gravada'],
                'anticipo' => 0,
                'igv' => $totals['igv'],
                'icbper' => 0,
                'gratuita' => 0,
                'otros_cargos' => 0,
                'total' => $totals['total'],
                'monto_credito' => 0,
                'cuotas' => null,
                'observaciones' => $reason,
                'id_tipo_nota_debito' => (int) $debitNoteType->id,
                'idfactura_anular' => (int) $parentBilling->id, // Parent document reference
                'motivo' => $reason,
                'contract_id' => $contract->id,
                'checklist_id' => $options['checklist_id'] ?? null,
                'idusuario' => auth()->id() ?: 1,
                'idalmacen' => $contract->idalmacen ?: 1,
                'estado_cpe' => null,
            ]);

            foreach ($totals['details'] as $det) {
                DetailBilling::query()->create([
                    'idfacturacion' => $debitNote->id,
                    'idproducto' => (int) $det['product_id'],
                    'descripcion_custom' => $det['descripcion'] ?? null,
                    'cantidad' => (float) $det['cantidad'],
                    'descuento' => 0,
                    'igv' => (float) $det['igv'],
                    'icbper' => 0,
                    'factor_icbper' => null,
                    'cantidad_bolsas' => 0,
                    'id_afectacion_igv' => (int) $det['id_afectacion_igv'],
                    'precio_unitario' => (float) $det['precio_unitario'],
                    'valor_unitario' => (float) $det['valor_unitario'],
                    'valor_total' => (float) $det['valor_total'],
                    'precio_total' => (float) $det['precio_total'],
                ]);
            }

            return $debitNote;
        });

        $payload = $this->payloadBuilder->build($debitNote);

        return [
            'debit_note' => $debitNote,
            'payload' => $payload,
        ];
    }

    /**
     * Prepares line items for an installment, using contract services, breakages, or default service products.
     */
    protected function prepareInstallmentItems(ContractInstallment $installment, array $options): array
    {
        if (! empty($options['items'])) {
            return $options['items'];
        }

        $items = [];
        $contract = $installment->contract;
        $installmentAmount = (float) $installment->monto;

        // Check if breakage adjustments should be included as line items in this installment
        $includeBreakages = (bool) ($options['include_breakages'] ?? false);
        $breakageTotal = 0.0;
        $breakageItems = [];

        if ($includeBreakages && $contract) {
            $breakageAdjustments = $contract->adjustments->filter(fn (ContractAdjustment $adj) => in_array($adj->type, [
                ContractAdjustment::TYPE_BREAKAGE,
                ContractAdjustment::TYPE_MISSING,
            ]) && (float) $adj->amount > 0);

            $defaultReplacementProd = Product::query()->where('opcion', 2)->first() ?: Product::query()->first();

            foreach ($breakageAdjustments as $adj) {
                $adjAmount = (float) $adj->amount;
                $breakageTotal += $adjAmount;
                $breakageItems[] = [
                    'product_id' => $adj->product_id ?: $defaultReplacementProd?->id,
                    'descripcion' => 'REPOSICIÓN / DAÑO: '.($adj->description ?: 'ARTÍCULO EVENTO'),
                    'cantidad' => 1,
                    'precio_unitario' => $adjAmount,
                    'id_afectacion_igv' => $options['id_afectacion_igv'] ?? 1, // Gravado (10)
                ];
            }
        }

        // Base installment amount (minus breakages if they were added into installment amount)
        $baseServiceAmount = max(0.01, $installmentAmount - $breakageTotal);

        // Find standard service product (opcion=2)
        $serviceProduct = Product::query()
            ->where('opcion', 2)
            ->where(function ($q) {
                $q->where('descripcion', 'LIKE', '%COCTEL%')
                    ->orWhere('descripcion', 'LIKE', '%MOZO%')
                    ->orWhere('descripcion', 'LIKE', '%SERVICIO%');
            })
            ->first() ?: Product::query()->where('opcion', 2)->first() ?: Product::query()->first();

        $defaultAffectation = $options['id_afectacion_igv'] ?? ($serviceProduct?->idcodigo_igv ?: 1);

        $items[] = [
            'product_id' => $serviceProduct?->id,
            'descripcion' => mb_strtoupper("SERVICIO DE EVENTO - CUOTA #{$installment->numero_cuota}: CONTRATO {$contract->contract_number}"),
            'cantidad' => 1,
            'precio_unitario' => $baseServiceAmount,
            'id_afectacion_igv' => $defaultAffectation,
        ];

        return array_merge($items, $breakageItems);
    }

    /**
     * Calculates tax bases, IGV amounts, and detail rows supporting mixed IGV/VAT scenarios.
     */
    public function calculateTotalsAndDetails(array $rawItems): array
    {
        $details = [];
        $gravada = 0.0;
        $exonerada = 0.0;
        $totalIgv = 0.0;
        $totalFinal = 0.0;

        foreach ($rawItems as $item) {
            $productId = (int) ($item['product_id'] ?? 1);
            $product = Product::query()->find($productId);

            $cantidad = (float) ($item['cantidad'] ?? 1);
            $precioUnitario = (float) ($item['precio_unitario'] ?? 0);
            $idAfectacion = (int) ($item['id_afectacion_igv'] ?? $product?->idcodigo_igv ?? 1);

            $afectacionModel = IgvTypeAffection::query()->find($idAfectacion);
            $afectacionCodigo = $afectacionModel?->codigo ?: '10';

            if ($afectacionCodigo === '10') {
                // Gravado - 18% IGV incluido
                $valorUnitario = round($precioUnitario / 1.18, 10);
                $valorTotal = round($valorUnitario * $cantidad, 2);
                $precioTotal = round($precioUnitario * $cantidad, 2);
                $igvLine = round($precioTotal - $valorTotal, 2);

                $gravada += $valorTotal;
                $totalIgv += $igvLine;
                $totalFinal += $precioTotal;
            } else {
                // Exonerado (20) o Inafecto (30) - 0% IGV
                $valorUnitario = $precioUnitario;
                $valorTotal = round($valorUnitario * $cantidad, 2);
                $precioTotal = $valorTotal;
                $igvLine = 0.00;

                $exonerada += $valorTotal;
                $totalFinal += $precioTotal;
            }

            $details[] = [
                'product_id' => $productId,
                'descripcion' => $item['descripcion'] ?? $product?->descripcion,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'precio_total' => $precioTotal,
                'igv' => $igvLine,
                'id_afectacion_igv' => $idAfectacion,
            ];
        }

        return [
            'gravada' => round($gravada, 2),
            'exonerada' => round($exonerada, 2),
            'igv' => round($totalIgv, 2),
            'total' => round($totalFinal, 2),
            'details' => $details,
        ];
    }

    /**
     * Validates client document for Sunat Electronic Invoicing.
     */
    protected function validateClientDocumentForEbilling(Client $client, string $docTypeCode): void
    {
        $tipoDocCodigo = optional($client->tipoDocumento)->codigo;
        $numDoc = preg_replace('/\D+/', '', (string) ($client->nro_documento ?? ''));

        if ($docTypeCode === '01') {
            // Factura requires RUC (type 6, 11 digits)
            if ($tipoDocCodigo !== '6' || strlen($numDoc) !== 11) {
                throw new InvalidArgumentException(
                    "Para emitir una Factura Electrónica (01), el cliente debe poseer RUC válido de 11 dígitos. Documento actual: '{$client->nro_documento}' (Tipo: '{$tipoDocCodigo}')."
                );
            }
        } elseif ($docTypeCode === '03') {
            // Boleta allows DNI (8 digits) or RUC (11 digits)
            if ($tipoDocCodigo === '0' || empty($numDoc)) {
                throw new InvalidArgumentException(
                    'Para emitir una Boleta de Venta Electrónica (03), el cliente debe registrar un documento de identidad válido (DNI o RUC).'
                );
            }
        }
    }

    protected function resolveDocumentType(Client $client, array $options): TypeDocument
    {
        if (! empty($options['idtipo_comprobante'])) {
            return TypeDocument::query()->findOrFail((int) $options['idtipo_comprobante']);
        }

        $code = $options['tipo_documento'] ?? null;
        if (! $code) {
            $numDoc = preg_replace('/\D+/', '', (string) ($client->nro_documento ?? ''));
            $tipoDocCodigo = optional($client->tipoDocumento)->codigo;

            $code = ($tipoDocCodigo === '6' && strlen($numDoc) === 11) ? '01' : '03';
        }

        return TypeDocument::query()->where('codigo', $code)->firstOrFail();
    }

    protected function resolveSerieAndCorrelative(TypeDocument $documentType, int $warehouseId): array
    {
        $code = trim((string) $documentType->codigo);
        $defaultSerie = $code === '01' ? 'F001' : 'B001';

        $serieModel = Serie::query()
            ->where('idtipo_documento', $documentType->id)
            ->where('estado', true)
            ->lockForUpdate()
            ->first();

        $serie = $serieModel?->serie ?: $defaultSerie;
        $nextCorrelativo = (int) ($serieModel?->correlativo ?: 1);

        if (! $serieModel) {
            $lastCorrelativo = (int) Billing::query()
                ->where('idtipo_comprobante', $documentType->id)
                ->where('serie', $serie)
                ->max('correlativo');
            $nextCorrelativo = $lastCorrelativo + 1;
        }

        return [
            $serie,
            str_pad((string) $nextCorrelativo, 8, '0', STR_PAD_LEFT),
            $serieModel,
        ];
    }
}
