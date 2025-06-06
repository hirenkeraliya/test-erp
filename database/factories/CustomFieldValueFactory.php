<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Attribute;
use App\Models\CustomFieldValue;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldValue>
 */
class CustomFieldValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $attribute = Attribute::factory()->create();

        return [
            'model_id' => Product::factory(),
            'model_type' => ModelMapping::PRODUCT->name,
            'attribute_id' => $attribute->id,
            'template_id' => $attribute->template_id,
            'value' => fake()->boolean(),
        ];
    }
}
