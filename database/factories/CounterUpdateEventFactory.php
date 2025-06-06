<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounterUpdateEvent>
 */
class CounterUpdateEventFactory extends Factory
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
            'type_id' => array_rand(array_flip(array_column(CounterUpdateEventTypes::cases(), 'value'))),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
