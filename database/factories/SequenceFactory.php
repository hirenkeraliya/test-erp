<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Sequence\Enums\SequenceTypes;
use App\Models\Location;
use App\Models\Sequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sequence>
 */
class SequenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => fn () => Location::factory()->create()->id,
            'type_id' => fn (): int => array_rand(array_flip(array_column(SequenceTypes::cases(), 'value'))),
            'number' => fake()->randomNumber(),
        ];
    }
}
