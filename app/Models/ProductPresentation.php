<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPresentation extends Model
{
    use HasFactory;

    protected $table      = 'product_presentations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'idproducto',
        'idunidad',
        'descripcion',
        'factor_conversion',
        'precio_compra',
        'precio_venta',
        'estado',
    ];

    protected $casts = [
        'estado'            => 'boolean',
        'factor_conversion' => 'float',
        'precio_compra'     => 'float',
        'precio_venta'      => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'idproducto');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'idunidad');
    }
}
