<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\PurchaseAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryUnit>
 */
class InventoryUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_id' => fn () => Inventory::factory()->create()->id,
            'purchase_amount_id' => fn () => PurchaseAmount::factory()->create()->id,
            'batch_id' => fn () => Batch::factory()->create()->id,
            'quantity' => random_int(1, 1000),
            'reserved_stock' => random_int(1, 1000),
        ];
    }
}
