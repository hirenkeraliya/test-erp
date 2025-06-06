<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promoter>
 */
class PromoterFactory extends Factory
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
            'monthly_sales_target' => fake()->randomFloat(2, 0, 100),
            'code' => fake()->uuid,
        ];
    }
}
