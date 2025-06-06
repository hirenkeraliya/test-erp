<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ColorGroup;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColorGroup>
 */
class ColorGroupFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'code' => fake()->uuid,
            'color_code' => fake()->uuid,
        ];
    }
}
