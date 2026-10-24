<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'name',
        'order_num',
        'badge_glasses',
        'tagline',
        'description',
        'image',
        'color',
        'light_bg',
        'active',
        'min_guests',
        'max_guests',
        'ideal_capacity',
        'default_guests',
        'price_mode',
        'base_price',
        'features',
        'is_barman',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'is_barman' => 'boolean',
        'min_guests' => 'integer',
        'max_guests' => 'integer',
        'default_guests' => 'integer',
        'base_price' => 'decimal:2',
        'features' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class, 'package_id');
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PackagePriceTier::class, 'package_id')->orderBy('from_guests');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getImageUrlAttribute(): string
    {
        if (! empty($this->image)) {
            if (file_exists(public_path($this->image))) {
                return asset($this->image);
            }

            $path = public_path('files/packages/'.$this->image);
            if (file_exists($path)) {
                return asset('files/packages/'.$this->image);
            }
        }

        return asset('assets/images/package-placeholder.svg');
    }

    public function getGuestRangeLabelAttribute(): string
    {
        if ($this->max_guests) {
            return "{$this->min_guests} - {$this->max_guests} personas";
        }

        return "Desde {$this->min_guests} personas";
    }
}
