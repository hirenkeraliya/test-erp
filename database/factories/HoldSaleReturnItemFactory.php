<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HoldSaleDetail;
use App\Models\HoldSaleReturnItem;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HoldSaleReturnItem>
 */
class HoldSaleReturnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'hold_sale_detail_id' => fn () => HoldSaleDetail::factory()->create()->id,
            'sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 0, 100),
            'sale_return_reason_id' => fake()->randomFloat(2, 0, 100),
            'total_price_paid' => fake()->randomFloat(2, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(2, 0, 100),
            'item_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_tax_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
