<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartiallyReceiveFulfillmentItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'partially_receive_fulfillment_id',
        'purchase_order_fulfillment_item_id',
        'received_quantity',
    ];

    public function purchaseOrderFulfillmentItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderFulfillmentItem::class);
    }

    public function partiallyReceiveFulfillment(): BelongsTo
    {
        return $this->belongsTo(PartiallyReceiveFulfillment::class);
    }
}
