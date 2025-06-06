<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\SaleSeason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleSeason>
 */
class SaleSeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $date = now();

        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'name' => fake()->unique()->word(),
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->addDay()->format('Y-m-d'),
        ];
    }
}
