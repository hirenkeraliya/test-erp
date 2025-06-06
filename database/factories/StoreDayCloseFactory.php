<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\StoreDayClose;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreDayClose>
 */
class StoreDayCloseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => fn () => Location::factory()->create()->id,
            'opened_at' => fake()->dateTime(),
            'closed_at' => fake()->dateTime(),
            'closed_by_store_manager_id' => fn () => StoreManager::factory()->create()->id,
            'total_sales' => random_int(1, 9999),
            'total_sales_amount' => fake()->randomFloat(2, 0, 100),
            'total_layaway_sales' => random_int(1, 9999),
            'total_layaway_sales_amount' => fake()->randomFloat(2, 0, 100),
            'total_credit_sales' => random_int(1, 9999),
            'total_credit_sales_amount' => fake()->randomFloat(2, 0, 100),
            'total_voided_sales' => random_int(1, 9999),
            'total_voided_sales_amount' => fake()->randomFloat(2, 0, 100),
            'total_item_wise_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_cart_wide_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_tax_amount' => fake()->randomFloat(2, 0, 100),
            'total_sales_round_off' => fake()->randomFloat(2, 0, 100),
            'total_sale_returns' => random_int(1, 9999),
            'total_sale_returns_amount' => fake()->randomFloat(2, 0, 100),
            'total_credit_notes_used_amount' => fake()->randomFloat(2, 0, 100),
            'total_credit_notes_used' => random_int(1, 9999),
            'total_credit_notes_refunded_amount' => fake()->randomFloat(2, 0, 100),
            'total_credit_notes_refunded' => random_int(1, 9999),
            'total_sale_returns_round_off' => fake()->randomFloat(2, 0, 100),
            'total_cashback' => random_int(1, 9999),
            'total_cashback_amount' => fake()->randomFloat(2, 0, 100),
            'total_vouchers_used' => random_int(1, 9999),
            'total_voucher_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_vouchers_generated' => random_int(1, 9999),
            'total_sale_promotion_used' => random_int(1, 9999),
            'total_sale_promotion_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_sale_item_promotion_used' => random_int(1, 9999),
            'total_sale_item_promotion_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_dream_price_used' => random_int(1, 9999),
            'total_dream_price_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_complimentary_item_discount_used' => random_int(1, 9999),
            'total_complimentary_item_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_price_override_used' => random_int(1, 9999),
            'total_price_override_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_booking_payment_amount' => fake()->randomFloat(2, 0, 100),
            'total_booking_payment_refunded_amount' => fake()->randomFloat(2, 0, 100),
            'total_booking_payment_used_amount' => fake()->randomFloat(2, 0, 100),
            'total_cash_ins_amount' => fake()->randomFloat(2, 0, 100),
            'total_cash_outs_amount' => fake()->randomFloat(2, 0, 100),
            'total_cash_amount_in_sales' => fake()->randomFloat(2, 0, 100),
            'total_cash_amount_in_booking_payment' => fake()->randomFloat(2, 0, 100),
            'total_cash_amount_in_booking_payment_refunded' => fake()->randomFloat(2, 0, 100),
            'total_cash_amount_in_credit_note_refunded' => fake()->randomFloat(2, 0, 100),
            'total_new_booking_payments' => random_int(1, 9999),
            'total_used_booking_payments' => random_int(1, 9999),
            'total_cancel_layaway_sales' => random_int(1, 9999),
            'total_cancel_layaway_sales_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
