<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => fn () => Country::factory()->create()->getKey(),
            'state_id' => fake()->name(),
            'name' => random_int(0, 1),
            'country_code' => random_int(1, 999),
        ];
    }
}
