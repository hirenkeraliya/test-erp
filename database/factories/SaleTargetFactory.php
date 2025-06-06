<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\Company;
use App\Models\SaleTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleTarget>
 */
class SaleTargetFactory extends Factory
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
            'amount' => fake()->randomFloat(2, 0, 100),
            'amount_type' => array_rand(array_flip(array_column(SaleTargetAmountTypes::cases(), 'value'))),
            'target_type' => array_rand(array_flip(array_column(TargetType::cases(), 'value'))),
            'time_interval_type' => array_rand(array_flip(array_column(TimeIntervalType::cases(), 'value'))),
            'status' => true,
        ];
    }
}
