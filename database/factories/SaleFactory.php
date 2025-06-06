<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Sale\Enums\SaleStatus;
use App\Models\CounterUpdate;
use App\Models\Member;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offline_sale_id' => fake()->randomFloat(2, 100, 1000) . fake()->randomNumber(),
            'member_id' => fn () => Member::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'total_tax_amount' => fake()->randomFloat(2, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(2, 0, 100),
            'items_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_amount_before_round_off' => fake()->randomFloat(2, 0, 100),
            'round_off' => fake()->randomFloat(2, 0, 100),
            'total_amount_paid' => fake()->randomFloat(2, 0, 100),
            'change_due' => fake()->randomFloat(2, 0, 100),
            'layaway_pending_amount' => fake()->randomFloat(2, 0, 100),
            'credit_pending_amount' => fake()->randomFloat(2, 0, 100),
            'status' => array_rand(array_flip(array_column(SaleStatus::cases(), 'value'))),
            'notes' => fake()->word(),
            'bill_reference_number' => fake()->word(),
            'happened_at' => fake()->dateTime(),
            'has_mismatch' => fake()->randomElement([true, false]),
        ];
    }
}
