<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\PurchaseAmount;
use App\Models\SaleItem;
use App\Models\SaleItemUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemUnit>
 */
class SaleItemUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'inventory_id' => fn () => Inventory::factory()->create()->id,
            'purchase_amount_id' => fn () => PurchaseAmount::factory()->create()->id,
            'batch_id' => fn () => Batch::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
