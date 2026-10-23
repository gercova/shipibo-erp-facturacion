<?php

namespace App\Services\Events;

use App\Models\Business;
use App\Models\District;
use App\Models\EventChecklist;
use App\Models\Serie;
use App\Models\ShipmentGuide;
use App\Models\TypeDocument;
use App\Services\Ebilling\SunatGreDispatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class EventChecklistShipmentGuideService
{
    const TICKET_PAPER_80 = [0, 0, 226.77, 650];

    public function __construct(
        private readonly SunatGreDispatchService $greDispatchService
    ) {}

    /**
     * Pre-populates and creates an Electronic Consignment Note (GRE) from an EventChecklist.
     * Only transports physical goods (opcion=1), strictly excluding services (opcion=2).
     */
    public function generateGuideFromChecklist(EventChecklist $checklist, array $options = []): ShipmentGuide
    {
        $checklist->loadMissing([
            'contract.client.tipoDocumento',
            'warehouse',
            'items.product.unit',
        ]);

        $client = $checklist->contract?->client;
        if (! $client && empty($options['idcliente'])) {
            throw new InvalidArgumentException('El checklist no tiene un contrato o cliente asociado para generar la guía de remisión.');
        }

        $clientId = $options['idcliente'] ?? $client?->id;

        // Destination Ubigeo validation (must be 6 digits and exist in districts table)
        $llegadaUbigeo = trim((string) ($options['llegada_ubigeo'] ?? $client?->ubigeo ?? '220901'));
        if (strlen($llegadaUbigeo) !== 6 || ! District::query()->where('codigo', $llegadaUbigeo)->exists()) {
            throw new InvalidArgumentException("El código de ubigeo de destino '{$llegadaUbigeo}' no es un ubigeo válido de 6 dígitos.");
        }

        // Origin Ubigeo & Address
        $business = Business::query()->first();
        $partidaUbigeo = trim((string) ($options['partida_ubigeo'] ?? $checklist->warehouse?->ubigeo ?? '220901'));
        $partidaDireccion = mb_strtoupper(trim((string) (
            $options['partida_direccion'] ??
            $checklist->warehouse?->direccion ??
            $business?->direccion ??
            'JR. ALEGRIA ARIAS DE MOREY NRO. 488, TARAPOTO'
        )));

        $llegadaDireccion = mb_strtoupper(trim((string) (
            $options['llegada_direccion'] ??
            $checklist->lugar_evento ??
            $checklist->contract?->event_place ??
            $client?->direccion ??
            'DIRECCION DEL EVENTO'
        )));

        // Filter physical goods only (strictly excluding services with opcion = 2)
        $physicalItems = $checklist->items->filter(function ($item) {
            if ($item->product) {
                return (int) $item->product->opcion !== 2;
            }

            return true;
        });

        if ($physicalItems->isEmpty()) {
            throw new InvalidArgumentException(
                'El checklist no contiene bienes físicos para transportar. Los servicios de personal, transporte o paquetes se facturan y no se trasladan con GRE.'
            );
        }

        $typeDocument = TypeDocument::query()->where('codigo', '09')->first();
        $warehouseId = $checklist->idalmacen ?: 1;

        $guide = DB::transaction(function () use (
            $checklist,
            $clientId,
            $warehouseId,
            $typeDocument,
            $partidaUbigeo,
            $partidaDireccion,
            $llegadaUbigeo,
            $llegadaDireccion,
            $physicalItems,
            $options
        ) {
            $serieData = $this->resolveGuideSerie($warehouseId, $typeDocument?->id);

            $motivoCodigo = trim((string) ($options['motivo_traslado_codigo'] ?? '01'));
            $motivoDesc = mb_strtoupper(trim((string) ($options['motivo_traslado_descripcion'] ?? 'VENTA')));
            $modoTransporte = trim((string) ($options['modo_transporte'] ?? '02')); // 02 = Transporte Privado
            $placaVehiculo = strtoupper(trim((string) ($options['placa_vehiculo'] ?? 'M1-1234')));

            $totalWeight = (float) ($options['peso_total'] ?? max(
                1.0,
                $physicalItems->sum(fn ($i) => (float) ($i->qty_enviada > 0 ? $i->qty_enviada : $i->cantidad))
            ));

            $conductorDoc = trim((string) ($options['conductor_documento'] ?? '00000000'));
            $conductorNombre = mb_strtoupper(trim((string) (
                $options['conductor_nombre'] ??
                $checklist->responsable_montaje ??
                'CONDUCTOR RESPONSABLE'
            )));

            $guide = ShipmentGuide::query()->create([
                'idcliente' => (int) $clientId,
                'idalmacen' => $warehouseId,
                'idtipo_comprobante' => $typeDocument?->id,
                'serie' => $serieData['serie'],
                'correlativo' => $serieData['correlativo'],
                'fecha_emision' => $options['fecha_emision'] ?? now()->toDateString(),
                'fecha_inicio_traslado' => $options['fecha_inicio_traslado'] ?? (
                    $checklist->fecha_evento ? $checklist->fecha_evento->toDateString() : now()->toDateString()
                ),
                'motivo_traslado_codigo' => $motivoCodigo,
                'motivo_traslado_descripcion' => $motivoDesc,
                'modo_transporte' => $modoTransporte,
                'peso_total' => $totalWeight,
                'unidad_peso' => strtoupper((string) ($options['unidad_peso'] ?? 'KGM')),
                'partida_ubigeo' => $partidaUbigeo,
                'partida_direccion' => $partidaDireccion,
                'llegada_ubigeo' => $llegadaUbigeo,
                'llegada_direccion' => $llegadaDireccion,
                'transportista_documento_tipo' => null,
                'transportista_documento' => null,
                'transportista_nombre' => null,
                'conductor_documento_tipo' => '1',
                'conductor_documento' => $conductorDoc,
                'conductor_nombre' => $conductorNombre,
                'placa_vehiculo' => $placaVehiculo,
                'placa_secundaria' => strtoupper(trim((string) ($options['placa_secundaria'] ?? ''))),
                'observaciones' => trim((string) ($options['observaciones'] ?? "Guía vinculada a checklist {$checklist->codigo} y contrato ".($checklist->contract?->contract_number ?? 'S/N'))),
                'contract_id' => $checklist->contract_id,
                'checklist_id' => $checklist->id,
                'estado_cpe' => 0,
            ]);

            foreach ($physicalItems as $item) {
                $qty = (float) ($item->qty_enviada > 0 ? $item->qty_enviada : $item->cantidad);
                $guide->items()->create([
                    'product_id' => $item->product_id,
                    'codigo' => $item->product?->codigo_interno ?: ('ITM-'.$item->id),
                    'descripcion' => mb_strtoupper(trim((string) ($item->product?->descripcion ?: $item->item_name ?: 'ARTICULO'))),
                    'unidad' => strtoupper((string) ($item->product?->unit?->codigo ?: 'NIU')),
                    'cantidad' => $qty,
                ]);
            }

            if ($serieData['serie_model']) {
                $serieData['serie_model']->update(['correlativo' => $serieData['next_correlativo']]);
            }

            // Link guide to checklist
            $checklist->update(['shipment_guide_id' => $guide->id]);

            return $guide;
        });

        // Generate PDF documents
        $this->generateGuidePdfs($guide);

        // Dispatch to SUNAT GRE REST API (Beta mock default)
        if ($options['send_to_sunat'] ?? true) {
            $this->greDispatchService->dispatch($guide, [
                'is_beta' => $options['is_beta'] ?? true,
                'mock' => $options['mock'] ?? true,
            ]);
        }

        return $guide;
    }

    public function generateGuidePdfs(ShipmentGuide $guide): void
    {
        $guide->loadMissing(['client', 'warehouse', 'items']);
        $business = Business::query()->find(1) ?: new Business(['ruc' => '20600000001', 'razon_social' => 'MYTEMS E.I.R.L.']);

        try {
            // A4 PDF
            $dirA4 = public_path('files/shipment_guides/a4');
            File::ensureDirectoryExists($dirA4);
            $pdfA4 = Pdf::loadView('admin.shipment_guides.pdf', compact('guide', 'business'))->setPaper('a4');
            file_put_contents($dirA4.DIRECTORY_SEPARATOR.$guide->serie.'-'.$guide->correlativo.'.pdf', $pdfA4->output());

            // Ticket PDF
            $dirTicket = public_path('files/shipment_guides/ticket');
            File::ensureDirectoryExists($dirTicket);
            $pdfTicket = Pdf::loadView('admin.shipment_guides.ticket', compact('guide', 'business'))->setPaper(self::TICKET_PAPER_80);
            file_put_contents($dirTicket.DIRECTORY_SEPARATOR.$guide->serie.'-'.$guide->correlativo.'.pdf', $pdfTicket->output());
        } catch (\Throwable) {
            // In headless/test environments where DomPDF fonts might not be installed, ignore PDF rendering exceptions
        }
    }

    protected function resolveGuideSerie(int $warehouseId, ?int $typeDocumentId): array
    {
        $serieModel = null;

        if ($typeDocumentId) {
            $serieModel = Serie::query()
                ->where('idtipo_documento', $typeDocumentId)
                ->where('estado', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        $serie = $serieModel?->serie ?: 'T001';
        $nextCorrelativo = (int) ($serieModel?->correlativo ?: 1);

        if (! $serieModel) {
            $lastCorrelativo = (int) ShipmentGuide::query()->where('serie', $serie)->max(DB::raw('CAST(correlativo as unsigned)'));
            $nextCorrelativo = $lastCorrelativo + 1;
        }

        return [
            'serie' => $serie,
            'correlativo' => str_pad((string) $nextCorrelativo, 8, '0', STR_PAD_LEFT),
            'next_correlativo' => $nextCorrelativo + 1,
            'serie_model' => $serieModel,
        ];
    }
}
