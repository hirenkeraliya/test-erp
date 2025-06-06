<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
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
            'lifetime_value' => fake()->randomFloat(2, 0, 100),
            'loyalty_points_per_currency_unit' => random_int(0, 100),
            'min_loyalty_points_for_redemption' => random_int(200, 40000),
            'max_loyalty_points_for_redemption' => random_int(201, 40000),
        ];
    }
}
