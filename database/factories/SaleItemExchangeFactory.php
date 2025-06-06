<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaleItem;
use App\Models\SaleItemExchange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemExchange>
 */
class SaleItemExchangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'old_item_price' => fake()->randomFloat(2, 0, 100),
            'current_item_price' => fake()->randomFloat(2, 0, 100),
            'price_difference' => fake()->randomFloat(2, 0, 100),
            'old_discount_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
