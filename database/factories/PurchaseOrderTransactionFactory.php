<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderTransaction>
 */
class PurchaseOrderTransactionFactory extends Factory
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
            'old_status' => fake()->boolean(),
            'new_status' => fake()->boolean(),
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
