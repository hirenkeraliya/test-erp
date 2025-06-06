<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Company\Enums\BookingPaymentRefundTypes;
use App\Domains\Company\Enums\BookingPaymentUseTypes;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'code' => fake()->uuid,
            'grn_format' => 'GRN/' . fake()->word(),
            'legal_name' => fake()->company . ' ' . fake()->companySuffix,
            'website' => fake()->url,
            'email' => fake()->unique()->safeEmail(),
            'fax' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'address' => fake()->address(),
            'employer_identification_number' => (string) fake()->randomNumber(),
            'social_security_number' => (string) fake()->randomNumber(),
            'void_sale_number_prefix' => fake()->word,
            'send_sale_email_to_member' => fake()->boolean,
            'new_member_free_loyalty_points' => fake()->randomNumber(),
            'number_of_receipts' => fake()->randomNumber(),
            'commission_type_id' => array_rand(array_flip(array_column(CommissionTypes::cases(), 'value'))),
            'min_promoters_per_item' => fake()->randomFloat(2, 0, 100),
            'allow_exchange_to_different_store' => fake()->boolean,
            'allow_price_override_cart_level' => fake()->boolean,
            'allow_negative_inventory' => fake()->boolean,
            'is_employee_booking_payment_allowed' => fake()->boolean,
            'allow_only_return' => fake()->boolean,
            'allow_credit_sale' => fake()->boolean,
            'allow_employee_credit_sale' => fake()->boolean,
            'yearly_target' => fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'discount_applicable_type' => array_rand(
                array_flip(array_column(DiscountApplicableTypes::cases(), 'value'))
            ),
            'booking_payment_use_type' => array_rand(
                array_flip(array_column(BookingPaymentUseTypes::cases(), 'value'))
            ),
            'booking_payment_refund_type' => array_rand(
                array_flip(array_column(BookingPaymentRefundTypes::cases(), 'value'))
            ),
            'auto_birthday_voucher_generation' => false,
            'enable_ioi_city_mall_integration' => false,
            'enable_trx_mall_integration' => false,
            'allow_happy_hour_discount' => true,
            'default_country_id' => fn () => Country::factory()->create()->id,
            'order_picking_list_prefix' => fake()->word,
            'loyalty_point_expiration_days' => 10,
        ];
    }
}
