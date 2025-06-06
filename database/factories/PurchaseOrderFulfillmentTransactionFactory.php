<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderFulfillmentTransaction>
 */
class PurchaseOrderFulfillmentTransactionFactory extends Factory
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
            'old_status' => fake()->boolean(),
            'new_status' => fake()->boolean(),
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
