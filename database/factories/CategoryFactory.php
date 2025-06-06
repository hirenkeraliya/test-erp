<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'parent_category_id' => null,
            'name' => fake()->regexify('[A-Z]{5}[1-4]{3}'),
            'code' => fake()->uuid,
            'status' => true,
            'is_available_in_ecommerce' => false,
            'is_display_on_menu' => false,
        ];
    }
}
