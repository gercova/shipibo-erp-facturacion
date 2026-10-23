@extends('admin.layout')

@section('styles')
<style>
    /* Tablet & Mobile Friendly Ergonomics */
    .tablet-header-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
    }

    .touch-btn {
        min-height: 52px;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease-in-out;
        user-select: none;
        touch-action: manipulation;
    }

    .touch-btn:active {
        transform: scale(0.97);
    }

    .touch-btn-toggle {
        min-height: 58px;
        border-radius: 14px;
        border: 2px solid transparent;
        font-size: 1.05rem;
    }

    .touch-btn-llevado-inactive {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    .touch-btn-llevado-inactive:hover,
    .touch-btn-llevado-inactive:focus {
        background: #e2e8f0;
        color: #1e293b;
    }

    .touch-btn-llevado-active {
        background: #10b981 !important;
        color: #ffffff !important;
        border-color: #059669 !important;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
    }

    .touch-btn-devuelto-inactive {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    .touch-btn-devuelto-inactive:hover,
    .touch-btn-devuelto-inactive:focus {
        background: #e2e8f0;
        color: #1e293b;
    }

    .touch-btn-devuelto-active {
        background: #0284c7 !important;
        color: #ffffff !important;
        border-color: #0369a1 !important;
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
    }

    .item-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .item-card:hover {
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }

    .item-card.has-incident {
        border-left: 6px solid #ef4444 !important;
        background: #fffafa;
    }

    .item-card.all-done {
        border-left: 6px solid #10b981 !important;
    }

    .filter-pill-btn {
        min-height: 44px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.5rem 1.25rem;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        transition: all 0.2s;
    }

    .filter-pill-btn.active {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    }

    .badge-counter {
        font-size: 0.8rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
    }

    .progress-bar-animated-custom {
        transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Top Tablet Bar -->
    <div class="card tablet-header-card border-0 mb-3 p-3 p-md-4">
        @if($checklist->isReturnClosed())
            <div class="alert alert-success d-flex align-items-center justify-content-between p-3 rounded-3 mb-3 border-0 bg-success bg-opacity-25 text-white">
                <div>
                    <i class="ri-checkbox-circle-fill fs-4 me-2 text-success"></i>
                    <strong>RETORNO CERRADO:</strong> Finalizado el {{ $checklist->retorno_cerrado_at->format('d/m/Y H:i') }}
                    por <strong>{{ $checklist->retornoCerradoBy->nombre ?? 'Usuario' }}</strong>.
                    @if($checklist->isStockApplied())
                        <span class="badge bg-info text-dark ms-2"><i class="ri-archive-line me-1"></i> Stock / Kardex Actualizado</span>
                    @endif
                </div>
                @if(auth()->user()->hasRole(['SUPERADMIN', 'ADMIN']))
                    <button type="button" class="btn btn-outline-warning btn-sm fw-bold px-3 py-1 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalReopenReturn">
                        <i class="ri-restart-line me-1"></i> Reabrir Retorno (Auditoría)
                    </button>
                @endif
            </div>
        @endif

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-primary fs-7 py-1 px-2">{{ $checklist->code }}</span>
                    @if($checklist->contract)
                        <span class="badge bg-secondary fs-7 py-1 px-2">Contrato: {{ $checklist->contract->contract_number }}</span>
                    @endif
                    <span id="badge-checklist-status">{!! $checklist->status_badge !!}</span>
                </div>
                <h2 class="h4 text-white fw-bold mb-1">
                    <i class="ri-survey-line text-info me-1"></i> Control de Despacho y Retorno para Evento
                </h2>
                <div class="text-white-50 small">
                    <span><i class="ri-user-star-line me-1 text-warning"></i> <strong>Cliente:</strong> {{ $checklist->contract->client->nombres ?? 'No especificado' }}</span>
                    <span class="mx-2">•</span>
                    <span><i class="ri-calendar-event-line me-1 text-info"></i> <strong>Fecha:</strong> {{ $checklist->contract ? \Carbon\Carbon::parse($checklist->contract->fecha_evento)->format('d/m/Y') : '-' }} {{ $checklist->contract?->hora_evento ? \Carbon\Carbon::parse($checklist->contract->hora_evento)->format('H:i') : '' }}</span>
                    @if($checklist->contract?->lugar_evento)
                        <span class="mx-2 d-none d-lg-inline">•</span>
                        <span class="d-block d-lg-inline mt-1 mt-lg-0"><i class="ri-map-pin-line me-1 text-danger"></i> {{ $checklist->contract->lugar_evento }}</span>
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if(!$checklist->isReturnClosed())
                    <button type="button" class="btn btn-danger touch-btn fw-bold px-3" id="btn-open-close-modal">
                        <i class="ri-lock-2-line me-1 fs-5"></i>
                        <span>Cerrar Retorno</span>
                    </button>
                    <button type="button" class="btn btn-primary touch-btn fw-bold px-3" id="btn-save-draft-return">
                        <i class="ri-save-line me-1 fs-5"></i>
                        <span>Guardar</span>
                    </button>
                @endif
                <a href="{{ route('admin.event_checklists.report', $checklist->id) }}" class="btn btn-warning text-dark touch-btn">
                    <i class="ri-file-chart-line me-1 fs-5"></i>
                    <span>Acta de Cierre</span>
                </a>
                <a href="{{ route('admin.event_checklists.pdf', $checklist->id) }}" class="btn btn-light touch-btn" target="_blank">
                    <i class="ri-printer-line me-1 fs-5"></i>
                    <span>PDF</span>
                </a>
                <button type="button" class="btn btn-success touch-btn" data-bs-toggle="modal" data-bs-target="#modalAddItem">
                    <i class="ri-add-line me-1 fs-5"></i>
                    <span>Agregar Ítem</span>
                </button>
                <a href="{{ route('admin.event_checklists') }}" class="btn btn-outline-light touch-btn">
                    <i class="ri-arrow-go-back-line me-1 fs-5"></i>
                    <span>Salir</span>
                </a>
            </div>
        </div>

        <!-- Quick Live KPI Cards inside Banner -->
        <div class="row g-2 g-md-3 mt-2 pt-2 border-top border-secondary">
            <!-- Salida Progress -->
            <div class="col-12 col-md-4">
                <div class="bg-dark bg-opacity-50 p-2 p-md-3 rounded-3 border border-secondary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-white fw-bold"><i class="ri-truck-line text-emerald-400 me-1"></i> SALIDA / MONTAJE (Llevados)</span>
                        <span class="badge bg-success" id="counter-llevados">{{ $checklist->total_llevados }} / {{ $checklist->total_items }}</span>
                    </div>
                    <div class="progress progress-slim bg-secondary" style="height: 8px;">
                        <div id="progress-bar-llevado" class="progress-bar bg-success progress-bar-animated-custom" style="width: {{ $checklist->progressLlevado() }}%"></div>
                    </div>
                    <div class="text-end text-white-50 mt-1" style="font-size: 0.75rem;" id="pct-llevado">{{ $checklist->progressLlevado() }}% completado</div>
                </div>
            </div>

            <!-- Retorno Progress -->
            <div class="col-12 col-md-4">
                <div class="bg-dark bg-opacity-50 p-2 p-md-3 rounded-3 border border-secondary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-white fw-bold"><i class="ri-inbox-archive-line text-cyan-400 me-1"></i> RETORNO / DESMONTAJE (Devueltos)</span>
                        <span class="badge bg-info" id="counter-devueltos">{{ $checklist->total_devueltos }} / {{ $checklist->total_items }}</span>
                    </div>
                    <div class="progress progress-slim bg-secondary" style="height: 8px;">
                        <div id="progress-bar-devuelto" class="progress-bar bg-info progress-bar-animated-custom" style="width: {{ $checklist->progressDevuelto() }}%"></div>
                    </div>
                    <div class="text-end text-white-50 mt-1" style="font-size: 0.75rem;" id="pct-devuelto">{{ $checklist->progressDevuelto() }}% completado</div>
                </div>
            </div>

            <!-- Incidencias Alert -->
            <div class="col-12 col-md-4">
                <div class="bg-dark bg-opacity-50 p-2 p-md-3 rounded-3 border border-secondary d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-white fw-bold d-block"><i class="ri-error-warning-line text-warning me-1"></i> FALTANTES O ROTURAS</span>
                        <span class="text-white-50 small" id="incident-subtext">
                            {{ $checklist->total_incidencias > 0 ? 'Existen observaciones reportadas' : 'Sin incidencias registradas' }}
                        </span>
                    </div>
                    <div>
                        <span class="badge {{ $checklist->total_incidencias > 0 ? 'bg-danger fs-6' : 'bg-secondary' }} px-3 py-2" id="counter-incidencias">
                            {{ $checklist->total_incidencias }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Batch Action Toolbar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2 p-md-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <!-- Filters Tabs -->
                <div class="d-flex align-items-center gap-2 overflow-auto py-1" id="filter-tabs">
                    <button type="button" class="filter-pill-btn active" data-filter="all">
                        Todos <span class="badge bg-secondary badge-counter ms-1" id="tab-count-all">{{ $checklist->items->count() }}</span>
                    </button>
                    <button type="button" class="filter-pill-btn" data-filter="pending-llevado">
                        Por Llevar <span class="badge bg-warning text-dark badge-counter ms-1" id="tab-count-pending-llevado">{{ $checklist->items->where('llevado', 0)->count() }}</span>
                    </button>
                    <button type="button" class="filter-pill-btn" data-filter="pending-devuelto">
                        Por Devolver <span class="badge bg-warning text-dark badge-counter ms-1" id="tab-count-pending-devuelto">{{ $checklist->items->where('devuelto', 0)->count() }}</span>
                    </button>
                    <button type="button" class="filter-pill-btn" data-filter="with-incident">
                        Con Incidencias <span class="badge bg-danger badge-counter ms-1" id="tab-count-incidents">{{ $checklist->items->where('tiene_incidencia', 1)->count() }}</span>
                    </button>
                </div>

                <!-- Batch Actions for Fast Operation -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if(!$checklist->isReturnClosed())
                        <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3 py-2 rounded-pill btn-bulk-action" data-action="all_llevados">
                            <i class="ri-truck-line me-1"></i> Todo Salida
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2 rounded-pill btn-bulk-return-ok" title="Asignar todo el retorno como conforme (OK)">
                            <i class="ri-check-double-line me-1"></i> Todo Retorno OK
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm fw-bold px-3 py-2 rounded-pill btn-bulk-consumables" title="Asignar insumos y licores como consumidos">
                            <i class="ri-goblet-line me-1"></i> Consumibles a Consumido
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Items Grid / List -->
    <div class="row g-3" id="items-container">
        @forelse($checklist->items as $item)
            @php
                $isAllDone = ($item->llevado == 1 && $item->devuelto == 1 && !$item->tiene_incidencia);
                $hasIncident = ($item->tiene_incidencia == 1);
            @endphp
            <div class="col-12 item-wrapper"
                 id="item-wrapper-{{ $item->id }}"
                 data-item-id="{{ $item->id }}"
                 data-llevado="{{ $item->llevado }}"
                 data-devuelto="{{ $item->devuelto }}"
                 data-incident="{{ $item->tiene_incidencia }}">
                <div class="item-card p-3 p-md-4 {{ $hasIncident ? 'has-incident' : ($isAllDone ? 'all-done' : '') }}">
                    <div class="row align-items-center g-3">
                        
                        <!-- Col 1: Item Details -->
                        <div class="col-12 col-lg-5">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-light text-dark border px-2 py-1 fs-6 fw-bold">
                                    {{ rtrim(rtrim(number_format($item->cantidad, 2), '0'), '.') }} {{ $item->unidad_medida }}
                                </span>
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark">{{ $item->descripcion }}</h5>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-secondary border me-1">{{ $item->categoria ?? 'Insumos / Equipos' }}</span>
                                        @if($item->producto_id)
                                            <span class="badge bg-light text-muted">ID: {{ $item->producto_id }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Incident Banner if exists -->
                            <div class="incident-box mt-2 p-2 rounded-3 bg-danger bg-opacity-10 border border-danger text-danger small {{ $hasIncident ? '' : 'd-none' }}" id="incident-box-{{ $item->id }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><i class="ri-alert-fill me-1"></i> <span id="incident-type-{{ $item->id }}">{{ $item->tipo_incidencia }}</span>: <span id="incident-qty-{{ $item->id }}">{{ rtrim(rtrim(number_format($item->cantidad_afectada, 2), '0'), '.') }}</span> {{ $item->unidad_medida }}</strong>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 fw-bold btn-open-incident" data-item-id="{{ $item->id }}">Editar</button>
                                </div>
                                <div class="text-dark mt-1 fst-italic" id="incident-notes-{{ $item->id }}">{{ $item->observaciones ?? 'Sin detalle adicional' }}</div>
                            </div>
                        </div>

                        <!-- Col 2: Button LLEVADO (Salida) -->
                        <div class="col-6 col-lg-3">
                            <button type="button"
                                    class="btn w-100 touch-btn touch-btn-toggle btn-toggle-llevado {{ $item->llevado ? 'touch-btn-llevado-active' : 'touch-btn-llevado-inactive' }}"
                                    data-item-id="{{ $item->id }}"
                                    data-current="{{ $item->llevado }}"
                                    id="btn-llevado-{{ $item->id }}">
                                <div class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <i class="{{ $item->llevado ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line' }} fs-5"></i>
                                        <span class="btn-text">{{ $item->llevado ? 'LLEVADO' : 'POR LLEVAR' }}</span>
                                    </div>
                                    <div class="text-xs audit-text fw-normal" style="font-size: 0.72rem; opacity: 0.85;" id="audit-llevado-{{ $item->id }}">
                                        @if($item->llevado && $item->fecha_llevado)
                                            {{ \Carbon\Carbon::parse($item->fecha_llevado)->format('d/m H:i') }} ({{ $item->userLlevado->nombre ?? 'Usuario' }})
                                        @else
                                            Salida / Montaje
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Col 3: Button DEVUELTO (Retorno) -->
                        <div class="col-6 col-lg-3">
                            <button type="button"
                                    class="btn w-100 touch-btn touch-btn-toggle btn-toggle-devuelto {{ $item->devuelto ? 'touch-btn-devuelto-active' : 'touch-btn-devuelto-inactive' }}"
                                    data-item-id="{{ $item->id }}"
                                    data-current="{{ $item->devuelto }}"
                                    id="btn-devuelto-{{ $item->id }}">
                                <div class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <i class="{{ $item->devuelto ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line' }} fs-5"></i>
                                        <span class="btn-text">{{ $item->devuelto ? 'DEVUELTO' : 'POR DEVOLVER' }}</span>
                                    </div>
                                    <div class="text-xs audit-text fw-normal" style="font-size: 0.72rem; opacity: 0.85;" id="audit-devuelto-{{ $item->id }}">
                                        @if($item->devuelto && $item->fecha_devuelto)
                                            {{ \Carbon\Carbon::parse($item->fecha_devuelto)->format('d/m H:i') }} ({{ $item->userDevuelto->nombre ?? 'Usuario' }})
                                        @else
                                            Retorno / Desmontaje
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Col 4: Incident Action Button -->
                        <div class="col-12 col-lg-1 text-end">
                            <button type="button"
                                    class="btn btn-outline-danger touch-btn w-100 px-2 btn-open-incident {{ $hasIncident ? 'bg-danger text-white' : '' }}"
                                    data-item-id="{{ $item->id }}"
                                    data-descripcion="{{ $item->descripcion }}"
                                    data-max-qty="{{ $item->cantidad }}"
                                    data-unidad="{{ $item->unidad_medida }}"
                                    data-incident-type="{{ $item->tipo_incidencia }}"
                                    data-incident-qty="{{ $item->cantidad_afectada }}"
                                    data-incident-notes="{{ $item->observaciones }}"
                                    title="Reportar rotura, faltante o daño">
                                <i class="ri-alert-line fs-5"></i>
                                <span class="d-lg-none ms-1">Incidencia</span>
                            </button>
                        </div>
                    </div>
                    <!-- Panel Desglosado de Retorno (OK, Dañado, Roto, Faltante, Consumido) -->
                    @php
                        $sentQty        = (float) ($item->qty_enviada > 0 ? $item->qty_enviada : ($item->cantidad_llevada > 0 ? $item->cantidad_llevada : $item->cantidad_planeada));
                        $effectiveCost  = $item->product ? $item->product->effective_replacement_cost : (float) $item->costo_penalidad_estimado;
                        $isRentable     = (bool) ($item->product?->rentable);
                        $isService      = (int) ($item->product?->opcion) === 2;
                    @endphp
                    <div class="mt-3 pt-3 border-top bg-light p-3 rounded-3 return-breakdown-panel"
                         data-item-id="{{ $item->id }}"
                         data-sent-qty="{{ $sentQty }}"
                         data-unit-cost="{{ $effectiveCost }}"
                         data-is-rentable="{{ $isRentable ? '1' : '0' }}"
                         data-is-service="{{ $isService ? '1' : '0' }}">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-dark fs-7 px-2 py-1">Enviado: <strong class="item-sent-display">{{ rtrim(rtrim(number_format($sentQty, 2), '0'), '.') }}</strong> {{ $item->unidad_medida }}</span>
                                <span class="badge item-balance-badge bg-secondary fs-7 px-2 py-1" id="balance-badge-{{ $item->id }}">Calculando...</span>
                                @if($isRentable)
                                    <span class="badge bg-secondary-subtle text-dark border"><i class="ri-tools-line me-1"></i> Alquiler / Menaje</span>
                                @elseif(!$isService)
                                    <span class="badge bg-info-subtle text-info border"><i class="ri-goblet-line me-1"></i> Insumo / Licor</span>
                                @endif
                                @if($effectiveCost > 0)
                                    <span class="badge bg-light text-muted border">Reposición: {{ $signo }} {{ number_format($effectiveCost, 2) }}</span>
                                @endif
                            </div>
                            @if(!$checklist->isReturnClosed())
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 btn-item-all-ok" data-item-id="{{ $item->id }}" title="Marcar todo como Conforme (OK)">
                                        <i class="ri-check-line me-1"></i> Todo OK
                                    </button>
                                    @if(!$isRentable && !$isService)
                                    <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 btn-item-all-consumed" data-item-id="{{ $item->id }}" title="Marcar todo como Consumido">
                                        <i class="ri-drop-line me-1"></i> Todo Consumido
                                    </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <!-- Stepper Counters Grid for 5 Categories -->
                        <div class="row g-2 text-center">
                            <!-- Qty OK -->
                            <div class="col-6 col-md">
                                <label class="form-label text-success small fw-bold mb-1"><i class="ri-checkbox-circle-line me-1"></i> OK (Conforme)</label>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-step-down" data-target="#qty-ok-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>-</button>
                                    <input type="number" step="0.01" min="0" class="form-control text-center fw-bold return-qty-input qty-ok-input" id="qty-ok-{{ $item->id }}" name="items[{{ $item->id }}][qty_ok]" value="{{ (float) $item->qty_ok }}" {{ $checklist->isReturnClosed() ? 'readonly' : '' }}>
                                    <button type="button" class="btn btn-outline-secondary btn-step-up" data-target="#qty-ok-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>+</button>
                                </div>
                            </div>
                            <!-- Qty Danada -->
                            <div class="col-6 col-md">
                                <label class="form-label text-warning small fw-bold mb-1"><i class="ri-tools-line me-1"></i> Dañado (Reparar)</label>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-step-down" data-target="#qty-danada-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>-</button>
                                    <input type="number" step="0.01" min="0" class="form-control text-center fw-bold return-qty-input qty-danada-input" id="qty-danada-{{ $item->id }}" name="items[{{ $item->id }}][qty_danada]" value="{{ (float) $item->qty_danada }}" {{ $checklist->isReturnClosed() ? 'readonly' : '' }}>
                                    <button type="button" class="btn btn-outline-secondary btn-step-up" data-target="#qty-danada-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>+</button>
                                </div>
                            </div>
                            <!-- Qty Quebrada -->
                            <div class="col-6 col-md">
                                <label class="form-label text-danger small fw-bold mb-1"><i class="ri-close-circle-line me-1"></i> Roto / Quebrado</label>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-step-down" data-target="#qty-quebrada-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>-</button>
                                    <input type="number" step="0.01" min="0" class="form-control text-center fw-bold text-danger return-qty-input qty-quebrada-input" id="qty-quebrada-{{ $item->id }}" name="items[{{ $item->id }}][qty_quebrada]" value="{{ (float) $item->qty_quebrada }}" {{ $checklist->isReturnClosed() ? 'readonly' : '' }}>
                                    <button type="button" class="btn btn-outline-secondary btn-step-up" data-target="#qty-quebrada-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>+</button>
                                </div>
                            </div>

                            <!-- Qty Faltante -->
                            <div class="col-6 col-md">
                                <label class="form-label text-danger small fw-bold mb-1"><i class="ri-search-eye-line me-1"></i> Faltante / Perdido</label>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-step-down" data-target="#qty-faltante-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>-</button>
                                    <input type="number" step="0.01" min="0" class="form-control text-center fw-bold text-danger return-qty-input qty-faltante-input" id="qty-faltante-{{ $item->id }}" name="items[{{ $item->id }}][qty_faltante]" value="{{ (float) $item->qty_faltante }}" {{ $checklist->isReturnClosed() ? 'readonly' : '' }}>
                                    <button type="button" class="btn btn-outline-secondary btn-step-up" data-target="#qty-faltante-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>+</button>
                                </div>
                            </div>

                            <!-- Qty Consumida -->
                            <div class="col-12 col-md">
                                <label class="form-label text-info small fw-bold mb-1"><i class="ri-goblet-line me-1"></i> Consumido</label>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-step-down" data-target="#qty-consumida-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>-</button>
                                    <input type="number" step="0.01" min="0" class="form-control text-center fw-bold text-info return-qty-input qty-consumida-input" id="qty-consumida-{{ $item->id }}" name="items[{{ $item->id }}][qty_consumida]" value="{{ (float) $item->qty_consumida }}" {{ $checklist->isReturnClosed() ? 'readonly' : '' }}>
                                    <button type="button" class="btn btn-outline-secondary btn-step-up" data-target="#qty-consumida-{{ $item->id }}" {{ $checklist->isReturnClosed() ? 'disabled' : '' }}>+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="text-muted fs-5 mb-3">No hay ítems registrados en este checklist.</div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddItem">
                    <i class="ri-add-line me-1"></i> Agregar primer ítem
                </button>
            </div>
        @endforelse
    </div>
</div>
<!-- Modal Report Incident -->
<div class="modal fade" id="modalIncident" tabindex="-1" aria-labelledby="modalIncidentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalIncidentLabel">
                    <i class="ri-alert-line me-1"></i> Reportar Incidencia / Faltante
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="incident-item-id" />

                <div class="alert alert-light border mb-3">
                    <div class="text-muted small">Ítem Seleccionado:</div>
                    <h6 class="fw-bold mb-0 text-dark" id="modal-incident-item-name"></h6>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Tipo de Incidencia</label>
                    <select class="form-select form-select-lg" id="modal-incident-type">
                        <option value="Rotura de Cristalería">Rotura de Cristalería</option>
                        <option value="Pérdida / Extravío">Pérdida / Extravío</option>
                        <option value="Daño de Equipamiento / Herramienta">Daño de Equipamiento / Herramienta</option>
                        <option value="Faltante de Insumo">Faltante de Insumo</option>
                        <option value="Devolución Incompleta">Devolución Incompleta</option>
                        <option value="Otro">Otro Incidente</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Cantidad Afectada (<span id="modal-incident-unit">UNIDAD</span>)</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-lg fw-bold" id="modal-incident-qty" value="1" />
                    <small class="text-muted">Cantidad máxima registrada: <span id="modal-incident-max-qty">1</span></small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Observaciones / Motivo detallado</label>
                    <textarea class="form-control" id="modal-incident-notes" rows="3" placeholder="Ej: Se rompieron 2 copas martini durante el brindis por caída de bandeja..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning text-dark" id="btn-clear-incident">
                    <i class="ri-delete-bin-line me-1"></i> Quitar Incidencia (Subsanado)
                </button>
                <button type="button" class="btn btn-danger fw-bold" id="btn-save-incident">
                    <i class="ri-save-line me-1"></i> Guardar Incidencia
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Quick Add Item -->
<div class="modal fade" id="modalAddItem" tabindex="-1" aria-labelledby="modalAddItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalAddItemLabel">
                    <i class="ri-add-circle-line me-1"></i> Agregar Ítem al Checklist
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddItem">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Descripción del Ítem / Cristalería / Insumo *</label>
                        <input type="text" name="descripcion" class="form-control form-control-lg" placeholder="Ej: Vaso Highball, Hielo adicional, Licuadora bar" required />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark">Cantidad *</label>
                            <input type="number" name="cantidad" step="0.01" min="0.01" value="1" class="form-control form-control-lg" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark">Unidad de Medida</label>
                            <input type="text" name="unidad_medida" value="UNIDAD" class="form-control form-control-lg" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="Cristalería">Cristalería (Copas, Vasos)</option>
                            <option value="Herramientas de Barra">Herramientas de Barra (Shakers, Jiggers)</option>
                            <option value="Insumos / Bebidas">Insumos / Bebidas / Hielo</option>
                            <option value="Mobiliario / Barras">Mobiliario / Barras Móviles</option>
                            <option value="Equipamiento">Equipamiento Eléctrico</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Nota / Observación adicional</label>
                        <input type="text" name="observaciones" class="form-control" placeholder="Opcional..." />
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold" id="btn-submit-add-item">
                        <i class="ri-check-line me-1"></i> Agregar al Checklist
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Cerrar Retorno e Impactar Stock -->
<div class="modal fade" id="modalCloseReturn" tabindex="-1" aria-labelledby="modalCloseReturnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalCloseReturnLabel">
                    <i class="ri-lock-2-line me-1"></i> Confirmar Cierre de Retorno e Impacto en Inventario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCloseReturn" method="POST" action="{{ route('admin.event_checklists.close_return', $checklist->id) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-2 mb-3">
                        <i class="ri-alert-line fs-4 text-warning"></i>
                        <div class="small">
                            <strong>¡Atención!</strong> Esta acción es irreversible para operadores estándar:
                            <ul class="mb-0 ps-3 mt-1">
                                <li>Se aplicará la <strong>baja permanente</strong> de stock en almacén para ítems rotos, extraviados o consumidos.</li>
                                <li>Se generarán <strong>ajustes de penalidad sugeridos</strong> para ítems con roturas, daños o pérdidas.</li>
                                <li>El checklist quedará <strong>bloqueado</strong> contra futuras ediciones directas.</li>
                            </ul>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2"><i class="ri-file-list-3-line me-1"></i> Resumen de Novedades y Penalidades:</h6>
                    <div id="close-return-preview-container" class="table-responsive border rounded mb-3 bg-light p-2" style="max-height: 260px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" id="preview-return-table">
                            <thead class="table-light">
                                <tr class="text-muted small">
                                    <th>Ítem</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Roto/Faltante</th>
                                    <th class="text-center">Dañado</th>
                                    <th class="text-center">Consumido</th>
                                    <th class="text-end">Penalidad Est.</th>
                                </tr>
                            </thead>
                            <tbody id="preview-return-rows">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="card border-danger-subtle bg-danger-subtle p-3 rounded-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-danger fw-bold fs-6">Total Penalidades Sugeridas:</span>
                                <div class="text-muted small">Monto que el supervisor podrá cargar a la 2da cuota o emitir Nota de Débito</div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bolder text-danger" id="preview-total-penalty">{{ $signo }} 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar / Seguir editando</button>
                    <button type="submit" class="btn btn-danger fw-bold" id="btn-submit-close-return">
                        <i class="ri-check-double-line me-1"></i> Confirmar Cierre y Descontar Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reabrir Retorno -->
<div class="modal fade" id="modalReopenReturn" tabindex="-1" aria-labelledby="modalReopenReturnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="modalReopenReturnLabel">
                    <i class="ri-restart-line me-1"></i> Reabrir Retorno (Auditoría Administrativa)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formReopenReturn" method="POST" action="{{ route('admin.event_checklists.reopen_return', $checklist->id) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-3">
                        <i class="ri-information-line me-1"></i>
                        Al reabrir el checklist, se revertirán los movimientos de inventario y ajustes generados para permitir corregir cantidades.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Motivo de Reapertura *</label>
                        <textarea name="motivo_reapertura" id="modal-motivo-reapertura" class="form-control" rows="3" placeholder="Explique por qué se reabre el retorno (mínimo 5 caracteres)..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" id="btn-submit-reopen-return">
                        <i class="ri-restart-line me-1"></i> Confirmar Reapertura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const checklistId       = {{ $checklist->id }};
    const updateItemUrl     = "{{ route('admin.event_checklists.update_item', $checklist->id) }}";
    const bulkToggleUrl     = "{{ route('admin.event_checklists.bulk_toggle', $checklist->id) }}";
    const addItemUrl        = "{{ route('admin.event_checklists.add_item', $checklist->id) }}";

    // Live update UI elements from response counters
    function updateCounters(counters) {
        if (!counters) return;

        $('#counter-llevados').text(counters.total_llevados + ' / ' + counters.total_items);
        $('#counter-devueltos').text(counters.total_devueltos + ' / ' + counters.total_items);
        $('#counter-incidencias').text(counters.total_incidencias);

        $('#progress-bar-llevado').css('width', counters.pct_llevado + '%');
        $('#pct-llevado').text(counters.pct_llevado + '% completado');

        $('#progress-bar-devuelto').css('width', counters.pct_devuelto + '%');
        $('#pct-devuelto').text(counters.pct_devuelto + '% completado');

        $('#badge-checklist-status').html(counters.status_badge);

        if (counters.total_incidencias > 0) {
            $('#counter-incidencias').removeClass('bg-secondary').addClass('bg-danger fs-6');
            $('#incident-subtext').text('Existen observaciones reportadas');
        } else {
            $('#counter-incidencias').removeClass('bg-danger fs-6').addClass('bg-secondary');
            $('#incident-subtext').text('Sin incidencias registradas');
        }

        // Update tab count badges
        $('#tab-count-all').text($('.item-wrapper').length);
        $('#tab-count-pending-llevado').text($('.item-wrapper[data-llevado="0"]').length);
        $('#tab-count-pending-devuelto').text($('.item-wrapper[data-devuelto="0"]').length);
        $('#tab-count-incidents').text($('.item-wrapper[data-incident="1"]').length);
    }

    // Toggle LLEVADO
    $(document).on('click', '.btn-toggle-llevado', function(e) {
        e.preventDefault();
        const btn           = $(this);
        const itemId        = btn.data('item-id');
        const currentVal    = parseInt(btn.data('current')) || 0;
        const newVal        = currentVal === 1 ? 0 : 1;

        btn.prop('disabled', true);

        $.ajax({
            url: updateItemUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                field: 'llevado',
                value: newVal
            },
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status) {
                    btn.data('current', newVal);
                    const wrapper = $('#item-wrapper-' + itemId);
                    wrapper.attr('data-llevado', newVal);

                    if (newVal === 1) {
                        btn.removeClass('touch-btn-llevado-inactive').addClass('touch-btn-llevado-active');
                        btn.find('.btn-text').text('LLEVADO');
                        btn.find('i').removeClass('ri-checkbox-blank-circle-line').addClass('ri-checkbox-circle-fill');
                        btn.find('.audit-text').text(res.item.fecha_llevado_formatted + ' (' + (res.item.user_llevado_name || 'Tú') + ')');
                    } else {
                        btn.removeClass('touch-btn-llevado-active').addClass('touch-btn-llevado-inactive');
                        btn.find('.btn-text').text('POR LLEVAR');
                        btn.find('i').removeClass('ri-checkbox-circle-fill').addClass('ri-checkbox-blank-circle-line');
                        btn.find('.audit-text').text('Salida / Montaje');
                    }

                    // Card border styling
                    checkCardCompletion(itemId);
                    updateCounters(res.counters);
                    toastr.success(newVal === 1 ? 'Ítem marcado como LLEVADO' : 'Ítem desmarcado');
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false);
                toastr.error('Error al actualizar el ítem.');
            }
        });
    });

    // Toggle DEVUELTO
    $(document).on('click', '.btn-toggle-devuelto', function(e) {
        e.preventDefault();
        const btn           = $(this);
        const itemId        = btn.data('item-id');
        const currentVal    = parseInt(btn.data('current')) || 0;
        const newVal        = currentVal === 1 ? 0 : 1;

        btn.prop('disabled', true);

        $.ajax({
            url: updateItemUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                field: 'devuelto',
                value: newVal
            },
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status) {
                    btn.data('current', newVal);
                    const wrapper = $('#item-wrapper-' + itemId);
                    wrapper.attr('data-devuelto', newVal);

                    if (newVal === 1) {
                        btn.removeClass('touch-btn-devuelto-inactive').addClass('touch-btn-devuelto-active');
                        btn.find('.btn-text').text('DEVUELTO');
                        btn.find('i').removeClass('ri-checkbox-blank-circle-line').addClass('ri-checkbox-circle-fill');
                        btn.find('.audit-text').text(res.item.fecha_devuelto_formatted + ' (' + (res.item.user_devuelto_name || 'Tú') + ')');
                    } else {
                        btn.removeClass('touch-btn-devuelto-active').addClass('touch-btn-devuelto-inactive');
                        btn.find('.btn-text').text('POR DEVOLVER');
                        btn.find('i').removeClass('ri-checkbox-circle-fill').addClass('ri-checkbox-blank-circle-line');
                        btn.find('.audit-text').text('Retorno / Desmontaje');
                    }

                    checkCardCompletion(itemId);
                    updateCounters(res.counters);
                    toastr.info(newVal === 1 ? 'Ítem verificado como DEVUELTO' : 'Retorno desmarcado');
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false);
                toastr.error('Error al actualizar el retorno.');
            }
        });
    });

    function checkCardCompletion(itemId) {
        const wrapper   = $('#item-wrapper-' + itemId);
        const card      = wrapper.find('.item-card');
        const llevado   = parseInt(wrapper.attr('data-llevado')) || 0;
        const devuelto  = parseInt(wrapper.attr('data-devuelto')) || 0;
        const incident  = parseInt(wrapper.attr('data-incident')) || 0;

        if (incident === 1) {
            card.addClass('has-incident').removeClass('all-done');
        } else if (llevado === 1 && devuelto === 1) {
            card.addClass('all-done').removeClass('has-incident');
        } else {
            card.removeClass('all-done has-incident');
        }
    }

    // Filter Tabs Handler
    $('.filter-pill-btn').on('click', function() {
        $('.filter-pill-btn').removeClass('active');
        $(this).addClass('active');

        const filter = $(this).data('filter');

        $('.item-wrapper').each(function() {
            const llevado = parseInt($(this).attr('data-llevado')) || 0;
            const devuelto = parseInt($(this).attr('data-devuelto')) || 0;
            const incident = parseInt($(this).attr('data-incident')) || 0;

            if (filter === 'all') {
                $(this).show();
            } else if (filter === 'pending-llevado') {
                llevado === 0 ? $(this).show() : $(this).hide();
            } else if (filter === 'pending-devuelto') {
                devuelto === 0 ? $(this).show() : $(this).hide();
            } else if (filter === 'with-incident') {
                incident === 1 ? $(this).show() : $(this).hide();
            }
        });
    });

    // Bulk Toggle
    $('.btn-bulk-action').on('click', function() {
        const action    = $(this).data('action');
        const title     = action === 'all_llevados' ? '¿Marcar TODO como LLEVADO?' : '¿Marcar TODO como DEVUELTO?';
        const text      = action === 'all_llevados'
            ? 'Todos los ítems se marcarán como despachados para el evento.'
            : 'Todos los ítems se marcarán como retornados del evento.';

        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    url: bulkToggleUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        action: action
                    },
                    success: function(resp) {
                        if (resp.status) {
                            toastr.success(resp.message);
                            location.reload();
                        } else {
                            toastr.error(resp.message);
                        }
                    },
                    error: function() {
                        toastr.error('Error al ejecutar la acción masiva.');
                    }
                });
            }
        });
    });

    // Open Incident Modal
    $(document).on('click', '.btn-open-incident', function(e) {
        e.preventDefault();
        const itemId        = $(this).data('item-id');
        const wrapper       = $('#item-wrapper-' + itemId);
        const desc          = $(this).data('descripcion') || wrapper.find('h5').text();
        const maxQty        = $(this).data('max-qty') || 1;
        const unit          = $(this).data('unidad') || 'UNIDAD';
        const incidentType  = $(this).data('incident-type') || 'Rotura de Cristalería';
        const incidentQty   = $(this).data('incident-qty') || 1;
        const notes         = $(this).data('incident-notes') || '';

        $('#incident-item-id').val(itemId);
        $('#modal-incident-item-name').text(desc);
        $('#modal-incident-unit').text(unit);
        $('#modal-incident-max-qty').text(maxQty);
        $('#modal-incident-qty').val(incidentQty).attr('max', maxQty);
        $('#modal-incident-type').val(incidentType);
        $('#modal-incident-notes').val(notes);

        $('#modalIncident').modal('show');
    });

    // Save Incident
    $('#btn-save-incident').on('click', function() {
        const itemId = $('#incident-item-id').val();
        const tipo = $('#modal-incident-type').val();
        const qty = parseFloat($('#modal-incident-qty').val()) || 1;
        const notes = $('#modal-incident-notes').val();

        $(this).prop('disabled', true);

        $.ajax({
            url: updateItemUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                field: 'incident',
                tipo_incidencia: tipo,
                cantidad_afectada: qty,
                observaciones: notes
            },
            success: function(res) {
                $('#btn-save-incident').prop('disabled', false);
                if (res.status) {
                    $('#modalIncident').modal('hide');
                    toastr.warning('Incidencia registrada.');

                    // Update wrapper DOM
                    const wrapper = $('#item-wrapper-' + itemId);
                    wrapper.attr('data-incident', '1');
                    $('#incident-box-' + itemId).removeClass('d-none');
                    $('#incident-type-' + itemId).text(tipo);
                    $('#incident-qty-' + itemId).text(qty);
                    $('#incident-notes-' + itemId).text(notes || 'Sin detalle adicional');

                    checkCardCompletion(itemId);
                    updateCounters(res.counters);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                $('#btn-save-incident').prop('disabled', false);
                toastr.error('Error al guardar incidencia.');
            }
        });
    });

    // Clear Incident
    $('#btn-clear-incident').on('click', function() {
        const itemId = $('#incident-item-id').val();

        $(this).prop('disabled', true);

        $.ajax({
            url: updateItemUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                field: 'incident_clear'
            },
            success: function(res) {
                $('#btn-clear-incident').prop('disabled', false);
                if (res.status) {
                    $('#modalIncident').modal('hide');
                    toastr.success('Incidencia removida/subsanada.');

                    const wrapper = $('#item-wrapper-' + itemId);
                    wrapper.attr('data-incident', '0');
                    $('#incident-box-' + itemId).addClass('d-none');

                    checkCardCompletion(itemId);
                    updateCounters(res.counters);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                $('#btn-clear-incident').prop('disabled', false);
                toastr.error('Error al limpiar incidencia.');
            }
        });
    });

    // Add Item Form Submit
    $('#formAddItem').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#btn-submit-add-item');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: addItemUrl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                submitBtn.prop('disabled', false);
                if (res.status) {
                    toastr.success(res.message);
                    $('#modalAddItem').modal('hide');
                    location.reload();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                submitBtn.prop('disabled', false);
                toastr.error('Error al agregar el ítem.');
            }
        });
    });

    // Stepper Handlers for Return Quantities
    $(document).on('click', '.btn-step-up', function(e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        const input = $(targetId);
        if (input.prop('readonly') || input.prop('disabled')) return;
        let currentVal = parseFloat(input.val()) || 0;
        currentVal = Math.round((currentVal + 1) * 100) / 100;
        input.val(currentVal).trigger('input');
    });

    $(document).on('click', '.btn-step-down', function(e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        const input = $(targetId);
        if (input.prop('readonly') || input.prop('disabled')) return;
        let currentVal = parseFloat(input.val()) || 0;
        currentVal = Math.max(0, Math.round((currentVal - 1) * 100) / 100);
        input.val(currentVal).trigger('input');
    });

    // Real-time Balance Calculation per Item
    function updateItemBalance(panel) {
        const itemId = panel.data('item-id');
        const sentQty = parseFloat(panel.data('sent-qty')) || 0;
        
        const qOk        = parseFloat(panel.find('.qty-ok-input').val()) || 0;
        const qDanada    = parseFloat(panel.find('.qty-danada-input').val()) || 0;
        const qQuebrada  = parseFloat(panel.find('.qty-quebrada-input').val()) || 0;
        const qFaltante  = parseFloat(panel.find('.qty-faltante-input').val()) || 0;
        const qConsumida = parseFloat(panel.find('.qty-consumida-input').val()) || 0;

        const sum = qOk + qDanada + qQuebrada + qFaltante + qConsumida;
        const diff = Math.round((sentQty - sum) * 10000) / 10000;
        const badge = $('#balance-badge-' + itemId);

        if (Math.abs(diff) < 0.0001) {
            badge.removeClass('bg-secondary bg-warning bg-danger text-dark')
                 .addClass('bg-success text-white')
                 .html('<i class="ri-checkbox-circle-line me-1"></i> Balanceado (' + sum.toFixed(2) + ')');
            panel.attr('data-balanced', '1');
        } else if (diff > 0.0001) {
            badge.removeClass('bg-secondary bg-success bg-danger text-white')
                 .addClass('bg-warning text-dark')
                 .html('<i class="ri-alert-line me-1"></i> Falta: ' + diff.toFixed(2));
            panel.attr('data-balanced', '0');
        } else {
            badge.removeClass('bg-secondary bg-success bg-warning text-dark')
                 .addClass('bg-danger text-white')
                 .html('<i class="ri-close-circle-line me-1"></i> Exceso: ' + (-diff).toFixed(2));
            panel.attr('data-balanced', '0');
        }
    }

    // Trigger on input changes
    $(document).on('input change keyup', '.return-qty-input', function() {
        const panel = $(this).closest('.return-breakdown-panel');
        updateItemBalance(panel);
    });

    // Initial check for all panels on load
    $('.return-breakdown-panel').each(function() {
        updateItemBalance($(this));
    });

    // Individual Item Shortcuts
    $(document).on('click', '.btn-item-all-ok', function() {
        const itemId = $(this).data('item-id');
        const panel = $('.return-breakdown-panel[data-item-id="' + itemId + '"]');
        const sentQty = parseFloat(panel.data('sent-qty')) || 0;
        panel.find('.qty-ok-input').val(sentQty);
        panel.find('.qty-danada-input').val(0);
        panel.find('.qty-quebrada-input').val(0);
        panel.find('.qty-faltante-input').val(0);
        panel.find('.qty-consumida-input').val(0);
        updateItemBalance(panel);
    });

    $(document).on('click', '.btn-item-all-consumed', function() {
        const itemId = $(this).data('item-id');
        const panel = $('.return-breakdown-panel[data-item-id="' + itemId + '"]');
        const sentQty = parseFloat(panel.data('sent-qty')) || 0;
        panel.find('.qty-ok-input').val(0);
        panel.find('.qty-danada-input').val(0);
        panel.find('.qty-quebrada-input').val(0);
        panel.find('.qty-faltante-input').val(0);
        panel.find('.qty-consumida-input').val(sentQty);
        updateItemBalance(panel);
    });

    // Global Shortcuts
    $('.btn-bulk-return-ok').on('click', function() {
        Swal.fire({
            title: '¿Asignar todo el retorno como Conforme (OK)?',
            text: 'Todos los ítems tendrán sus cantidades devueltas en OK igual a lo enviado.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, asignar todo OK',
            cancelButtonText: 'Cancelar'
        }).then((res) => {
            if (res.isConfirmed) {
                $('.return-breakdown-panel').each(function() {
                    const panel = $(this);
                    const sentQty = parseFloat(panel.data('sent-qty')) || 0;
                    panel.find('.qty-ok-input').val(sentQty);
                    panel.find('.qty-danada-input').val(0);
                    panel.find('.qty-quebrada-input').val(0);
                    panel.find('.qty-faltante-input').val(0);
                    panel.find('.qty-consumida-input').val(0);
                    updateItemBalance(panel);
                });
                toastr.success('Todos los ítems se marcaron como retorno OK.');
            }
        });
    });

    $('.btn-bulk-consumables').on('click', function() {
        Swal.fire({
            title: '¿Asignar insumos y bebidas como consumidos?',
            text: 'Los ítems no alquilables (insumos, licores) se asignarán a "Consumido". Los menajes y equipos alquilables se mantendrán intactos.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Sí, aplicar',
            cancelButtonText: 'Cancelar'
        }).then((res) => {
            if (res.isConfirmed) {
                let count = 0;
                $('.return-breakdown-panel').each(function() {
                    const panel = $(this);
                    const isRentable = panel.data('is-rentable') == '1';
                    const isService = panel.data('is-service') == '1';
                    if (!isRentable && !isService) {
                        const sentQty = parseFloat(panel.data('sent-qty')) || 0;
                        panel.find('.qty-ok-input').val(0);
                        panel.find('.qty-danada-input').val(0);
                        panel.find('.qty-quebrada-input').val(0);
                        panel.find('.qty-faltante-input').val(0);
                        panel.find('.qty-consumida-input').val(sentQty);
                        updateItemBalance(panel);
                        count++;
                    }
                });
                toastr.success(count + ' insumos marcados como consumidos.');
            }
        });
    });

    // Helper to collect items data
    function collectReturnItemsPayload() {
        const payload = {
            _token: '{{ csrf_token() }}',
            items: {}
        };
        $('.return-breakdown-panel').each(function() {
            const panel = $(this);
            const id = panel.data('item-id');
            payload.items[id] = {
                qty_ok: parseFloat(panel.find('.qty-ok-input').val()) || 0,
                qty_danada: parseFloat(panel.find('.qty-danada-input').val()) || 0,
                qty_quebrada: parseFloat(panel.find('.qty-quebrada-input').val()) || 0,
                qty_faltante: parseFloat(panel.find('.qty-faltante-input').val()) || 0,
                qty_consumida: parseFloat(panel.find('.qty-consumida-input').val()) || 0
            };
        });
        return payload;
    }

    // Save Return Items Draft
    $('#btn-save-draft-return').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);
        const payload = collectReturnItemsPayload();

        $.ajax({
            url: "{{ route('admin.event_checklists.save_return_items', $checklist->id) }}",
            type: 'POST',
            data: payload,
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message || 'Error al guardar los ítems.');
                }
            },
            error: function() {
                btn.prop('disabled', false);
                toastr.error('Error al guardar las cantidades de retorno.');
            }
        });
    });

    // Open Close Modal with Preview and Validation
    $('#btn-open-close-modal').on('click', function() {
        let unbalancedCount = 0;
        $('.return-breakdown-panel').each(function() {
            if ($(this).attr('data-balanced') !== '1') {
                unbalancedCount++;
            }
        });

        if (unbalancedCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cantidades desbalanceadas',
                html: 'Hay <strong>' + unbalancedCount + '</strong> ítem(s) cuya suma de categorías no coincide exactamente con la cantidad enviada.<br><br>Por favor revisa las tarjetas marcadas en amarillo o rojo antes de cerrar el retorno.',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Build preview
        const rowsContainer = $('#preview-return-rows');
        rowsContainer.empty();
        let totalPenalty = 0;
        let changesCount = 0;

        $('.return-breakdown-panel').each(function() {
            const panel = $(this);
            const desc = panel.closest('.item-card').find('h5').text().trim();
            const unitCost = parseFloat(panel.data('unit-cost')) || 0;
            const isRentable = panel.data('is-rentable') == '1';
            const isService = panel.data('is-service') == '1';

            const qOk        = parseFloat(panel.find('.qty-ok-input').val()) || 0;
            const qDanada    = parseFloat(panel.find('.qty-danada-input').val()) || 0;
            const qQuebrada  = parseFloat(panel.find('.qty-quebrada-input').val()) || 0;
            const qFaltante  = parseFloat(panel.find('.qty-faltante-input').val()) || 0;
            const qConsumida = parseFloat(panel.find('.qty-consumida-input').val()) || 0;

            const penaltyUnits = qQuebrada + qFaltante + qDanada;
            const penaltySubtotal = penaltyUnits * unitCost;

            if (qDanada > 0 || qQuebrada > 0 || qFaltante > 0 || qConsumida > 0) {
                changesCount++;
                totalPenalty += penaltySubtotal;
                const typeLabel = isRentable ? '<span class="badge bg-secondary-subtle text-dark">Alquiler</span>' : (isService ? '<span class="badge bg-light text-muted">Servicio</span>' : '<span class="badge bg-info-subtle text-info">Insumo</span>');
                const row = `<tr>
                    <td><strong>${desc}</strong></td>
                    <td class="text-center">${typeLabel}</td>
                    <td class="text-center text-danger fw-bold">${qQuebrada + qFaltante > 0 ? (qQuebrada + qFaltante) : '-'}</td>
                    <td class="text-center text-warning fw-bold">${qDanada > 0 ? qDanada : '-'}</td>
                    <td class="text-center text-info fw-bold">${qConsumida > 0 ? qConsumida : '-'}</td>
                    <td class="text-end fw-bold">${penaltySubtotal > 0 ? '{{ $signo }} ' + penaltySubtotal.toFixed(2) : '-'}</td>
                </tr>`;
                rowsContainer.append(row);
            }
        });

        if (changesCount === 0) {
            rowsContainer.append('<tr><td colspan="6" class="text-center text-success py-3"><i class="ri-checkbox-circle-line me-1"></i> Todos los ítems retornaron conformes (100% OK). Sin penalidades ni bajas por rotura.</td></tr>');
        }

        $('#preview-total-penalty').text('{{ $signo }} ' + totalPenalty.toFixed(2));
        $('#modalCloseReturn').modal('show');
    });

    // Form Close Return submission
    $('#formCloseReturn').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#btn-submit-close-return');
        submitBtn.prop('disabled', true);

        // First auto-save the items to guarantee consistency
        const payload = collectReturnItemsPayload();
        $.ajax({
            url: "{{ route('admin.event_checklists.save_return_items', $checklist->id) }}",
            type: 'POST',
            data: payload,
            success: function() {
                // Now submit close-return
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(resp) {
                        if (resp.status) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Retorno Cerrado!',
                                text: 'El retorno ha sido cerrado y el inventario fue actualizado exitosamente.',
                                confirmButtonText: 'Ver Acta de Cierre'
                            }).then(() => {
                                window.location.href = "{{ route('admin.event_checklists.report', $checklist->id) }}";
                            });
                        } else {
                            submitBtn.prop('disabled', false);
                            Swal.fire({ icon: 'error', title: 'Error', text: resp.message || 'Error al cerrar el retorno.' });
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error inesperado al cerrar el retorno.';
                        Swal.fire({ icon: 'error', title: 'No se pudo cerrar', text: msg });
                    }
                });
            },
            error: function() {
                submitBtn.prop('disabled', false);
                toastr.error('Error al guardar datos previos al cierre.');
            }
        });
    });

    // Form Reopen Return submission
    $('#formReopenReturn').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#btn-submit-reopen-return');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(resp) {
                if (resp.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Retorno Reabierto',
                        text: resp.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    submitBtn.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.message || 'Error al reabrir el retorno.' });
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false);
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error inesperado al reabrir.';
                Swal.fire({ icon: 'error', title: 'No se pudo reabrir', text: msg });
            }
        });
    });
});
</script>
@endsection
