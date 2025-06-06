<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderFulfillmentItemBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderFulfillmentItemBatch>
 */
class PurchaseOrderFulfillmentItemBatchFactory extends Factory
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
            'batch_id' => fn () => Batch::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
