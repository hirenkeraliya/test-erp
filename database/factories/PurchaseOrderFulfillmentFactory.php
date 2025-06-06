<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderFulfillment>
 */
class PurchaseOrderFulfillmentFactory extends Factory
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
            'happened_at' => fake()->date(),
            'delivery_order_number' => fake()->uuid,
            'status' => FulfillmentStatuses::DRAFT->value,
        ];
    }
}
