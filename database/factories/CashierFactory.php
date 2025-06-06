<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CashierGroup;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashierGroup>
 */
class CashierFactory extends Factory
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
            'cashier_group_id' => fn () => CashierGroup::factory()->create()->id,
            'username' => fake()->unique()->word(),
            'pin' => '1234',
        ];
    }
}
