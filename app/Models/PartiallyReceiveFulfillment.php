<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PartiallyReceiveFulfillment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_fulfillment_id',
        'received_by_user_id',
        'received_by_user_type',
        'status',
        'partially_receive_number',
    ];

    // Can be StoreManager, Admin, WarehouseManager
    public function receivedByUser(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PartiallyReceiveFulfillmentItem::class);
    }
}
