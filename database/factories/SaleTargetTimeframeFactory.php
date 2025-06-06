<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaleTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleTarget>
 */
class SaleTargetTimeframeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_target_id' => fn () => SaleTarget::factory()->create()->id,
            'target_label' => fake()->unique()->word(),
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 0, 100),
            'percentage' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
