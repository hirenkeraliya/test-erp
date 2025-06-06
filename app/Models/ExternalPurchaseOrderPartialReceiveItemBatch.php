<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExternalPurchaseOrderPartialReceiveItemBatch extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_purchase_order_partial_receive_item_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'notes',
    ];
}
