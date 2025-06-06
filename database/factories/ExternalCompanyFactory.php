<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalCompany>
 */
class ExternalCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_connection_id' => fn () => ExternalConnection::factory()->create()->id,
            'external_company_id' => random_int(1, 500),
            'name' => fake()->word(),
            'code' => fake()->uuid,
            'email' => fake()->unique()->safeEmail(),
            'fax' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'address' => fake()->address(),
        ];
    }
}
