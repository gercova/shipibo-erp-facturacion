<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasFactory;

    protected $table = 'cash_movements';

    protected $fillable = [
        'idarqueocaja',
        'idcaja',
        'idalmacen',
        'idusuario',
        'tipo',
        'monto',
        'motivo',
        'metodo_pago',
        'fecha',
        'hora',
        'beneficiario',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'estado' => 'integer',
    ];

    const TYPE_DEPOSIT = 'deposit';

    const TYPE_WITHDRAWAL = 'withdrawal';

    public function archingCash(): BelongsTo
    {
        return $this->belongsTo(ArchingCash::class, 'idarqueocaja');
    }

    public function cash(): BelongsTo
    {
        return $this->belongsTo(Cash::class, 'idcaja');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function isDeposit(): bool
    {
        return $this->tipo === self::TYPE_DEPOSIT;
    }

    public function isWithdrawal(): bool
    {
        return $this->tipo === self::TYPE_WITHDRAWAL;
    }
}
