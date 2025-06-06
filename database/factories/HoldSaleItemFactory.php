<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HoldSaleDetail;
use App\Models\HoldSaleItem;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HoldSaleItem>
 */
class HoldSaleItemFactory extends Factory
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
            'product_id' => fn () => Product::factory()->create()->id,
            'derivative_id' => fn () => UnitOfMeasureDerivative::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 0, 100),
            'original_sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'returned_quantity' => fake()->randomFloat(2, 0, 100),
            'original_price_per_unit' => fake()->randomFloat(2, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(2, 0, 100),
            'item_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_discount_amount' => fake()->randomFloat(2, 0, 100),
            'total_tax_amount' => fake()->randomFloat(2, 0, 100),
            'price_paid_per_unit' => fake()->randomFloat(2, 0, 100),
            'total_price_paid' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
