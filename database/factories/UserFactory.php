<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\User\Enums\UserTypes;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Color>
 */
class UserFactory extends Factory
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
            'username' => fake()->unique()->word(),
            'type_id' => array_rand(array_flip(array_column(UserTypes::cases(), 'value'))),
            'password' => bcrypt('123456'),
            'remember_token' => null,
        ];
    }
}
