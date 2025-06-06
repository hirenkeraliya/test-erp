<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Models\Admin;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderFulfillmentItemTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderFulfillmentItemTransaction>
 */
class PurchaseOrderFulfillmentItemTransactionFactory extends Factory
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
            'remarks' => fake()->word(),
            'status' => FulfillmentStatuses::DRAFT->value,
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
