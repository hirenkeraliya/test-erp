<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Denomination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Denomination>
 */
class DenominationFactory extends Factory
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
            'denomination' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
