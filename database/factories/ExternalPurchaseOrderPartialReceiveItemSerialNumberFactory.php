<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use App\Models\ExternalPurchaseOrderPartialReceiveItemSerialNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalPurchaseOrderPartialReceiveItemSerialNumber>
 */
class ExternalPurchaseOrderPartialReceiveItemSerialNumberFactory extends Factory
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
            'serial_number' => fake()->uuid,
        ];
    }
}
