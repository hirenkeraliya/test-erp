<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CloseCounterPayment;
use App\Models\CounterUpdate;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloseCounterPayment>
 */
class CloseCounterPaymentFactory extends Factory
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
            'payment_type_id' => fn () => PaymentType::factory()->create()->id,
            'total_transactions' => fake()->randomFloat(2, 0, 100),
            'total_amount' => random_int(1, 9999),
        ];
    }
}
