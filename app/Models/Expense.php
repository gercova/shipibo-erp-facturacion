<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';
    protected $primaryKey = 'id';

    protected $fillable = [
        'idarqueocaja',
        'idcaja',
        'idalmacen',
        'idusuario',
        'fecha',
        'hora',
        'tipo_comprobante',
        'nro_comprobante',
        'motivo',
        'monto',
        'metodo_pago',
        'beneficiario',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'float',
        'estado' => 'integer',
    ];

    public function archingCash(): BelongsTo {
        return $this->belongsTo(ArchingCash::class, 'idarqueocaja');
    }

    public function cash(): BelongsTo {
        return $this->belongsTo(Cash::class, 'idcaja');
    }

    public function warehouse(): BelongsTo {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'idusuario');
    }
}
