<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ExternalCategory;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalCategory>
 */
class ExternalCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'parent_category_id' => 0,
            'name' => fake()->name(),
            'company_id' => fn () => Company::factory()->create()->id,
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->id,
            'external_category_id' => random_int(0, 999),
        ];
    }
}
