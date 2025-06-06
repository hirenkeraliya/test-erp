<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\CounterUpdate;
use App\Models\Director;
use App\Models\HappyHourDiscount;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturn>
 */
class HappyHourDiscountTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $authorizerType = 0 !== random_int(0, 1) ? Director::class : StoreManager::class;

        return [
            'happy_hour_discount_id' => fn () => HappyHourDiscount::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'offline_id' => (string) fake()->randomNumber(),
            'authorizer_id' => fn () => $authorizerType::factory()->create()->id,
            'authorizer_type' => ModelMapping::getCaseName($authorizerType),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
