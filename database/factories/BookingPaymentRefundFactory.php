<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BookingPayment;
use App\Models\BookingPaymentRefund;
use App\Models\CounterUpdate;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPaymentRefund>
 */
class BookingPaymentRefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_payment_id' => fn () => BookingPayment::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'payment_type_id' => fn () => PaymentType::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
