<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyPointUpdate>
 */
class LoyaltyPointUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $affectedBy = 0 !== random_int(0, 1) ? Sale::class : SaleReturn::class;

        return [
            'member_id' => fn () => Member::factory()->create()->id,
            'affected_by_id' => fn () => $affectedBy::factory()->create()->id,
            'affected_by_type' => ModelMapping::getCaseName($affectedBy),
            'type_id' => array_rand(array_flip(array_column(LoyaltyPointUpdateTypes::cases(), 'value'))),
            'points' => fake()->randomNumber(),
            'closing_loyalty_points_balance' => fake()->randomNumber(),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
