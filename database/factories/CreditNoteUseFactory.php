<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BookingPaymentPayment;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\CreditNoteUse;
use App\Models\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditNoteUse>
 */
class CreditNoteUseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_note_id' => fn () => CreditNote::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'sale_payment_id' => fn () => SalePayment::factory()->create()->id,
            'booking_payment_payment_id' => fn () => BookingPaymentPayment::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
