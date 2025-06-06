<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => fn () => Sale::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'derivative_id' => fn () => UnitOfMeasureDerivative::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 0, 100),
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
