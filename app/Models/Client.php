<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $fillable = [
        'iddoc',
        'nro_documento',
        'nombres',
        'direccion',
        'codigo_pais',
        'ubigeo',
        'telefono',
        'email'
    ];

    public function tipoDocumento(): BelongsTo {
        return $this->belongsTo(IdentityDocumentType::class, 'iddoc');
    }

    public function contracts(): HasMany {
        return $this->hasMany(Contract::class, 'idcliente');
    }
}
