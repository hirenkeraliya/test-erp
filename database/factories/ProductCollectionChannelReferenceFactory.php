<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductCollection;
use App\Models\ProductCollectionChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCollectionChannelReference>
 */
class ProductCollectionChannelReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->id,
            'product_collection_id' => fn () => ProductCollection::factory()->create()->id,
            'external_product_collection_id' => random_int(1, 100),
        ];
    }
}
