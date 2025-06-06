<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Member;
use App\Models\Sale;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voucher_configuration_id' => fn () => VoucherConfiguration::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'generated_by_sale_id' => fn () => Sale::factory()->create()->id,
            'discount_type' => array_rand(array_flip(array_column(DiscountTypes::cases(), 'value'))),
            'number' => fake()->uuid,
            'minimum_spend_amount' => fake()->randomFloat(2, 0, 100),
            'percentage' => fake()->randomFloat(2, 0, 100),
            'flat_amount' => fake()->randomFloat(2, 0, 100),
            'used_at' => fake()->dateTime(),
            'expiry_date' => fake()->date(),
            'cancelled_at' => fake()->date(),
        ];
    }
}
