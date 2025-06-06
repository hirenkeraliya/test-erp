<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderFulfillmentItemUnit extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_fulfillment_item_id',
        'inventory_id',
        'purchase_amount_id',
        'batch_id',
        'quantity',
    ];
}
