<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
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
            'reason' => fake()->word(),
            'created_by_admin_id' => fn () => Admin::factory()->create()->id,
            'approved_by_employee_id' => fn () => Employee::factory()->create()->id,
            'type_id' => array_rand(array_flip(array_column(StockAdjustmentTypes::cases(), 'value'))),
            'adjustment_date' => fake()->date(),
        ];
    }
}
