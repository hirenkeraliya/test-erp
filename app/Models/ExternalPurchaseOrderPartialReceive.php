<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderPartialReceive extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_purchase_order_id',
        'goods_received_note_id',
        'status',
        'received_date',
        'notes',
        'is_grn',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ExternalPurchaseOrderPartialReceiveItem::class);
    }

    public function externalPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(ExternalPurchaseOrder::class);
    }

    public function getItems(): Collection
    {
        return $this->items;
    }
}
