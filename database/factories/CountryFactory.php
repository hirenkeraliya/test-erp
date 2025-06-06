<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iso2' => Str::random(2),
            'name' => fake()->name(),
            'status' => random_int(0, 1),
            'phone_code' => random_int(1, 999),
            'iso3' => Str::random(3),
            'region' => fake()->name(),
            'subregion' => fake()->name(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Country $country): void {
            Currency::factory()->create([
                'country_id' => $country->id,
            ]);
        });
    }
}
