<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\CashierGroup;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashierGroup>
 */
class CashierGroupFactory extends Factory
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
            'price_override_type' => array_rand(array_flip(array_column(PriceOverrideTypes::cases(), 'value'))),
            'price_override_limit_percentage_for_item' => fake()->randomFloat(2, 0, 100),
            'price_override_limit_percentage_for_cart' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
