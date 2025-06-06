<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\PurchaseAmount;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransferItemUnit>
 */
class StockTransferItemUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_transfer_item_id' => fn () => StockTransferItem::factory()->create()->id,
            'inventory_id' => fn () => Inventory::factory()->create()->id,
            'purchase_amount_id' => fn () => PurchaseAmount::factory()->create()->id,
            'batch_id' => fn () => Batch::factory()->create()->id,
            'quantity' => random_int(0, 100),
        ];
    }
}
