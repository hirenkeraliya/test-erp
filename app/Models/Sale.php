<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\Events\SaleCreatedEvent;
use App\Domains\SaleDiscount\Enums\DiscountableTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class Sale extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_return_id', 'offline_sale_id', 'counter_update_id', 'total_tax_amount', 'cart_discount_amount', 'items_discount_amount', 'total_discount_amount', 'total_amount_before_round_off', 'round_off', 'total_amount_paid', 'change_due', 'layaway_pending_amount', 'layaway_completed_at', 'layaway_authorizer_id', 'layaway_authorizer_type', 'status', 'notes', 'bill_reference_number', 'happened_at', 'has_mismatch', 'extra_details', 'credit_pending_amount', 'credit_completed_at', 'credit_authorizer_id', 'credit_authorizer_type', 'member_id', 'digital_invoice_number', 'digital_invoice_submitted',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'has_mismatch' => 'boolean',
        'extra_details' => 'json',
        'digital_invoice_submitted' => 'boolean',
    ];

    public function loyaltyPointUpdates(): MorphMany
    {
        return $this->morphMany(LoyaltyPointUpdate::class, 'affected_by');
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function voidSale(): HasOne
    {
        return $this->hasOne(VoidSale::class);
    }

    public function cancelLayawaySale(): HasOne
    {
        return $this->hasOne(CancelLayawaySale::class);
    }

    public function cancelCreditSale(): HasOne
    {
        return $this->hasOne(CancelCreditSale::class);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function cashback(): HasOne
    {
        return $this->hasOne(SaleCashback::class);
    }

    public function issuedLoyaltyPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoint::class);
    }

    // Can be StoreManager
    public function layawayAuthorizer(): MorphTo
    {
        return $this->morphTo();
    }

    // Can be StoreManager
    public function creditAuthorizer(): MorphTo
    {
        return $this->morphTo();
    }

    public function saleDiscounts(): HasMany
    {
        return $this->hasMany(SaleDiscount::class);
    }

    public function scopeOnlyVoidedSales(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::VOID_SALE->value);
    }

    public function scopeOnlyRegular(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::REGULAR_SALE->value);
    }

    public function scopeOnlyLayawaySale(Builder $query): Builder
    {
        return $query->whereIntegerInRaw('status', SaleStatus::getOnlyPendingAndCompleteLayawaySaleStatusValues());
    }

    public function scopeOnlyPendingLayawaySale(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::PENDING_LAYAWAY_SALE->value);
    }

    public function scopeOnlyCompleteLayawaySale(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::COMPLETE_LAYAWAY_SALE->value);
    }

    public function scopeOnlyRegularAndCompleteLayawaySale(Builder $query): Builder
    {
        return $query->whereIntegerInRaw('status', SaleStatus::getOnlyRegularAndCompleteLayawaySaleStatusValues());
    }

    public function scopeOnlyRegularCompleteCreditAndCompleteLayawaySale(Builder $query): Builder
    {
        return $query->whereIntegerInRaw('status', SaleStatus::getOnlyLayawayAndCreditCompleteSaleStatusValues());
    }

    public function scopeWithoutVoidSale(Builder $query): Builder
    {
        return $query
            ->whereIntegerInRaw('status', SaleStatus::getCommonActiveSaleStatusValues());
    }

    public function scopeOnlyCreditSale(Builder $query): Builder
    {
        return $query->whereIntegerInRaw('status', SaleStatus::getOnlyPendingAndCompleteCreditSaleStatusValues());
    }

    public function scopeOnlyPendingCreditSale(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::PENDING_CREDIT_SALE->value);
    }

    public function scopeOnlyCompleteCreditSale(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::COMPLETE_CREDIT_SALE->value);
    }

    public function mismatches(): MorphMany
    {
        return $this->morphMany(PosMismatch::class, 'module');
    }

    public function generatedVouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'generated_by_sale_id');
    }

    public function usedVoucher(): HasOne
    {
        return $this->hasOne(SaleDiscount::class)->where('discountable_type', DiscountableTypes::VOUCHER);
    }

    public function usedPromotion(): HasOne
    {
        return $this->hasOne(SaleDiscount::class)->where('discountable_type', DiscountableTypes::PROMOTION);
    }

    public function getLayWayPendingAmount(): float
    {
        return (float) $this->layaway_pending_amount;
    }

    public function getCreditPendingAmount(): float
    {
        return (float) $this->credit_pending_amount;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getCounterUpdateId(): ?int
    {
        return $this->counter_update_id;
    }

    public function getSaleItems(): Collection
    {
        return $this->saleItems;
    }

    public function getGrossTotal(): float
    {
        return (float) ($this->total_amount_before_round_off + $this->total_discount_amount - $this->total_tax_amount);
    }

    public function getGrossTotalForCreditSale(): float
    {
        return (float) ($this->total_amount_before_round_off + $this->total_discount_amount - $this->total_tax_amount + $this->credit_pending_amount);
    }

    public function getGrossTotalForLayawaySale(): float
    {
        return (float) ($this->total_amount_before_round_off + $this->total_discount_amount - $this->total_tax_amount + $this->layaway_pending_amount);
    }

    public function getOfflineSaleId(): string
    {
        return $this->offline_sale_id;
    }

    public function getHappenedAt(): string
    {
        return $this->happened_at;
    }

    public function getTotalDiscountAmount(): float
    {
        return (float) $this->total_discount_amount;
    }

    public function getTotalTaxAmount(): float
    {
        return (float) $this->total_tax_amount;
    }

    public function getLayawayPendingAmount(): float
    {
        return (float) $this->layaway_pending_amount;
    }

    public function getLayawayTotalAmount(): float
    {
        return (float) ($this->layaway_pending_amount + $this->total_amount_paid);
    }

    public function getCreditSaleTotalAmount(): float
    {
        return (float) ($this->credit_pending_amount + $this->total_amount_paid);
    }

    public function getTotalAmountPaid(): float
    {
        return (float) $this->total_amount_paid;
    }

    public function getRoundOff(): float
    {
        return (float) $this->round_off;
    }

    public function getTotalAmountBeforeRoundOff(): float
    {
        return (float) $this->total_amount_before_round_off;
    }

    public function scopeCancelLayawaySale(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::CANCEL_LAYAWAY_SALE->value);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function mysteryGiftUsages(): HasOne
    {
        return $this->hasOne(MysteryGiftUsage::class, 'sale_id');
    }

    protected static function boot()
    {
        parent::boot();
        // Event listener for the "created" event
        static::created(function ($sale): void {
            event(new SaleCreatedEvent($sale));
        });
    }
}
