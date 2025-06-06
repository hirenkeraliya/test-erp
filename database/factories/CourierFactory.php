<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Courier\Enums\CourierTypes;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'code' => fake()->name(),
            'type_id' => array_rand(array_flip(array_column(CourierTypes::cases(), 'value'))),
        ];
    }
}
