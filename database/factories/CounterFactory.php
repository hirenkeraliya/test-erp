<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Counter>
 */
class CounterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'name' => fake()->name(),
            'is_locked' => true,
            'app_version' => fake()->numberBetween(1, 100),
            'app_version_updated_at' => fake()->dateTime(),
            'is_self_checkout' => fake()->boolean(),
        ];
    }
}
