<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HoldSaleDetail extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'hold_sale_id', 'member_id', 'happened_at', 'released_at', 'total_amount_paid', 'total_tax_amount', 'cart_discount_amount', 'items_discount_amount', 'total_discount_amount', 'round_off', 'change_due', 'bill_reference_number', 'notes', 'extra_details', 'is_layaway', 'layaway_pending_amount', 'store_manager_id', 'reason', 'is_credit_sale', 'credit_pending_amount',
    ];

    protected $casts = [
        'extra_details' => 'json',
    ];

    public function holdSaleItem(): HasOne
    {
        return $this->hasOne(HoldSaleItem::class);
    }

    public function holdSale(): BelongsTo
    {
        return $this->belongsTo(HoldSale::class);
    }

    public function getHoldSaleItem(): ?HoldSaleItem
    {
        return $this->holdSaleItem;
    }

    public function holdSaleReturnItem(): HasOne
    {
        return $this->hasOne(HoldSaleReturnItem::class);
    }

    public function getHoldSaleReturnItem(): ?HoldSaleReturnItem
    {
        return $this->holdSaleReturnItem;
    }

    public function holdBookingPaymentItem(): HasOne
    {
        return $this->hasOne(HoldBookingPaymentItem::class);
    }

    public function getHoldBookingPaymentItem(): ?HoldBookingPaymentItem
    {
        return $this->holdBookingPaymentItem;
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
