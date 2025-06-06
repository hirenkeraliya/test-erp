<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustmentItem>
 */
class StockAdjustmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_adjustment_id' => fn () => StockAdjustment::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create()->id,
            'quantity' => random_int(1, 100),
        ];
    }
}
