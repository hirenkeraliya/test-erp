<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
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
            'manager_name' => fake()->unique()->name(),
            'manager_email' => fake()->unique()->email(),
        ];
    }
}
