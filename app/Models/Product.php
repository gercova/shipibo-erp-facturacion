<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table        = 'products';
    protected $primaryKey   = 'id';

    protected $fillable =
    [
        'codigo_interno',
        'codigo_barras',
        'codigo_sunat',
        'descripcion',
        'idunidad',
        'idcategoria',
        'igv',
        'idcodigo_igv',
        'precio_compra',
        'precio_venta',
        'opcion',
        'rentable',
        'es_alquilable',
        'stock_actual'
    ];

    protected $casts = [
        'rentable' => 'boolean',
        'stock_actual' => 'decimal:4',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'opcion' => 'integer',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'idunidad');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'idcategoria');
    }

    public function igvTypeAffection()
    {
        return $this->belongsTo(IgvTypeAffection::class, 'idcodigo_igv');
    }

    public function presentations()
    {
        return $this->hasMany(ProductPresentation::class, 'idproducto')->where('estado', true)->orderBy('id');
    }

    public function getEsAlquilableAttribute(): bool
    {
        return (bool) $this->rentable;
    }

    public function setEsAlquilableAttribute($value): void
    {
        $this->attributes['rentable'] = (bool) $value;
    }

    public function isRentable(): bool
    {
        return (bool) $this->rentable;
    }

    public function scopeRentable($query)
    {
        return $query->where('rentable', true);
    }
}
