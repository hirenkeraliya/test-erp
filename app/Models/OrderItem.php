<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'exchange_item_id',
        'quantity',
        'complimentary_item_reason_id',
        'promotion_id',
        'original_product_price_per_unit',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'item_tax_amount',
        'price_paid_per_unit',
        'total_price_paid',
        'is_exchange',
        'box_product_id',
        'product_box_units',
        'product_box_package_type_id',
        'vendor_commission_percentage',
    ];

    // All The Relationship Definitions.

    public function promoters(): BelongsToMany
    {
        return $this->belongsToMany(Promoter::class, 'order_item_promoter');
    }

    public function orderReturnItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class, 'original_order_item_id');
    }

    public function pickingListItems(): HasMany
    {
        return $this->hasMany(OrderPickingListItem::class, 'order_id', 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function complimentaryItemReason(): BelongsTo
    {
        return $this->belongsTo(ComplimentaryItemReason::class);
    }

    public function orderItemUnits(): HasMany
    {
        return $this->hasMany(OrderItemUnit::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function boxProduct(): BelongsTo
    {
        return $this->belongsTo(BoxProduct::class);
    }

    // Get Columns and Some Calculations.

    public function getSubTotal(): float
    {
        return (float) ($this->original_product_price_per_unit * $this->quantity);
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getPricePaidPerUnit(): float
    {
        return (float) $this->price_paid_per_unit;
    }

    public function getTotalDiscountAmount(): float
    {
        return (float) $this->total_discount_amount;
    }

    public function getTotalTaxAmount(): float
    {
        return (float) $this->item_tax_amount;
    }

    public function getTotalPricePaid(): float
    {
        return (float) $this->total_price_paid;
    }

    public function getOriginalPricePerUnit(): float
    {
        return (float) $this->original_product_price_per_unit;
    }

    public function getOrderItemUnits(): Collection
    {
        return $this->orderItemUnits;
    }

    public function getComplimentaryItemReasonId(): ?int
    {
        return $this->complimentary_item_reason_id;
    }

    public function getComplimentaryItemReason(): ?ComplimentaryItemReason
    {
        return $this->complimentaryItemReason;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getTotalAmount(): float
    {
        return $this->getSubTotal() - $this->getTotalDiscountAmount() + $this->getTotalTaxAmount();
    }

    public function getOrderReturnItems(): Collection
    {
        return $this->orderReturnItems;
    }

    public function getBoxProductId(): ?int
    {
        return $this->box_product_id;
    }

    public function getBoxProductUnits(): float
    {
        return (float) $this->product_box_units;
    }

    public function getBoxProductPackageTypeId(): ?int
    {
        return $this->product_box_package_type_id;
    }

    // Scopes

    public function scopeIsNotExchange(Builder $query): Builder
    {
        return $query->where('is_exchange', false)
            ->whereNull('exchange_item_id');
    }
}
