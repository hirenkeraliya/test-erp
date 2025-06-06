<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_return_id',
        'original_order_item_id',
        'product_id',
        'quantity',
        'total_price_paid',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'total_tax_amount',
        'order_return_reason_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function orderReturnReason(): BelongsTo
    {
        return $this->belongsTo(SaleReturnReason::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'original_order_item_id');
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getOrderItem(): ?OrderItem
    {
        return $this->orderItem;
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getTotalDiscountAmount(): float
    {
        return (float) $this->total_discount_amount;
    }

    public function getTotalTaxAmount(): float
    {
        return (float) $this->total_tax_amount;
    }

    public function getTotalPricePaid(): float
    {
        return (float) $this->total_price_paid;
    }

    public function getOrderReturnReason(): ?SaleReturnReason
    {
        return $this->orderReturnReason;
    }
}
