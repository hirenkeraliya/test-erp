<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Models\Company;
use App\Models\EmployeeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeGroup>
 */
class EmployeeGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'name' => fake()->unique()->word(),
            'code' => fake()->uuid,
            'purchase_limit_type_id' => array_rand(array_flip(array_column(PurchaseLimitTypes::cases(), 'value'))),
            'limit_reset_type_id' => array_rand(array_flip(array_column(LimitResetTypes::cases(), 'value'))),
            'item_purchase_limit' => 0,
            'limit_reset' => 10,
        ];
    }
}
