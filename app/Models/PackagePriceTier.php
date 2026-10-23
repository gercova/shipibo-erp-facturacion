<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePriceTier extends Model
{
    use HasFactory;

    protected $table = 'package_price_tiers';

    protected $fillable = [
        'package_id',
        'from_guests',
        'to_guests',
        'price',
        'price_type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'from_guests' => 'integer',
        'to_guests' => 'integer',
        'price' => 'decimal:2',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function containsGuests(int $guests): bool
    {
        return $guests >= $this->from_guests && $guests <= $this->to_guests;
    }

    public function calculatePrice(int $guests): float
    {
        $basePrice = (float) $this->price;

        if ($this->price_type === 'per_guest') {
            return round($basePrice * $guests, 2);
        }

        return round($basePrice, 2);
    }
}
