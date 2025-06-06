<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterProduct>
 */
class ProductVariantValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => fn () => Product::factory()->create()->id,
            'attribute_id' => fn () => Attribute::factory()->create()->id,
            'value' => fake()->text(),
        ];
    }
}
