<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PackageType;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransferItem>
 */
class StockTransferItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_transfer_id' => fn () => StockTransfer::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'package_type_id' => fn () => PackageType::factory()->create()->id,
            'package_quantity' => random_int(0, 100),
            'package_total_quantity' => random_int(0, 100),
            'quantity' => random_int(0, 100),
            'received_quantity' => random_int(0, 100),
        ];
    }
}
