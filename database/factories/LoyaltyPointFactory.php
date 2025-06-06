<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyPoint>
 */
class LoyaltyPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => fn () => Member::factory()->create()->id,
            'sale_id' => fn () => Sale::factory()->create()->id,
            'loyalty_campaign_id' => fn () => LoyaltyCampaign::factory()->create()->id,
            'expiry_date' => fake()->date(),
            'points' => fake()->randomNumber(),
            'available_points' => fake()->randomNumber(),
            'minimum_spend_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
