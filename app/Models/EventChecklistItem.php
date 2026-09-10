<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventChecklistItem extends Model
{
    use HasFactory;

    protected $table = 'event_checklist_items';

    protected $fillable = [
        'checklist_id',
        'contract_item_id',
        'idproducto',
        'producto_id',
        'descripcion',
        'categoria',
        'cantidad_planeada',
        'cantidad',
        'cantidad_llevada',
        'llevado',
        'idusuario_llevado',
        'fecha_llevado',
        'cantidad_devuelta',
        'devuelto',
        'idusuario_devuelto',
        'fecha_devuelto',
        'tiene_incidencia',
        'tipo_incidencia',
        'observaciones',
        'costo_penalidad_estimado',
        'orden',
    ];

    protected $casts = [
        'cantidad_planeada'         => 'decimal:2',
        'cantidad_llevada'          => 'decimal:2',
        'cantidad_devuelta'         => 'decimal:2',
        'costo_penalidad_estimado'  => 'decimal:2',
        'llevado'                   => 'boolean',
        'devuelto'                  => 'boolean',
        'tiene_incidencia'          => 'boolean',
        'fecha_llevado'             => 'datetime',
        'fecha_devuelto'            => 'datetime',
        'orden'                     => 'integer',
    ];

    public function checklist(): BelongsTo {
        return $this->belongsTo(EventChecklist::class, 'checklist_id');
    }

    public function contractItem(): BelongsTo {
        return $this->belongsTo(ContractItem::class, 'contract_item_id');
    }

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class, 'idproducto');
    }

    public function userLlevado(): BelongsTo {
        return $this->belongsTo(User::class, 'idusuario_llevado');
    }

    public function userDevuelto(): BelongsTo {
        return $this->belongsTo(User::class, 'idusuario_devuelto');
    }

    public function toggleLlevado($userId, $quantity = null): bool {
        if ($this->llevado) {
            $this->llevado = false;
            $this->cantidad_llevada = 0.00;
            $this->idusuario_llevado = null;
            $this->fecha_llevado = null;
        } else {
            $this->llevado = true;
            $this->cantidad_llevada = $quantity !== null ? floatval($quantity) : $this->cantidad_planeada;
            $this->idusuario_llevado = $userId;
            $this->fecha_llevado = Carbon::now();
        }

        $saved = $this->save();
        $this->checklist?->recalculateCounters();
        return $saved;
    }

    public function toggleDevuelto($userId, $quantity = null): bool {
        if ($this->devuelto) {
            $this->devuelto = false;
            $this->cantidad_devuelta = 0.00;
            $this->idusuario_devuelto = null;
            $this->fecha_devuelto = null;
        } else {
            $this->devuelto = true;
            $this->cantidad_devuelta = $quantity !== null ? floatval($quantity) : ($this->cantidad_llevada > 0 ? $this->cantidad_llevada : $this->cantidad_planeada);
            $this->idusuario_devuelto = $userId;
            $this->fecha_devuelto = Carbon::now();
        }

        $saved = $this->save();
        $this->checklist?->recalculateCounters();
        return $saved;
    }

    public function reportIncident(string $type, $arg2 = null, $arg3 = null, float $penalty = 0.0): bool {
        $this->tiene_incidencia = true;
        $this->tipo_incidencia = $type;

        if (is_numeric($arg2)) {
            // Called with ($type, $qty, $notes, $penalty)
            $qty = floatval($arg2);
            $notes = is_string($arg3) ? $arg3 : null;
            $this->observaciones = $notes;
            $this->costo_penalidad_estimado = $penalty;
            if ($qty > 0 && $this->cantidad_llevada > 0) {
                $this->cantidad_devuelta = max(0.00, $this->cantidad_llevada - $qty);
            }
        } else {
            // Called with ($type, $notes, $penalty)
            $this->observaciones = is_string($arg2) ? $arg2 : null;
            $this->costo_penalidad_estimado = is_numeric($arg3) ? floatval($arg3) : $penalty;
        }

        $saved = $this->save();
        $this->checklist?->recalculateCounters();
        return $saved;
    }

    public function clearIncident(): bool {
        $this->tiene_incidencia = false;
        $this->tipo_incidencia = null;
        $this->observaciones = null;
        $this->costo_penalidad_estimado = 0.00;

        $saved = $this->save();
        $this->checklist?->recalculateCounters();
        return $saved;
    }

    public function getCantidadAttribute(): float {
        return (float) $this->cantidad_planeada;
    }

    public function getCantidadAfectadaAttribute(): float {
        if ($this->cantidad_devuelta > 0 && $this->cantidad_llevada > $this->cantidad_devuelta) {
            return (float) ($this->cantidad_llevada - $this->cantidad_devuelta);
        }
        return (float) ($this->cantidad_planeada ?: 1.00);
    }

    public function getUnidadMedidaAttribute(): string {
        return $this->product?->unidad_medida ?? 'UNIDAD';
    }

    public function getUserLlevadoNameAttribute(): string {
        return $this->userLlevado?->nombre ?: ($this->userLlevado?->name ?: '');
    }

    public function getUserDevueltoNameAttribute(): string {
        return $this->userDevuelto?->nombre ?: ($this->userDevuelto?->name ?: '');
    }

    public function getFechaLlevadoFormattedAttribute(): string {
        return $this->fecha_llevado ? $this->fecha_llevado->format('d/m H:i') : '';
    }

    public function getFechaDevueltoFormattedAttribute(): string {
        return $this->fecha_devuelto ? $this->fecha_devuelto->format('d/m H:i') : '';
    }

    public function setProductoIdAttribute($value): void {
        $this->attributes['idproducto'] = $value;
    }

    public function setCantidadAttribute($value): void {
        $this->attributes['cantidad_planeada'] = $value;
    }
}
