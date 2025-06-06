<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionMonthDate>
 */
class PromotionMonthDateFactory extends Factory
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
            'month_date' => random_int(1, 31),
        ];
    }
}
