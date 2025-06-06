<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'code' => fake()->uuid,
            'commission_percentage' => fake()->randomFloat(2, 0, 100),
            'flat_commission' => null,
            'discount_type' => DiscountTypes::PERCENTAGE->value,
        ];
    }
}
