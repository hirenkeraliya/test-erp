<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleItemAssemblyChildProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemAssemblyChildProduct>
 */
class SaleItemAssemblyChildProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'child_product_id' => fn () => Product::factory()->create()->id,
            'units' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
