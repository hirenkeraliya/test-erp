<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Company;
use App\Models\VoucherConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoucherConfiguration>
 */
class VoucherConfigurationFactory extends Factory
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
            'restricted_by_type' => array_rand(array_flip(array_column(RestrictedByTypes::cases(), 'value'))),
            'voucher_type' => array_rand(array_flip(array_column(VoucherTypes::cases(), 'value'))),
            'exclude_by_type' => array_rand(array_flip(array_column(ExcludeByTypes::cases(), 'value'))),
            'issue_minimum_spend_amount' => random_int(1, 999),
            'use_minimum_spend_amount' => random_int(1, 99),
            'validity_days' => random_int(1, 30),
            'discount_type' => array_rand(array_flip(array_column(DiscountTypes::cases(), 'value'))),
            'get_value' => random_int(1, 99),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'dream_price_applicable' => fake()->randomElement([true, false]),
            'item_wise_promotion_applicable' => fake()->randomElement([true, false]),
            'cart_wide_promotion_applicable' => fake()->randomElement([true, false]),
            'redemption_foot_note' => fake()->paragraph,
            'handover_foot_note' => fake()->paragraph,
            'title' => fake()->paragraph,
            'description' => fake()->paragraph,
            'terms_and_conditions' => fake()->paragraph,
            'is_available_in_ecommerce' => fake()->randomElement([true, false]),
        ];
    }
}
