<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CocktailMenuItem extends Model
{
    use HasFactory;

    protected $table = 'cocktail_menu_items';

    protected $fillable = [
        'product_id',
        'menu_category_id',
        'nombre',
        'descripcion_corta',
        'cristaleria',
        'garnish',
        'imagen',
        'precio',
        'es_autor',
        'destacado',
        'orden',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'es_autor' => 'boolean',
        'destacado' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(CocktailMenuCategory::class, 'menu_category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopeDestacados($query)
    {
        return $query->where('destacado', true);
    }

    public function getImageUrlAttribute(): string
    {
        if (!empty($this->imagen)) {
            $path = public_path('files/cocktails/' . $this->imagen);
            if (file_exists($path)) {
                return asset('files/cocktails/' . $this->imagen);
            }
        }

        // Elegante placeholder temático de cóctel en SVG data URI
        $initial = mb_substr($this->nombre, 0, 1);
        $accentColor = $this->destacado ? '%23b45309' : '%23475569';
        $bgColor = '%231e293b';

        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect width='400' height='300' fill='{$bgColor}'/%3E%3Cpath d='M160 90 L240 90 L205 155 L205 210 L230 210 L230 220 L170 220 L170 210 L195 210 L195 155 Z' fill='none' stroke='{$accentColor}' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='215' cy='120' r='10' fill='{$accentColor}' opacity='0.7'/%3E%3Cpath d='M175 110 L225 110' stroke='{$accentColor}' stroke-width='2' stroke-dasharray='4'/%3E%3Ctext x='200' y='260' font-family='sans-serif' font-size='16' font-weight='bold' fill='%2394a3b8' text-anchor='middle'%3E" . rawurlencode(mb_substr($this->nombre, 0, 24)) . "%3C/text%3E%3C/svg%3E";
    }
}
