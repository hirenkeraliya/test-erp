<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreManagerAuthorizationCode>
 */
class StoreManagerAuthorizationCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'store_manager_id' => fn () => StoreManager::factory()->create(),
            'code' => fake()->uuid,
            'expiry_date' => fake()->date(),
            'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
        ];
    }
}
