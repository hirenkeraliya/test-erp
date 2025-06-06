<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BookingPayment;
use App\Models\BookingPaymentUse;
use App\Models\BookingPaymentVoidUse;
use App\Models\VoidSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPaymentVoidUse>
 */
class BookingPaymentVoidUseFactory extends Factory
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
            'booking_payment_uses_id' => fn () => BookingPaymentUse::factory()->create()->id,
            'void_sale_id' => fn () => VoidSale::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
