<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItem extends Model
{
    use HasFactory;

    protected $table = 'package_items';

    protected $fillable = [
        'package_id',
        'product_id',
        'cocktail_id',
        'quantity',
        'is_per_guest',
        'is_mandatory',
        'unit_price',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'is_per_guest' => 'boolean',
        'is_mandatory' => 'boolean',
        'unit_price' => 'decimal:2',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(CocktailMenuItem::class, 'cocktail_id');
    }

    public function getNameAttribute(): string
    {
        if ($this->relationLoaded('cocktail') && $this->cocktail) {
            return (string) $this->cocktail->nombre;
        }

        if ($this->relationLoaded('product') && $this->product) {
            return (string) $this->product->descripcion;
        }

        if ($this->cocktail_id && $this->cocktail) {
            return (string) $this->cocktail->nombre;
        }

        if ($this->product_id && $this->product) {
            return (string) $this->product->descripcion;
        }

        return (string) ($this->notes ?: 'Ítem de Paquete');
    }

    public function calculateQuantity(int $guests): float
    {
        $baseQty = (float) $this->quantity;

        return $this->is_per_guest ? round($baseQty * max(1, $guests), 2) : $baseQty;
    }
}
