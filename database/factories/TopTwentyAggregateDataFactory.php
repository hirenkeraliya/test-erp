<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CounterUpdate;
use App\Models\Product;
use App\Models\TopTwentyAggregateData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopTwentyAggregateData>
 */
class TopTwentyAggregateDataFactory extends Factory
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
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'date' => fake()->date(),
            'quantity' => fake()->randomFloat(2, 0, 100),
            'gross_sales' => fake()->randomFloat(2, 0, 100),
            'discount' => fake()->randomFloat(2, 0, 100),
            'net_sales' => fake()->randomFloat(2, 0, 100),
            'tax' => fake()->randomFloat(2, 0, 100),
            'total_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
