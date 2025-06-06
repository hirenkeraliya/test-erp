<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\SaleLoyaltyPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleLoyaltyPoint>
 */
class SaleLoyaltyPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'product_id' => fn () => Product::factory()->create()->id,
            'sale_id' => fn () => Product::factory()->create()->id,
            'loyalty_points' => fake()->randomNumber(),
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
