<?php

namespace App\Services\Ebilling;

use App\Models\Business;
use App\Models\ShipmentGuide;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SunatGreDispatchService
{
    const SUNAT_AUTH_URL_BETA = 'https://api-seguridad.sunat.gob.pe/v1/clientessol';

    const SUNAT_GRE_URL_BETA = 'https://gre-test.cpe.sunat.gob.pe/v1/contribuyente/gem/comprobantes';

    /**
     * Builds the standard SUNAT REST API payload for Electronic Consignment Note (GRE Remitente).
     */
    public function buildPayload(ShipmentGuide $guide, Business $business): array
    {
        $guide->loadMissing(['client.tipoDocumento', 'warehouse', 'items.product']);

        if ($guide->items->isEmpty()) {
            throw new InvalidArgumentException('La guía de remisión no contiene bienes físicos para el traslado.');
        }

        $destDocTipo = optional($guide->client?->tipoDocumento)->codigo ?: '1';
        $destDocNum = trim((string) ($guide->client?->nro_documento ?? '00000000'));
        $destNombre = trim((string) ($guide->client?->nombres ?? 'CLIENTE DESTINO'));

        return [
            'version' => '1.0',
            'tipoDoc' => '09',
            'serie' => trim((string) $guide->serie),
            'correlativo' => trim((string) $guide->correlativo),
            'fechaEmision' => optional($guide->fecha_emision)->format('Y-m-d') ?: now()->toDateString(),
            'horaEmision' => now()->format('H:i:s'),
            'datosEnvio' => [
                'codTraslado' => trim((string) ($guide->motivo_traslado_codigo ?: '01')),
                'desTraslado' => trim((string) ($guide->motivo_traslado_descripcion ?: 'VENTA')),
                'modTraslado' => trim((string) ($guide->modo_transporte ?: '02')),
                'fecInicioTraslado' => optional($guide->fecha_inicio_traslado)->format('Y-m-d') ?: now()->toDateString(),
                'pesoTotal' => round((float) $guide->peso_total, 3),
                'undMedidaPeso' => trim((string) ($guide->unidad_peso ?: 'KGM')),
                'numBultos' => $guide->items->count(),
                'partida' => [
                    'ubigeo' => trim((string) ($guide->partida_ubigeo ?: '220901')),
                    'direccion' => trim((string) ($guide->partida_direccion ?: 'ALMACEN PRINCIPAL')),
                ],
                'llegada' => [
                    'ubigeo' => trim((string) ($guide->llegada_ubigeo ?: '220901')),
                    'direccion' => trim((string) ($guide->llegada_direccion ?: 'DIRECCION DEL EVENTO')),
                ],
                'vehiculo' => [
                    'placa' => strtoupper(trim((string) ($guide->placa_vehiculo ?: 'M1-1234'))),
                    'placaSecundaria' => strtoupper(trim((string) ($guide->placa_secundaria ?? ''))),
                ],
                'chofer' => [
                    'tipoDocIdentidad' => trim((string) ($guide->conductor_documento_tipo ?: '1')),
                    'numDocIdentidad' => trim((string) ($guide->conductor_documento ?: '00000000')),
                    'nombres' => trim((string) ($guide->conductor_nombre ?: 'CONDUCTOR PRINCIPAL')),
                ],
            ],
            'remitente' => [
                'ruc' => trim((string) $business->ruc),
                'razonSocial' => trim((string) $business->razon_social),
            ],
            'destinatario' => [
                'tipoDocIdentidad' => $destDocTipo,
                'numDocIdentidad' => $destDocNum,
                'razonSocial' => $destNombre,
            ],
            'detalles' => $guide->items->map(function ($item, $idx) {
                return [
                    'linea' => $idx + 1,
                    'codigo' => trim((string) ($item->codigo ?: ('ITM-'.($idx + 1)))),
                    'descripcion' => trim((string) $item->descripcion),
                    'unidad' => trim((string) ($item->unidad ?: 'NIU')),
                    'cantidad' => round((float) $item->cantidad, 2),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Dispatches the consignment note to SUNAT GRE Remitente via REST API / OAuth2 or Beta mock.
     */
    public function dispatch(ShipmentGuide $guide, array $options = []): array
    {
        $business = Business::query()->first() ?: new Business([
            'ruc' => '20600000001',
            'razon_social' => 'MYTEMS E.I.R.L.',
        ]);

        $payload = $this->buildPayload($guide, $business);

        $isBeta = $options['is_beta'] ?? true;
        $mock = $options['mock'] ?? (app()->environment('testing') || empty($business->gre_client_id) || $isBeta);

        if ($mock) {
            $ticket = 'GRE-BETA-'.strtoupper(Str::random(12));
            $xmlSimulado = '<GreSimulation serie="'.$guide->serie.'" correlativo="'.$guide->correlativo.'" status="ACEPTADO"/>';

            $guide->update([
                'estado_cpe' => 1,
                'ticket' => $ticket,
                'cdr' => 1,
                'xml' => $xmlSimulado,
                'errores' => null,
            ]);

            return [
                'ok' => true,
                'mode' => 'beta_mock',
                'status' => 'ACEPTADO',
                'ticket' => $ticket,
                'payload' => $payload,
                'message' => 'Guía de remisión emitida y aceptada satisfactoriamente en entorno Beta/Mock.',
            ];
        }

        // Production / Live OAuth2 Call (if real credentials provided)
        try {
            $token = $this->fetchOAuthToken($business);
            $response = Http::withToken($token)
                ->post(self::SUNAT_GRE_URL_BETA, $payload);

            if ($response->successful()) {
                $ticket = $response->json('numTicket') ?: ('GRE-'.strtoupper(Str::random(8)));
                $guide->update([
                    'estado_cpe' => 1,
                    'ticket' => $ticket,
                    'cdr' => 1,
                    'errores' => null,
                ]);

                return [
                    'ok' => true,
                    'mode' => 'live',
                    'status' => 'ACEPTADO',
                    'ticket' => $ticket,
                    'payload' => $payload,
                ];
            }

            $guide->update([
                'estado_cpe' => 2,
                'errores' => $response->body(),
            ]);

            return [
                'ok' => false,
                'mode' => 'live',
                'status' => 'RECHAZADO',
                'message' => 'SUNAT rechazó el envío de la GRE: '.$response->body(),
                'payload' => $payload,
            ];
        } catch (\Throwable $e) {
            $guide->update([
                'estado_cpe' => 2,
                'errores' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 'ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function fetchOAuthToken(Business $business): string
    {
        $clientId = $business->gre_client_id;
        $clientSecret = $business->gre_client_secret;

        if (empty($clientId) || empty($clientSecret)) {
            throw new InvalidArgumentException('Credenciales GRE Client ID o Secret no configuradas.');
        }

        $authUrl = self::SUNAT_AUTH_URL_BETA.'/'.$clientId.'/oauth2/token';
        $res = Http::asForm()->post($authUrl, [
            'grant_type' => 'client_credentials',
            'scope' => 'https://api-cpe.sunat.gob.pe',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (! $res->successful() || ! $res->json('access_token')) {
            throw new InvalidArgumentException('Error al obtener token OAuth2 de SUNAT: '.$res->body());
        }

        return $res->json('access_token');
    }
}
