<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\CounterUpdate;
use App\Models\Director;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
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
            'offline_id' => (string) fake()->randomNumber(),
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'cash_movement_type_id' => array_rand(array_flip(array_column(CashMovementTypes::cases(), 'value'))),
            'cash_movement_reason_id' => fn () => CashMovementReason::factory()->create()->id,
            'other_reason' => fake()->word,
            'remarks' => 'R' . fake()->randomNumber(),
            'authorizer_id' => fn () => $authorizerType::factory()->create()->id,
            'authorizer_type' => ModelMapping::getCaseName($authorizerType),
            'amount' => fake()->randomFloat(2, 0, 100),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
