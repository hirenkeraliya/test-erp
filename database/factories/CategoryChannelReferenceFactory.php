<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryChannelReference>
 */
class CategoryChannelReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'category_id' => fn () => Category::factory()->create()->id,
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->id,
            'external_category_id' => fn () => Category::factory()->create()->id,
        ];
    }
}
