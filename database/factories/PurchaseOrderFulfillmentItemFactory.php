<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderFulfillmentItem>
 */
class PurchaseOrderFulfillmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'purchase_order_fulfillment_id' => fn () => PurchaseOrderFulfillment::factory()->create()->id,
            'purchase_order_item_id' => fn () => PurchaseOrderItem::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'transfer_quantity' => fake()->randomFloat(2, 1, 1000),
            'received_quantity' => fake()->randomFloat(2, 1, 1000),
            'remarks' => fake()->word(),
        ];
    }
}
