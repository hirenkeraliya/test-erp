<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\DreamPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DreamPrice>
 */
class DreamPriceFactory extends Factory
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
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'is_available_in_pos' => fake()->randomElement([true]),
            'is_available_in_ecommerce' => fake()->randomElement([false]),
            'status' => fake()->randomElement([true, false]),
        ];
    }
}
