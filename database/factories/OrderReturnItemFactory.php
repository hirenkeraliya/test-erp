<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Product;
use App\Models\SaleReturnReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReturnItem>
 */
class OrderReturnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_return_id' => fn () => OrderReturn::factory()->create()->id,
            'original_order_item_id' => fn () => OrderItem::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => fake()->randomFloat(6, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(6, 0, 100),
            'item_discount_amount' => fake()->randomFloat(6, 0, 100),
            'total_discount_amount' => fake()->randomFloat(6, 0, 100),
            'total_tax_amount' => fake()->randomFloat(6, 0, 100),
            'order_return_reason_id' => fn () => SaleReturnReason::factory()->create()->id,
        ];
    }
}
