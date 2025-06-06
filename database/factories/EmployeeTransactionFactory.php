<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeTransaction>
 */
class EmployeeTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'employee_id' => fn () => Employee::factory()->create()->id,
            'status' => fake()->randomElement([1, 0]),
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
