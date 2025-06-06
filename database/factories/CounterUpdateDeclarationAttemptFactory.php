<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounterUpdateDeclarationAttempt>
 */
class CounterUpdateDeclarationAttemptFactory extends Factory
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
            'offline_id' => (string) fake()->randomNumber(),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
