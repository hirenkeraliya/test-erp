<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'is_variant' => fake()->randomElement([true, false]),
        ];
    }
}
