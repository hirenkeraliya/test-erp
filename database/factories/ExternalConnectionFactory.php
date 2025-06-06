<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\ExternalConnection\Enums\Statuses;
use App\Models\ExternalConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalConnection>
 */
class ExternalConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->word(),
            'url' => fake()->url(),
            'token' => fake()->uuid,
            'approved_at' => fake()->dateTime(),
            'create_by_super_admin_id' => null,
            'approve_by_super_admin_id' => null,
            'status' => Statuses::PENDING->value,
        ];
    }
}
