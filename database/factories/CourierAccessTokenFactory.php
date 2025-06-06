<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Courier;
use App\Models\CourierAccessToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourierAccessToken>
 */
class CourierAccessTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'courier_id' => fn () => Courier::factory()->create()->id,
            'access_token' => fake()->text(20),
        ];
    }
}
