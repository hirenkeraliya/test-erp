<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CounterUpdate;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturn>
 */
class SaleReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offline_sale_return_id' => (string) fake()->uuid,
            'original_sale_id' => fn () => Sale::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'total_tax_amount' => fake()->randomFloat(2, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(2, 0, 100),
            'items_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_price_paid' => fake()->randomFloat(2, 0, 100),
            'round_off_amount' => fake()->randomFloat(2, 0, 100),
            'total_amount_before_round_off' => fake()->randomFloat(2, 0, 100),
            'happened_at' => fake()->dateTime(),
            'notes' => fake()->word(),
            'has_mismatch' => fake()->randomElement([true, false]),
        ];
    }
}
