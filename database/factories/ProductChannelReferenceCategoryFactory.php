<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductChannelReference;
use App\Models\ProductChannelReferenceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductChannelReferenceCategory>
 */
class ProductChannelReferenceCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_category_id' => $this->faker->randomNumber(),
            'product_channel_references_id' => fn () => ProductChannelReference::factory()->create()->id,
        ];
    }
}
