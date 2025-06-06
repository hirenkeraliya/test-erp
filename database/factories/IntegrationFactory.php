<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Integration\Enums\IntegrationConnections;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'company_id' => $this->faker->numberBetween(1, 100),
            'secret' => $this->faker->sha256,
            'connection_type' => $this->faker->randomElement(IntegrationConnections::cases()),
            'url' => $this->faker->url,
            'status' => $this->faker->boolean,
        ];
    }
}
