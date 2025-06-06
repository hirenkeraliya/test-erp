<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'username' => fake()->userName,
            'password' => bcrypt('123456'),
            'employee_id' => fn () => Employee::factory()->create()->id,
            'remember_token' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ];
    }
}
