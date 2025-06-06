<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockTake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTakeProduct>
 */
class StockTakeProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_take_id' => fn () => StockTake::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'actual_stock' => fake()->randomFloat(2, 0, 100),
            'submitted_stock' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
