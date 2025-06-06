<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use App\Models\ExternalPurchaseOrderPartialReceiveItemBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalPurchaseOrderPartialReceiveItemBatch>
 */
class ExternalPurchaseOrderPartialReceiveItemBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_purchase_order_partial_receive_item_id' => fn () => ExternalPurchaseOrderPartialReceiveItem::factory()->create()->id,
            'batch_number' => fake()->uuid,
            'expiry_date' => fake()->date(),
            'quantity' => fake()->randomFloat(2, 1, 1000),
            'notes' => fake()->word(),
        ];
    }
}
