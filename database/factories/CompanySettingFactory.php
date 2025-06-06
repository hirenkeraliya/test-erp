<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class CompanySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'credit_sale_use_cashback' => random_int(0, 1),
            'credit_sale_redeem_loyalty_points' => random_int(0, 1),
            'credit_sale_earn_loyalty_points' => random_int(0, 1),
            'credit_sale_redeem_vouchers' => random_int(0, 1),
            'credit_sale_generate_vouchers' => random_int(0, 1),
            'credit_sale_cart_wide_automatic_promotions' => random_int(0, 1),
            'credit_sale_cart_wide_manual_promotions' => random_int(0, 1),
            'credit_sale_item_wise_automatic_promotions' => random_int(0, 1),
            'credit_sale_item_wise_manual_promotions' => random_int(0, 1),
            'credit_sale_complimentary_item' => random_int(0, 1),
            'credit_sale_manual_cart_discount' => random_int(0, 1),
            'credit_sale_manual_item_discount' => random_int(0, 1),
            'credit_sale_happy_hour_discount' => random_int(0, 1),
            'credit_sale_allow_multi_currency_in_payment' => random_int(0, 1),

            'layaway_sale_use_cashback' => random_int(0, 1),
            'layaway_sale_redeem_loyalty_points' => random_int(0, 1),
            'layaway_sale_earn_loyalty_points' => random_int(0, 1),
            'layaway_sale_redeem_vouchers' => random_int(0, 1),
            'layaway_sale_generate_vouchers' => random_int(0, 1),
            'layaway_sale_cart_wide_automatic_promotions' => random_int(0, 1),
            'layaway_sale_cart_wide_manual_promotions' => random_int(0, 1),
            'layaway_sale_item_wise_automatic_promotions' => random_int(0, 1),
            'layaway_sale_item_wise_manual_promotions' => random_int(0, 1),
            'layaway_sale_complimentary_item' => random_int(0, 1),
            'layaway_sale_manual_cart_discount' => random_int(0, 1),
            'layaway_sale_manual_item_discount' => random_int(0, 1),
            'layaway_sale_happy_hour_discount' => random_int(0, 1),
            'layaway_sale_allow_multi_currency_in_payment' => random_int(0, 1),

            'booking_payment_allow_multi_currency_in_payment' => random_int(0, 1),
        ];
    }
}
