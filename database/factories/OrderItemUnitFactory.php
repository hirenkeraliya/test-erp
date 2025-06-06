<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\OrderItemUnit;
use App\Models\PurchaseAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemUnit>
 */
class OrderItemUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_item_id' => fn () => OrderItem::factory()->create()->id,
            'inventory_id' => fn () => Inventory::factory()->create()->id,
            'purchase_amount_id' => fn () => PurchaseAmount::factory()->create()->id,
            'batch_id' => fn () => Batch::factory()->create()->id,
            'quantity' => fake()->randomFloat(6, 0, 100),
            'return_quantity' => fake()->randomFloat(6, 0, 100),
        ];
    }
}
