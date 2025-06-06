<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssemblyChildProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssemblyChildProduct>
 */
class AssemblyChildProductFactory extends Factory
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
            'child_product_id' => fn () => Product::factory()->create()->id,
            'units' => random_int(1, 9999),
        ];
    }
}
