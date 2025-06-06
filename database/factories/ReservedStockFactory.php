<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\ReservedStock;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservedStock>
 */
class ReservedStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'inventory_id' => fn () => Inventory::factory()->create()->id,
            'inventory_unit_id' => fn () => InventoryUnit::factory()->create()->id,
            'affected_by_id' => fn () => SaleItem::factory()->create()->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'quantity' => fake()->randomFloat(2, 0, 100),
            'notes' => fake()->word(),
        ];
    }
}
