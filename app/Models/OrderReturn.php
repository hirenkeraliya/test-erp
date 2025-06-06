<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class OrderReturn extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'store_manager_id',
        'location_id',
        'member_id',
        'receipt_number',
        'original_order_id',
        'total_tax_amount',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'total_amount_before_round_off',
        'round_off_amount',
        'total_price_paid',
        'digital_invoice_number',
        'digital_invoice_submitted',
    ];

    protected $casts = [
        'digital_invoice_submitted' => 'boolean',
    ];

    public function orderReturnItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function originalOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'id', 'original_order_id');
    }

    public function orderCreditNote(): HasOne
    {
        return $this->hasOne(OrderCreditNote::class);
    }

    public function getOrderReturnItems(): Collection
    {
        return $this->orderReturnItems;
    }

    public function getStoreManager(): ?StoreManager
    {
        return $this->storeManager;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function getReceiptNumber(): string
    {
        return $this->receipt_number;
    }

    public function getTotalPricePaid(): float
    {
        return (float) $this->total_price_paid;
    }

    public function getTotalTaxAmount(): float
    {
        return (float) $this->total_tax_amount;
    }

    public function getTotalDiscountAmount(): float
    {
        return (float) $this->total_discount_amount;
    }

    public function getRoundOffAmount(): float
    {
        return (float) $this->round_off_amount;
    }

    public function getTotalAmountBeforeRoundOff(): float
    {
        return (float) $this->total_amount_before_round_off;
    }

    public function getGrossTotal(): float
    {
        return (float) ($this->total_price_paid + $this->total_discount_amount - $this->total_tax_amount);
    }

    public function getOriginalOrderId(): ?int
    {
        return $this->original_order_id;
    }

    public function getOriginalOrder(): ?Order
    {
        return $this->originalOrder;
    }

    public function getMemberId(): ?int
    {
        return $this->member_id;
    }
}
