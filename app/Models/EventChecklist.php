<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventChecklist extends Model
{
    use HasFactory;

    protected $table = 'event_checklists';

    protected $fillable = [
        'contract_id',
        'codigo',
        'code',
        'titulo',
        'fecha_evento',
        'hora_evento',
        'lugar_evento',
        'responsable_montaje',
        'responsable_desmontaje',
        'estado',
        'total_items',
        'items_llevados',
        'items_devueltos',
        'items_con_incidencia',
        'observaciones_salida',
        'observaciones_retorno',
        'retorno_cerrado_at',
        'retorno_cerrado_by',
        'stock_aplicado_at',
        'reabierto_at',
        'reabierto_by',
        'motivo_reapertura',
        'shipment_guide_id',
        'idusuario',
        'idalmacen',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
        'estado' => 'integer',
        'total_items' => 'integer',
        'items_llevados' => 'integer',
        'items_devueltos' => 'integer',
        'items_con_incidencia' => 'integer',
        'retorno_cerrado_at' => 'datetime',
        'stock_aplicado_at' => 'datetime',
        'reabierto_at' => 'datetime',
    ];

    const STATUS_PLANNED = 0; // Borrador / Planificado

    const STATUS_DISPATCHED = 1; // En Montaje / Salida Verificada

    const STATUS_RETURNED_OK = 2; // Conforme / Completado

    const STATUS_WITH_INCIDENTS = 3; // Con Incidencias / Daños

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EventChecklistItem::class, 'checklist_id')->orderBy('orden', 'asc')->orderBy('id', 'asc');
    }

    public function retornoCerradoBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retorno_cerrado_by');
    }

    public function reabiertoBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reabierto_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'checklist_id');
    }

    public function shipmentGuide(): BelongsTo
    {
        return $this->belongsTo(ShipmentGuide::class, 'shipment_guide_id');
    }

    public function hasShipmentGuide(): bool
    {
        return ! is_null($this->shipment_guide_id);
    }

    public function isReturnClosed(): bool
    {
        return ! is_null($this->retorno_cerrado_at);
    }

    public function isStockApplied(): bool
    {
        return ! is_null($this->stock_aplicado_at);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function recalculateCounters(): void
    {
        $items = $this->items()->get();
        $this->total_items = $items->count();
        $this->items_llevados = $items->where('llevado', true)->count();
        $this->items_devueltos = $items->where('devuelto', true)->count();
        $this->items_con_incidencia = $items->where('tiene_incidencia', true)->count();

        // Determine status automatically if not manually finalized
        if ($this->items_con_incidencia > 0 && $this->items_devueltos > 0) {
            $this->estado = self::STATUS_WITH_INCIDENTS;
        } elseif ($this->total_items > 0 && $this->items_devueltos === $this->total_items) {
            $this->estado = self::STATUS_RETURNED_OK;
        } elseif ($this->items_llevados > 0) {
            $this->estado = self::STATUS_DISPATCHED;
        } else {
            $this->estado = self::STATUS_PLANNED;
        }

        $this->save();
    }

    public function progressLlevado(): float
    {
        if ($this->total_items <= 0) {
            return 0.0;
        }

        return round(($this->items_llevados / $this->total_items) * 100, 1);
    }

    public function progressDevuelto(): float
    {
        if ($this->total_items <= 0) {
            return 0.0;
        }

        return round(($this->items_devueltos / $this->total_items) * 100, 1);
    }

    public function hasIncidents(): bool
    {
        return $this->items_con_incidencia > 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->estado) {
            self::STATUS_PLANNED => 'Planificado',
            self::STATUS_DISPATCHED => 'En Montaje / Salida',
            self::STATUS_RETURNED_OK => 'Conforme / Retornado',
            self::STATUS_WITH_INCIDENTS => 'Con Incidencias / Daños',
            default => 'Desconocido',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->estado) {
            self::STATUS_PLANNED => '<span class="badge bg-warning-subtle text-warning fw-bold"><i class="ri-time-line me-1"></i> Planificado</span>',
            self::STATUS_DISPATCHED => '<span class="badge bg-primary-subtle text-primary fw-bold"><i class="ri-truck-line me-1"></i> En Montaje / Salida</span>',
            self::STATUS_RETURNED_OK => '<span class="badge bg-success-subtle text-success fw-bold"><i class="ri-checkbox-circle-line me-1"></i> Conforme</span>',
            self::STATUS_WITH_INCIDENTS => '<span class="badge bg-danger text-white fw-bold"><i class="ri-alarm-warning-line me-1"></i> Con Incidencias</span>',
            default => '<span class="badge bg-secondary">N/A</span>',
        };
    }

    public function getCodeAttribute(): string
    {
        return $this->codigo ?? '';
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['codigo'] = $value;
    }

    public function getTotalLlevadosAttribute(): int
    {
        return (int) $this->items_llevados;
    }

    public function getTotalDevueltosAttribute(): int
    {
        return (int) $this->items_devueltos;
    }

    public function getTotalIncidenciasAttribute(): int
    {
        return (int) $this->items_con_incidencia;
    }

    public static function generateNextCode(): string
    {
        $year = date('Y');
        $latest = self::where('codigo', 'LIKE', "CHK-$year-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latest && preg_match('/CHK-\d{4}-(\d+)/', $latest->codigo, $matches)) {
            $correlativo = intval($matches[1]) + 1;
        } else {
            $correlativo = 1;
        }

        return sprintf('CHK-%s-%04d', $year, $correlativo);
    }

    public static function generateCode(): string
    {
        return self::generateNextCode();
    }
}
