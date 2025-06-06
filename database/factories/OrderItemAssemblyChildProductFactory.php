<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SaleItemAssemblyChildProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemAssemblyChildProduct>
 */
class OrderItemAssemblyChildProductFactory extends Factory
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
            'child_product_id' => fn () => Product::factory()->create()->id,
            'units' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
