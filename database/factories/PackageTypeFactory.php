<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\PackageType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageType>
 */
class PackageTypeFactory extends Factory
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
            'name' => fake()->name(),
        ];
    }
}
