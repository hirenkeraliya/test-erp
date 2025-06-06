<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CounterUpdate;
use App\Models\HoldSale;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HoldSale>
 */
class HoldSaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'offline_id' => (string) fake()->randomNumber(),
            'cancelled_at' => fake()->dateTime(),
            'complete_at' => fake()->dateTime(),
            'complete_sale_id' => fn () => Sale::factory()->create()->id,
        ];
    }
}
