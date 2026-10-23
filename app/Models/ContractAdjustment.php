<?php

namespace App\Models;

use App\Exceptions\ImmutableAdjustmentException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAdjustment extends Model
{
    use HasFactory;

    protected $table = 'contract_adjustments';

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'installment_id',
        'type',
        'description',
        'amount',
        'product_id',
        'checklist_item_id',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    const TYPE_BREAKAGE = 'breakage';

    const TYPE_MISSING = 'missing_item';

    const TYPE_ADDITIONAL = 'additional_consumption';

    const TYPE_DISCOUNT = 'discount';

    const TYPE_MANUAL = 'manual';

    /**
     * Booted method enforcing audit immutability.
     * Records cannot be updated or deleted; any correction must be an offsetting adjustment.
     */
    protected static function booted(): void
    {
        static::creating(function (ContractAdjustment $adjustment) {
            if (empty($adjustment->created_at)) {
                $adjustment->created_at = now();
            }
        });

        static::updating(function () {
            throw new ImmutableAdjustmentException(
                'Los registros de ajustes de contrato son inmutables y no pueden modificarse. Para correcciones, registre un ajuste compensatorio inverso.'
            );
        });

        static::deleting(function () {
            throw new ImmutableAdjustmentException(
                'Los registros de ajustes de contrato son inmutables y no pueden eliminarse. Para correcciones, registre un ajuste compensatorio inverso.'
            );
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(ContractInstallment::class, 'installment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BREAKAGE => 'Rotura de Menaje/Cristalería',
            self::TYPE_MISSING => 'Ítem Faltante / No Devuelto',
            self::TYPE_ADDITIONAL => 'Consumo Adicional',
            self::TYPE_DISCOUNT => 'Descuento Comercial',
            default => 'Ajuste Manual',
        };
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BREAKAGE => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="ri-goblet-line me-1"></i> Rotura</span>',
            self::TYPE_MISSING => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="ri-error-warning-line me-1"></i> Faltante</span>',
            self::TYPE_ADDITIONAL => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="ri-cup-line me-1"></i> Consumo Adic.</span>',
            self::TYPE_DISCOUNT => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="ri-discount-percent-line me-1"></i> Descuento</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary border"><i class="ri-edit-line me-1"></i> Manual</span>',
        };
    }
}
