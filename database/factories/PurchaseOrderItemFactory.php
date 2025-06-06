<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'purchase_order_id' => fn () => PurchaseOrder::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 1, 1000),
            'rejected_quantity' => fake()->randomFloat(2, 1, 1000),
            'transferred_quantity' => fake()->randomFloat(2, 1, 1000),
            'price_per_unit' => fake()->randomFloat(2, 1, 1000),
            'remarks' => fake()->word(),
        ];
    }
}
