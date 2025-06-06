<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalPurchaseOrderPartialReceiveItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_purchase_order_partial_receive_id',
        'external_purchase_order_item_id',
        'quantity_received',
        'notes',
        'unit_of_measure_derivative_id',
    ];

    public function externalPurchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(ExternalPurchaseOrderItem::class);
    }

    public function itemBatches(): HasMany
    {
        return $this->hasMany(ExternalPurchaseOrderPartialReceiveItemBatch::class);
    }

    public function derivative(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureDerivative::class, 'unit_of_measure_derivative_id');
    }
}
