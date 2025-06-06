<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HoldSale;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\HoldSaleDetails>
 */
class HoldSaleDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'hold_sale_id' => fn () => HoldSale::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'happened_at' => fake()->dateTime(),
            'released_at' => fake()->dateTime(),
            'total_amount_paid' => fake()->randomFloat(2, 0, 100),
            'total_tax_amount' => fake()->randomFloat(2, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(2, 0, 100),
            'items_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_discount_amount' => fake()->randomFloat(2, 0, 100),
            'round_off' => fake()->randomFloat(2, 0, 100),
            'change_due' => fake()->randomFloat(2, 0, 100),
            'bill_reference_number' => (string) fake()->randomNumber(),
            'notes' => fake()->word(),
        ];
    }
}
