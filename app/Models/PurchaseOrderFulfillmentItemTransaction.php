<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderFulfillmentItemTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['purchase_order_fulfillment_item_id', 'remarks', 'status', 'user_id', 'user_type'];
}
