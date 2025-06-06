<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturnItem>
 */
class SaleReturnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_return_id' => fn () => SaleReturn::factory()->create()->id,
            'original_sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 0, 100),
            'total_price_paid' => fake()->randomFloat(2, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(2, 0, 100),
            'item_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_tax_amount' => fake()->randomFloat(2, 0, 100),
            'sale_return_reason_id' => fn () => SaleReturnReason::factory()->create()->id,
        ];
    }
}
