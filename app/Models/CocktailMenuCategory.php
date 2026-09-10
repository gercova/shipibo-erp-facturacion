<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CocktailMenuCategory extends Model
{
    use HasFactory;

    protected $table = 'cocktail_menu_categories';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function items(): HasMany {
        return $this->hasMany(CocktailMenuItem::class, 'menu_category_id')->orderBy('orden');
    }

    public function activeItems(): HasMany {
        return $this->hasMany(CocktailMenuItem::class, 'menu_category_id')->where('activo', true)->orderBy('orden');
    }

    public function scopeActive($query) {
        return $query->where('activo', true)->orderBy('orden');
    }
}
