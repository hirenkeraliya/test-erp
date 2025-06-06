<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\PurchaseAmount;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderFulfillmentItemUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderFulfillmentItemUnit>
 */
class PurchaseOrderFulfillmentItemUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'purchase_order_fulfillment_item_id' => fn () => PurchaseOrderFulfillmentItem::factory()->create()->id,
            'inventory_id' => fn () => Inventory::factory()->create()->id,
            'purchase_amount_id' => fn () => PurchaseAmount::factory()->create()->id,
            'batch_id' => fn () => Batch::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
