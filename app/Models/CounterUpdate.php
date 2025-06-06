<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CounterUpdate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'counter_id',
        'cashier_id',
        'opening_balance',
        'closing_balance',
        'closed_at',
        'opened_by_pos_at',
        'closed_by_pos_at',
        'mismatch_amount',
        'amount_mismatch_reason',
        'sales_collection_amount',
        'total_sales',
        'total_sales_amount',
        'total_layaway_sales',
        'total_layaway_sales_amount',
        'total_credit_sales',
        'total_credit_sales_amount',
        'total_voided_sales',
        'total_voided_sales_amount',
        'total_item_wise_discount_amount',
        'total_cart_wide_discount_amount',
        'total_tax_amount',
        'total_sales_round_off',
        'total_sale_returns',
        'total_sale_returns_amount',
        'total_credit_notes_used_amount',
        'total_credit_notes_used',
        'total_credit_notes_refunded_amount',
        'total_credit_notes_refunded',
        'total_sale_returns_round_off',
        'total_cashback',
        'total_cashback_amount',
        'total_vouchers_used',
        'total_voucher_discount_amount',
        'total_vouchers_generated',
        'total_sale_promotion_used',
        'total_sale_promotion_discount_amount',
        'total_sale_item_promotion_used',
        'total_sale_item_promotion_discount_amount',
        'total_dream_price_used',
        'total_dream_price_discount_amount',
        'total_complimentary_item_discount_used',
        'total_complimentary_item_discount_amount',
        'total_price_override_used',
        'total_price_override_discount_amount',
        'total_booking_payment_amount',
        'total_booking_payment_refunded_amount',
        'total_booking_payment_used_amount',
        'total_cash_ins_amount',
        'total_cash_outs_amount',
        'total_cash_amount_in_sales',
        'total_cash_amount_in_booking_payment',
        'total_cash_amount_in_booking_payment_refunded',
        'total_cash_amount_in_credit_note_refunded',
        'total_new_booking_payments',
        'total_used_booking_payments',
        'total_cancel_layaway_sales',
        'total_cancel_layaway_sales_amount',
        'closed_by_type',
        'closed_by_id',
    ];

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class);
    }

    public function getOpeningBalance(): float
    {
        return (float) $this->opening_balance;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CloseCounterPayment::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function getClosingBalance(): float
    {
        return (float) $this->closing_balance;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function getCounter(): ?Counter
    {
        return $this->counter;
    }

    public function getCashier(): ?Cashier
    {
        return $this->cashier;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function getMismatchAmount(): float
    {
        return (float) $this->mismatch_amount;
    }

    public function getAmountMismatchReason(): ?string
    {
        return $this->amount_mismatch_reason;
    }

    public function denominations(): HasMany
    {
        return $this->hasMany(CloseCounterDenomination::class);
    }

    public function counterUpdateEvents(): HasMany
    {
        return $this->HasMany(CounterUpdateEvent::class);
    }

    public function counterUpdateDeclarationAttempts(): HasMany
    {
        return $this->HasMany(CounterUpdateDeclarationAttempt::class);
    }

    public function sales(): HasMany
    {
        return $this->HasMany(Sale::class);
    }

    public function saleReturns(): HasMany
    {
        return $this->HasMany(SaleReturn::class);
    }
}
