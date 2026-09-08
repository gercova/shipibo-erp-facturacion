<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractInstallment extends Model
{
    use HasFactory;

    protected $table = 'contract_installments';

    protected $fillable = [
        'contract_id',
        'numero_cuota',
        'descripcion',
        'monto',
        'porcentaje',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'metodo_pago',
        'referencia_pago',
        'observaciones',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago'        => 'date',
        'monto'             => 'decimal:2',
        'porcentaje'        => 'decimal:2',
        'estado'            => 'integer',
    ];

    const STATUS_PENDING = 0;
    const STATUS_PAID    = 1;

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function isPaid(): bool
    {
        return $this->estado === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->estado === self::STATUS_PENDING;
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return $this->fecha_vencimiento && $this->fecha_vencimiento->lt(Carbon::today());
    }

    public function isDueToday(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return $this->fecha_vencimiento && $this->fecha_vencimiento->isToday();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->estado) {
            self::STATUS_PAID => 'Pagado',
            default           => 'Pendiente',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->isPaid()) {
            return '<span class="badge bg-success-subtle text-success fw-semibold"><i class="ri-checkbox-circle-line me-1"></i> Pagado</span>';
        }

        if ($this->isOverdue()) {
            return '<span class="badge bg-danger-subtle text-danger fw-bold"><i class="ri-alarm-warning-line me-1"></i> Vencida (' . $this->fecha_vencimiento->format('d/m/Y') . ')</span>';
        }

        if ($this->isDueToday()) {
            return '<span class="badge bg-warning-subtle text-warning fw-bold"><i class="ri-time-line me-1"></i> Vence Hoy</span>';
        }

        return '<span class="badge bg-light text-secondary fw-medium"><i class="ri-calendar-line me-1"></i> Vence ' . $this->fecha_vencimiento->format('d/m/Y') . '</span>';
    }
}
