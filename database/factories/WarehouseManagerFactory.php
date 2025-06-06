<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use App\Models\WarehouseManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseManager>
 */
class WarehouseManagerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => fn () => Employee::factory()->create()->id,
            'username' => fake()->userName,
            'password' => bcrypt('123456'),
            'remember_token' => null,
            'forgot_password_token' => null,
            'forgot_password_token_expiration_at' => null,
        ];
    }
}
