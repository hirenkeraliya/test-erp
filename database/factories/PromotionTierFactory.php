<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\PromotionTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionTier>
 */
class PromotionTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotion_id' => fn () => Promotion::factory()->create()->id,
            'buy_value' => fake()->randomFloat(2, 0, 100),
            'get_value' => fake()->randomFloat(2, 0, 100),
            'get_quantity' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
