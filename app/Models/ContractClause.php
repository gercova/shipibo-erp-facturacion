<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractClause extends Model
{
    use HasFactory;

    protected $table = 'contract_clauses';

    protected $fillable = [
        'contract_id',
        'titulo',
        'contenido',
        'orden'
    ];

    public function contract(): BelongsTo {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
