<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderPartialReceive;
use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalPurchaseOrderPartialReceiveItem>
 */
class ExternalPurchaseOrderPartialReceiveItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_purchase_order_partial_receive_id' => fn () => ExternalPurchaseOrderPartialReceive::factory()->create()->id,
            'external_purchase_order_item_id' => fn () => ExternalPurchaseOrderItem::factory()->create()->id,
            'quantity_received' => fake()->randomFloat(2, 1, 1000),
            'notes' => fake()->word(),
        ];
    }
}
