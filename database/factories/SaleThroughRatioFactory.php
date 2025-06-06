<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SalesStoreRatio>
 */
class SaleThroughRatioFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'percentage' => fake()->randomFloat(2, 0, 100),
            'description' => fake()->sentence(),
        ];
    }
}
