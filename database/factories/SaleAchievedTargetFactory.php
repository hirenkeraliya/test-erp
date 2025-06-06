<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Location;
use App\Models\SaleAchievedTarget;
use App\Models\SaleTargetTimeframe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleAchievedTarget>
 */
class SaleAchievedTargetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_target_timeframe_id' => fn () => SaleTargetTimeframe::factory()->create()->id,
            'targetable_id' => fn () => Location::factory()->create()->id,
            'targetable_type' => ModelMapping::LOCATION->name,
            'target_value' => fake()->randomFloat(2, 0, 100),
            'achieved_value' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
