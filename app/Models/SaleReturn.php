<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class SaleReturn extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'offline_sale_return_id',
        'original_sale_id',
        'counter_update_id',
        'total_tax_amount',
        'cart_discount_amount',
        'items_discount_amount',
        'total_discount_amount',
        'total_price_paid',
        'round_off_amount',
        'total_amount_before_round_off',
        'happened_at',
        'notes',
        'has_mismatch',
        'member_id',
        'digital_invoice_number',
        'digital_invoice_submitted',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'has_mismatch' => 'boolean',
        'digital_invoice_submitted' => 'boolean',
    ];

    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function originalSale(): HasOne
    {
        return $this->hasOne(Sale::class, 'id', 'original_sale_id');
    }

    public function exchangeSale(): HasOne
    {
        return $this->hasOne(Sale::class, 'sale_return_id');
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function saleReturnReason(): BelongsTo
    {
        return $this->belongsTo(SaleReturnReason::class);
    }

    public function mismatches(): MorphMany
    {
        return $this->morphMany(PosMismatch::class, 'module');
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class);
    }

    public function getSaleReturnItems(): Collection
    {
        return $this->saleReturnItems;
    }

    public function getOfflineSaleReturnId(): string
    {
        return $this->offline_sale_return_id;
    }

    public function getOriginalSaleId(): int
    {
        return $this->original_sale_id;
    }

    public function getHappenedAt(): string
    {
        return $this->happened_at;
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

    public function loyaltyPointUpdates(): MorphMany
    {
        return $this->morphMany(LoyaltyPointUpdate::class, 'affected_by');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
