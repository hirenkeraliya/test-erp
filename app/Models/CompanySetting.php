<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanySetting extends Model
{
    use HasFactory;

    protected $table = 'company_settings';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'credit_sale_use_cashback',
        'credit_sale_redeem_loyalty_points',
        'credit_sale_earn_loyalty_points',
        'credit_sale_redeem_vouchers',
        'credit_sale_generate_vouchers',
        'credit_sale_cart_wide_automatic_promotions',
        'credit_sale_cart_wide_manual_promotions',
        'credit_sale_item_wise_automatic_promotions',
        'credit_sale_item_wise_manual_promotions',
        'credit_sale_complimentary_item',
        'credit_sale_manual_cart_discount',
        'credit_sale_manual_item_discount',
        'credit_sale_happy_hour_discount',
        'credit_sale_allow_multi_currency_in_payment',

        'layaway_sale_use_cashback',
        'layaway_sale_redeem_loyalty_points',
        'layaway_sale_earn_loyalty_points',
        'layaway_sale_redeem_vouchers',
        'layaway_sale_generate_vouchers',
        'layaway_sale_cart_wide_automatic_promotions',
        'layaway_sale_cart_wide_manual_promotions',
        'layaway_sale_item_wise_automatic_promotions',
        'layaway_sale_item_wise_manual_promotions',
        'layaway_sale_complimentary_item',
        'layaway_sale_manual_cart_discount',
        'layaway_sale_manual_item_discount',
        'layaway_sale_happy_hour_discount',
        'layaway_sale_allow_multi_currency_in_payment',

        'booking_payment_allow_multi_currency_in_payment',
    ];

    protected $casts = [
        'credit_sale_use_cashback' => 'boolean',
        'credit_sale_redeem_loyalty_points' => 'boolean',
        'credit_sale_earn_loyalty_points' => 'boolean',
        'credit_sale_redeem_vouchers' => 'boolean',
        'credit_sale_generate_vouchers' => 'boolean',
        'credit_sale_cart_wide_automatic_promotions' => 'boolean',
        'credit_sale_cart_wide_manual_promotions' => 'boolean',
        'credit_sale_item_wise_automatic_promotions' => 'boolean',
        'credit_sale_item_wise_manual_promotions' => 'boolean',
        'credit_sale_complimentary_item' => 'boolean',
        'credit_sale_manual_cart_discount' => 'boolean',
        'credit_sale_manual_item_discount' => 'boolean',
        'credit_sale_happy_hour_discount' => 'boolean',
        'credit_sale_allow_multi_currency_in_payment' => 'boolean',

        'layaway_sale_use_cashback' => 'boolean',
        'layaway_sale_redeem_loyalty_points' => 'boolean',
        'layaway_sale_earn_loyalty_points' => 'boolean',
        'layaway_sale_redeem_vouchers' => 'boolean',
        'layaway_sale_generate_vouchers' => 'boolean',
        'layaway_sale_cart_wide_automatic_promotions' => 'boolean',
        'layaway_sale_cart_wide_manual_promotions' => 'boolean',
        'layaway_sale_item_wise_automatic_promotions' => 'boolean',
        'layaway_sale_item_wise_manual_promotions' => 'boolean',
        'layaway_sale_complimentary_item' => 'boolean',
        'layaway_sale_manual_cart_discount' => 'boolean',
        'layaway_sale_manual_item_discount' => 'boolean',
        'layaway_sale_happy_hour_discount' => 'boolean',
        'layaway_sale_allow_multi_currency_in_payment' => 'boolean',

        'booking_payment_allow_multi_currency_in_payment' => 'boolean',
    ];

    protected $hidden = ['company_id'];

    public function getNameColumnName(): string
    {
        return 'company_id,credit_sale_use_cashback,credit_sale_redeem_loyalty_points,credit_sale_earn_loyalty_points,credit_sale_redeem_vouchers,credit_sale_generate_vouchers,credit_sale_cart_wide_automatic_promotions,credit_sale_cart_wide_manual_promotions,credit_sale_item_wise_automatic_promotions,credit_sale_item_wise_manual_promotions,credit_sale_complimentary_item,credit_sale_manual_cart_discount,credit_sale_manual_item_discount,credit_sale_happy_hour_discount,credit_sale_allow_multi_currency_in_payment,layaway_sale_use_cashback,layaway_sale_redeem_loyalty_points,layaway_sale_earn_loyalty_points,layaway_sale_redeem_vouchers,layaway_sale_generate_vouchers,layaway_sale_cart_wide_automatic_promotions,layaway_sale_cart_wide_manual_promotions,layaway_sale_item_wise_automatic_promotions,layaway_sale_item_wise_manual_promotions,layaway_sale_complimentary_item,layaway_sale_manual_cart_discount,layaway_sale_manual_item_discount,layaway_sale_happy_hour_discount,layaway_sale_allow_multi_currency_in_payment,booking_payment_allow_multi_currency_in_payment';
    }
}
