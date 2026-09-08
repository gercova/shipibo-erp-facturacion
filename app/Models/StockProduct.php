<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockProduct extends Model
{
    use HasFactory;
    protected $table        = 'stock_products';
    protected $primaryKey   = 'id';
    protected $fillable     = 
    [
        'idproducto',
        'idalmacen',
        'stock_minimo',
        'stock_actual',
        'precio_compra',
        'precio_venta',
        'fecha_registro',
        'stock_entrada'
    ];

    protected $casts = [
        'stock_minimo' => 'decimal:4',
        'stock_actual' => 'decimal:4',
        'stock_entrada' => 'decimal:4',
        'precio_compra' => 'decimal:4',
        'precio_venta' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'idproducto');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }
}
