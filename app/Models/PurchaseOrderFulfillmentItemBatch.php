<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderFulfillmentItemBatch extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_fulfillment_item_id',
        'batch_id',
        'received_quantity',
        'quantity',
        'is_discrepancy',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_discrepancy' => 'boolean',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
