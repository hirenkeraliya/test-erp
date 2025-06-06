<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ComplimentaryItemReason;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturnItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_id' => fn () => Order::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'exchange_item_id' => fn () => OrderReturnItem::factory()->create()->id,
            'quantity' => fake()->randomFloat(6, 0, 100),
            'complimentary_item_reason_id' => fn () => ComplimentaryItemReason::factory()->create()->id,
            'original_product_price_per_unit' => fake()->randomFloat(6, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(6, 0, 100),
            'item_discount_amount' => fake()->randomFloat(6, 0, 100),
            'total_discount_amount' => fake()->randomFloat(6, 0, 100),
            'item_tax_amount' => fake()->randomFloat(6, 0, 100),
            'price_paid_per_unit' => fake()->randomFloat(6, 0, 100),
            'total_price_paid' => fake()->randomFloat(6, 0, 100),
            'is_exchange' => fake()->boolean(),
        ];
    }
}
