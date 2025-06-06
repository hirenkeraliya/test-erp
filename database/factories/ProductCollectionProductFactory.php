<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCollection>
 */
class ProductCollectionProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'product_collection_id' => fn () => ProductCollection::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'is_synced' => true,
        ];
    }
}
