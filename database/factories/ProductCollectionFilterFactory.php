<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\ProductCollectionFilter\Enums\ConditionOperatorTypes;
use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Models\ProductCollection;
use App\Models\ProductCollectionFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCollectionFilter>
 */
class ProductCollectionFilterFactory extends Factory
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
            'filter_type_id' => FilterTypes::CATEGORY->value,
            'condition_operator_type_id' => ConditionOperatorTypes::EQUAL->value,
        ];
    }
}
