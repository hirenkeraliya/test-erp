<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\StoreDayClose\Events\StoreDayCloseEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreDayClose extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'location_id',
        'opened_at',
        'closed_at',
        'closed_by_store_manager_id',
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
        'counter_update_ids',
        'opening_balance',
        'total_new_booking_payments',
        'total_cancel_layaway_sales',
        'total_cancel_layaway_sales_amount',
        'total_used_booking_payments',
        'orders_collection_amount',
        'total_orders',
        'total_orders_amount',
        'total_layaway_orders',
        'total_layaway_orders_amount',
        'total_credit_orders',
        'total_credit_orders_amount',
        'total_cancelled_orders',
        'total_cancelled_orders_amount',
        'total_order_item_wise_discount_amount',
        'total_order_cart_wide_discount_amount',
        'total_order_tax_amount',
        'total_orders_round_off',
        'total_order_returns',
        'total_order_returns_amount',
        'total_order_returns_round_off',
        'total_order_complimentary_item_discount_used',
        'total_order_complimentary_item_discount_amount',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'counter_update_ids' => 'json',
    ];

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class, 'closed_by_store_manager_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StoreDayClosePayment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($storeDayClose): void {
            event(new StoreDayCloseEvent($storeDayClose));
        });
    }
}
