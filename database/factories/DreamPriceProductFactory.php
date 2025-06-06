<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DreamPriceProduct>
 */
class DreamPriceProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dream_price_id' => fn () => DreamPrice::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'price' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
