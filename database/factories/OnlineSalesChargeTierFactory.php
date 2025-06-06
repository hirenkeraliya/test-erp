<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OnlineSalesCharges;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnlineSalesChargeTier>
 */
class OnlineSalesChargeTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'online_sales_charges_id' => fn () => OnlineSalesCharges::factory()->create()->id,
            'min_weight' => fake()->numberBetween(1, 5),
            'max_weight' => fake()->numberBetween(6, 10),
            'amount' => random_int(100, 200),
        ];
    }
}
