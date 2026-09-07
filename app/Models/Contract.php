<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';

    protected $fillable = [
        'contract_number',
        'title',
        'idcliente',
        'provider_name',
        'provider_document',
        'provider_representative',
        'fecha_evento',
        'hora_evento',
        'lugar_evento',
        'fecha_emision',
        'fecha_vencimiento',
        'subtotal',
        'igv',
        'total',
        'moneda',
        'observaciones',
        'firma_cliente',
        'firma_proveedor',
        'estado',
        'idusuario',
        'idalmacen'
    ];

    protected $casts = [
        'fecha_evento'      => 'date',
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal'          => 'decimal:2',
        'igv'               => 'decimal:2',
        'total'             => 'decimal:2',
        'estado'            => 'integer',
    ];

    // Status Constants
    const STATUS_DRAFT      = 0;
    const STATUS_SIGNED     = 1;
    const STATUS_COMPLETED  = 2;
    const STATUS_VOIDED     = 3;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function clauses(): HasMany
    {
        return $this->hasMany(ContractClause::class, 'contract_id')->orderBy('orden', 'asc');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->estado) {
            self::STATUS_DRAFT      => 'Borrador',
            self::STATUS_SIGNED     => 'Firmado / Activo',
            self::STATUS_COMPLETED  => 'Completado',
            self::STATUS_VOIDED     => 'Anulado',
            default                 => 'Desconocido',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->estado) {
            self::STATUS_DRAFT      => '<span class="badge bg-warning text-dark">Borrador</span>',
            self::STATUS_SIGNED     => '<span class="badge bg-success">Firmado</span>',
            self::STATUS_COMPLETED  => '<span class="badge bg-info">Completado</span>',
            self::STATUS_VOIDED     => '<span class="badge bg-danger">Anulado</span>',
            default                 => '<span class="badge bg-secondary">N/A</span>',
        };
    }

    public static function validationRules($id = null): array
    {
        return [
            'idcliente'         => 'required|exists:clients,id',
            'contract_number'   => 'required|string|max:50|unique:contracts,contract_number' . ($id ? ",$id" : ''),
            'fecha_evento'      => 'required|date',
            'fecha_emision'     => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after:fecha_emision',
            'items'             => 'required|array|min:1',
            'items.*.descripcion' => 'required|string|max:255',
            'items.*.cantidad'    => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'clauses'               => 'nullable|array',
            'clauses.*.titulo'      => 'required_with:clauses|string|max:255',
            'clauses.*.contenido'   => 'required_with:clauses|string',
        ];
    }
}
