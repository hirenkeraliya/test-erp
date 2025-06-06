<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CloseCounterDenomination;
use App\Models\CounterUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloseCounterDenomination>
 */
class CloseCounterDenominationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'denomination' => fake()->randomFloat(2, 0, 100),
            'quantity' => random_int(1, 9999),
        ];
    }
}
