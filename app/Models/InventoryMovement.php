<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'concept',
        'quantity',
        'movement_sign',
        'checklist_id',
        'checklist_item_id',
        'contract_id',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'movement_sign' => 'integer',
    ];

    const TYPE_RETORNO_EVENTO = 'retorno_evento';

    const CONCEPT_QUEBRADA = 'quebrada';

    const CONCEPT_FALTANTE = 'faltante';

    const CONCEPT_CONSUMO = 'consumo';

    const CONCEPT_REVERSION = 'reversion_retorno';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(EventChecklist::class, 'checklist_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(EventChecklistItem::class, 'checklist_item_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
