<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PurchaseAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseAmount>
 */
class PurchaseAmountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landed_cost' => fake()->randomFloat(2, 1, 1000),
            'fob' => fake()->randomFloat(2, 1, 1000),
            'freight_charges' => fake()->randomFloat(2, 1, 1000),
            'insurance_charges' => fake()->randomFloat(2, 1, 1000),
            'duty' => fake()->randomFloat(2, 1, 1000),
            'sst' => fake()->randomFloat(2, 1, 1000),
            'handling_charges' => fake()->randomFloat(2, 1, 1000),
            'other_charges' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
