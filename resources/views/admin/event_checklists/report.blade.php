@extends('admin.layout')

@section('styles')
<style>
    .report-card {
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    }

    .badge-status-pill {
        font-size: 0.95rem;
        padding: 0.4rem 1rem;
        border-radius: 999px;
    }
</style>
@endsection

@section('content')
<div class="container-xl px-4 py-4">

    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.event_checklists.tablet', $checklist->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="ri-arrow-left-line me-1"></i> Volver a Modo Tablet
                </a>
                <span class="text-muted small">|</span>
                <span class="badge bg-primary fs-7">{{ $checklist->code }}</span>
                {!! $checklist->status_badge !!}
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">Acta de Cierre y Liquidación de Evento</h1>
            <p class="text-muted mb-0">Conciliación de insumos, cristalería, roturas y estado de garantía contractual.</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.event_checklists.pdf', $checklist->id) }}" target="_blank" class="btn btn-primary px-3 py-2 rounded-pill fw-bold">
                <i class="ri-printer-line me-1"></i> Imprimir / Exportar PDF A4
            </a>
            <a href="{{ route('admin.event_checklists') }}" class="btn btn-outline-dark px-3 py-2 rounded-pill">
                <i class="ri-list-check me-1"></i> Lista de Checklists
            </a>
        </div>
    </div>

    <!-- 20% Contractual Guarantee Banner -->
    <div class="card border-0 mb-4 shadow-sm {{ $checklist->hasIncidents() ? 'bg-danger text-white' : 'bg-success text-white' }}" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="{{ $checklist->hasIncidents() ? 'ri-error-warning-line' : 'ri-shield-check-line' }} fs-2"></i>
                        <h4 class="fw-bold mb-0 text-white">
                            {{ $checklist->hasIncidents() ? 'Alerta de Garantía: Se registraron roturas o pérdidas' : 'Garantía Liberable: Retorno conforme sin incidencias' }}
                        </h4>
                    </div>
                    <p class="mb-0 {{ $checklist->hasIncidents() ? 'text-white-50' : 'text-white-50' }}">
                        @if($checklist->hasIncidents())
                            Se registraron <strong>{{ $checklist->total_incidencias }} ítems con incidencias/daños</strong>. Proceder con la retención parcial o total del fondo de garantía contractual según la Cláusula Octava del contrato.
                        @else
                            Todos los insumos y equipos físicos fueron devueltos conforme. Procede la devolución o liberación íntegra del fondo de garantía contractual.
                        @endif
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="bg-black bg-opacity-25 p-3 rounded-3 border border-white border-opacity-25 d-inline-block text-center w-100">
                        <span class="small d-block text-white-50">FONDO DE GARANTÍA (20% CONTRATO)</span>
                        <span class="h3 fw-bold text-white mb-0">{{ $signo }} {{ number_format($guaranteeAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event & Contract Header Information -->
    <div class="card border-0 report-card mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6 border-end">
                    <h6 class="fw-bold text-primary text-uppercase mb-3"><i class="ri-file-text-line me-1"></i> Datos del Contrato y Evento</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" width="35%">N° de Contrato:</td>
                                    <td class="fw-bold">{{ $checklist->contract->contract_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Fecha del Evento:</td>
                                    <td class="fw-bold">
                                        {{ $checklist->contract ? \Carbon\Carbon::parse($checklist->contract->fecha_evento)->format('d/m/Y') : '-' }}
                                        {{ $checklist->contract?->hora_evento ? \Carbon\Carbon::parse($checklist->contract->hora_evento)->format('H:i') : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Lugar / Dirección:</td>
                                    <td class="fw-bold">{{ $checklist->contract->lugar_evento ?? 'No especificado' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Almacén de Origen:</td>
                                    <td class="fw-bold">{{ $checklist->warehouse->nombre ?? 'Almacén Principal' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-success text-uppercase mb-3"><i class="ri-user-star-line me-1"></i> Datos del Cliente y Control</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" width="35%">Cliente:</td>
                                    <td class="fw-bold text-dark">{{ $checklist->contract->client->nombres ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Documento:</td>
                                    <td class="fw-bold">{{ $checklist->contract->client->nro_documento ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Responsable Despacho:</td>
                                    <td class="fw-bold">{{ $checklist->user->nombre ?? 'Administrador' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Fecha Registro Checklist:</td>
                                    <td class="fw-bold">{{ \Carbon\Carbon::parse($checklist->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 report-card text-center p-3">
                <span class="text-muted small fw-bold">TOTAL ÍTEMS CONTROLADOS</span>
                <span class="h2 fw-bold text-dark mb-0">{{ $checklist->total_items }}</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 report-card text-center p-3">
                <span class="text-muted small fw-bold">SALIDA VERIFICADA</span>
                <span class="h2 fw-bold text-success mb-0">{{ $checklist->total_llevados }} / {{ $checklist->total_items }}</span>
                <small class="text-muted">{{ $checklist->progressLlevado() }}% despachado</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 report-card text-center p-3">
                <span class="text-muted small fw-bold">RETORNO CONFORME</span>
                <span class="h2 fw-bold text-primary mb-0">{{ $checklist->total_devueltos }} / {{ $checklist->total_items }}</span>
                <small class="text-muted">{{ $checklist->progressDevuelto() }}% devuelto</small>
            </div>
        </div>
    </div>

    <!-- Return Status & Supervisor Decision Banner -->
    @php
        $affectedItems = $checklist->items->filter(function($i) {
            return $i->tiene_incidencia || ($i->qty_quebrada > 0 || $i->qty_faltante > 0 || $i->qty_danada > 0);
        });
        $totalCargos = 0.00;
        foreach($affectedItems as $ai) {
            $prod = $ai->product;
            $unitCost = $prod ? $prod->effective_replacement_cost : (float) $ai->costo_penalidad_estimado;
            $totalCargos += round(($ai->qty_quebrada + $ai->qty_faltante + $ai->qty_danada) * $unitCost, 2);
        }
    @endphp

    <div class="card border-0 report-card mb-4 bg-light">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="ri-scales-3-line text-primary me-1"></i> Estado del Cierre de Retorno
                    </h5>
                    <div class="text-muted small">
                        @if($checklist->isReturnClosed())
                            <span class="badge bg-success me-2"><i class="ri-checkbox-circle-line me-1"></i> Retorno Cerrado</span>
                            Cerrado el {{ $checklist->retorno_cerrado_at->format('d/m/Y H:i') }}
                            por <strong>{{ $checklist->retornoCerradoBy->nombre ?? 'Usuario' }}</strong>
                            | Impacto en Stock: <span class="badge bg-info text-dark">Aplicado</span>
                        @else
                            <span class="badge bg-warning text-dark me-2"><i class="ri-time-line me-1"></i> Pendiente de Cierre</span>
                            Las cantidades de retorno aún pueden ser editadas en el modo tablet.
                        @endif
                    </div>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <div class="d-inline-flex flex-column align-items-lg-end">
                        <span class="small text-muted fw-bold">TOTAL CARGOS ADICIONALES SUGERIDOS</span>
                        <span class="h3 fw-bold text-danger mb-0">{{ $signo }} {{ number_format($totalCargos, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($totalCargos > 0)
            <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small text-muted">
                    <i class="ri-information-line text-info me-1"></i>
                    <strong>Decisión del Supervisor:</strong> Las roturas y faltantes ya generan registros de auditoría en <code>contract_adjustments</code>. Seleccione el destino financiero:
                </div>
                <div class="d-flex gap-2">
                    @if($checklist->contract)
                        <a href="{{ route('admin.edit_contract', $checklist->contract->id) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="ri-wallet-3-line me-1"></i> Imputar a Cuota 2 (Contrato)
                        </a>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="Swal.fire('Nota de Débito', 'Solicitud notificada al área de administración para emisión de Nota de Débito (08).', 'info')">
                        <i class="ri-file-paper-2-line me-1"></i> Solicitar Nota de Débito (08)
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Incidents Breakdown Table if any -->
    @if($affectedItems->count() > 0)
    <div class="card border-0 report-card mb-4 border-start border-danger border-4">
        <div class="card-header bg-danger bg-opacity-10 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-danger mb-0">
                <i class="ri-error-warning-line me-1"></i> Detalle de Roturas / Faltantes / Daños
            </h5>
            <span class="badge bg-danger fs-6">{{ $affectedItems->count() }} ítems afectados</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th width="4%" class="text-center">#</th>
                            <th>Ítem / Descripción</th>
                            <th width="14%" class="text-center">Desglose Faltante</th>
                            <th width="14%" class="text-center">Costo Reposición</th>
                            <th width="14%" class="text-end">Subtotal Cargo</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($affectedItems as $index => $item)
                        @php
                            $prod = $item->product;
                            $costUnit = $prod ? $prod->effective_replacement_cost : (float) $item->costo_penalidad_estimado;
                            $affectedQty = (float) ($item->qty_quebrada + $item->qty_faltante + $item->qty_danada);
                            $subtotalCargo = round($affectedQty * $costUnit, 2);
                        @endphp
                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $item->descripcion }}</strong>
                                <div class="text-muted small">{{ $item->categoria }}</div>
                            </td>
                            <td class="text-center small">
                                @if($item->qty_quebrada > 0) <span class="badge bg-danger">Roto: {{ $item->qty_quebrada }}</span> @endif
                                @if($item->qty_faltante > 0) <span class="badge bg-warning text-dark">Falta: {{ $item->qty_faltante }}</span> @endif
                                @if($item->qty_danada > 0) <span class="badge bg-secondary">Daño: {{ $item->qty_danada }}</span> @endif
                            </td>
                            <td class="text-center">
                                {{ $signo }} {{ number_format($costUnit, 2) }}
                            </td>
                            <td class="text-end fw-bold text-danger">
                                {{ $signo }} {{ number_format($subtotalCargo, 2) }}
                            </td>
                            <td>
                                <em>{{ $item->observaciones ?? 'Sin detalle adicional' }}</em>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Full Items Inventory Table with 6 Return Categories -->
    <div class="card border-0 report-card mb-4">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">
                <i class="ri-inbox-line me-1 text-primary"></i> Inventario Completo y Retorno Detallado
            </h5>
            <span class="badge bg-secondary">{{ $checklist->items->count() }} ítems</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th width="4%" class="text-center">#</th>
                            <th>Descripción del Ítem</th>
                            <th width="10%" class="text-center">Enviado</th>
                            <th width="9%" class="text-center text-success">OK</th>
                            <th width="9%" class="text-center text-warning">Dañado</th>
                            <th width="9%" class="text-center text-danger">Roto</th>
                            <th width="9%" class="text-center text-danger">Faltante</th>
                            <th width="9%" class="text-center text-info">Consumido</th>
                            <th width="14%" class="text-center">Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($checklist->items as $index => $item)
                        @php
                            $sentQty = (float) ($item->qty_enviada > 0 ? $item->qty_enviada : ($item->cantidad_llevada > 0 ? $item->cantidad_llevada : $item->cantidad_planeada));
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <strong class="text-dark">{{ $item->descripcion }}</strong>
                                <div class="text-muted small">{{ $item->categoria }}</div>
                            </td>
                            <td class="text-center fw-bold bg-light">
                                {{ rtrim(rtrim(number_format($sentQty, 2), '0'), '.') }}
                            </td>
                            <td class="text-center text-success fw-bold">
                                {{ rtrim(rtrim(number_format($item->qty_ok, 2), '0'), '.') }}
                            </td>
                            <td class="text-center text-warning">
                                {{ $item->qty_danada > 0 ? rtrim(rtrim(number_format($item->qty_danada, 2), '0'), '.') : '-' }}
                            </td>
                            <td class="text-center text-danger fw-bold">
                                {{ $item->qty_quebrada > 0 ? rtrim(rtrim(number_format($item->qty_quebrada, 2), '0'), '.') : '-' }}
                            </td>
                            <td class="text-center text-danger fw-bold">
                                {{ $item->qty_faltante > 0 ? rtrim(rtrim(number_format($item->qty_faltante, 2), '0'), '.') : '-' }}
                            </td>
                            <td class="text-center text-info">
                                {{ $item->qty_consumida > 0 ? rtrim(rtrim(number_format($item->qty_consumida, 2), '0'), '.') : '-' }}
                            </td>
                            <td class="text-center">
                                @if($item->tiene_incidencia || ($item->qty_quebrada + $item->qty_faltante + $item->qty_danada) > 0)
                                    <span class="badge bg-danger">Incidencias</span>
                                @elseif($item->isReturnBalanced())
                                    <span class="badge bg-success">Conforme</span>
                                @else
                                    <span class="badge bg-warning text-dark">Descuadre</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Signatures Area -->
    <div class="card border-0 report-card">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-4">Firmas de Conformidad del Evento</h6>
            <div class="row text-center mt-5 pt-4">
                <div class="col-6">
                    <div style="border-top: 1px dashed #94a3b8; width: 80%; margin: 0 auto; padding-top: 8px;">
                        <strong class="d-block text-dark">{{ $checklist->user->nombre ?? 'Supervisor de Barra' }}</strong>
                        <span class="text-muted small">Supervisor de Operaciones / Barra</span>
                    </div>
                </div>
                <div class="col-6">
                    <div style="border-top: 1px dashed #94a3b8; width: 80%; margin: 0 auto; padding-top: 8px;">
                        <strong class="d-block text-dark">{{ $checklist->contract->client->nombres ?? 'Cliente Contratante' }}</strong>
                        <span class="text-muted small">Cliente / Responsable de Recepción</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
