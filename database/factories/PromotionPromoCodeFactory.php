<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\PromotionPromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionPromoCode>
 */
class PromotionPromoCodeFactory extends Factory
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
            'promo_code' => fake()->randomNumber(6),
        ];
    }
}
