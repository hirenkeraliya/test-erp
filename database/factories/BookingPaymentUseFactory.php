<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BookingPayment;
use App\Models\BookingPaymentUse;
use App\Models\CounterUpdate;
use App\Models\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPaymentUse>
 */
class BookingPaymentUseFactory extends Factory
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
            'sale_payment_id' => fn () => SalePayment::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
