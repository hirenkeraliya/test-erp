<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalPurchaseOrder;
use App\Models\ExternalPurchaseOrderItem;
use App\Models\Product;
use App\Models\PurchasePlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalPurchaseOrderItem>
 */
class ExternalPurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_purchase_order_id' => fn () => ExternalPurchaseOrder::factory()->create()->id,
            'purchase_plan_item_id' => fn () => PurchasePlanItem::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 1, 1000),
            'received_quantity' => fake()->randomFloat(2, 1, 1000),
            'cost_price' => fake()->randomFloat(2, 1, 1000),
            'charge_per_unit' => fake()->randomFloat(2, 1, 1000),
            'total_price' => fake()->randomFloat(2, 1, 1000),
            'remarks' => fake()->word(),
        ];
    }
}
