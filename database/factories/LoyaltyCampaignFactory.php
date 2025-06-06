<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\LoyaltyCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyCampaign>
 */
class LoyaltyCampaignFactory extends Factory
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
            'name' => fake()->unique()->word,
            'minimum_spend_amount' => fake()->randomFloat(2, 0, 100),
            'loyalty_points' => random_int(0, 100),
            'loyalty_point_expiration_days' => random_int(0, 100),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
        ];
    }
}
