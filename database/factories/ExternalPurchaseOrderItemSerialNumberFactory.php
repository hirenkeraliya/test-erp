<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderItemSerialNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalPurchaseOrderItemSerialNumber>
 */
class ExternalPurchaseOrderItemSerialNumberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_purchase_order_item_id' => fn () => ExternalPurchaseOrderItem::factory()->create()->id,
            'serial_number' => fake()->uuid,
        ];
    }
}
