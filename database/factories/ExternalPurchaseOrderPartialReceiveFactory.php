<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderPartialReceive;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalPurchaseOrderPartialReceive>
 */
class ExternalPurchaseOrderPartialReceiveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_purchase_order_id' => fn () => ExternalPurchaseOrderItem::factory()->create()->id,
            'received_date' => fake()->date(),
            'notes' => fake()->word(),
            'goods_received_note_id' => null,
        ];
    }
}
