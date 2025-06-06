<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CounterUpdateDeclarationAttempt;
use App\Models\CounterUpdateDeclarationAttemptPayment;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounterUpdateDeclarationAttemptPayment>
 */
class CounterUpdateDeclarationAttemptPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'counter_update_declaration_attempt_id' => fn () => CounterUpdateDeclarationAttempt::factory()->create()->id,
            'payment_type_id' => fn () => PaymentType::factory()->create()->id,
            'declared_amount' => fake()->randomFloat(2, 0, 100),
            'calculated_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
