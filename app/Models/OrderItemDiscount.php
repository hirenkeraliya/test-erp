<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItemDiscount extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['order_item_id', 'discountable_type', 'discountable_id', 'amount', 'promo_code'];

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function getDiscountableType(): string
    {
        return $this->discountable_type;
    }

    // Can be Promotion, DreamPrice, Complimentary Item, Price Override, Sale Item Exchange, Happy Hour Discount
    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }
}
