<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemExchange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemExchange>
 */
class OrderItemExchangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_item_id' => fn () => OrderItem::factory()->create()->id,
            'old_item_price' => fake()->randomFloat(6, 0, 100),
            'current_item_price' => fake()->randomFloat(6, 0, 100),
            'price_differences' => fn (array $attributes): int|float => $attributes['current_item_price'] - $attributes['old_item_price'],
            'old_discount_amount' => fake()->randomFloat(6, 0, 100),
            'old_item_tax' => fake()->randomFloat(6, 0, 100),
            'current_item_tax' => fake()->randomFloat(6, 0, 100),
            'tax_differences' => fn (array $attributes): int|float => $attributes['current_item_tax'] - $attributes['old_item_tax'],
        ];
    }
}
