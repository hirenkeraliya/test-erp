<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
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
            'id_number' => fake()->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'email' => fake()->optional(0.7)->safeEmail(),
            'mobile_number' => fake()->phoneNumber(),
            'country_code' => fake()->randomElement(['+1', '+44', '+61', '+91', '+86', '+81']),
            'status' => true,
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_by_id' => fn () => Admin::factory()->create()->id,
        ];
    }
}
