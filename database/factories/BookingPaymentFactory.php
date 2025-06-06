<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Models\BookingPayment;
use App\Models\CounterUpdate;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPayment>
 */
class BookingPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offline_id' => fake()->randomFloat(2, 100, 1000) . fake()->randomNumber(),
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'total_amount' => fake()->randomFloat(2, 0, 100),
            'available_amount' => fake()->randomFloat(2, 0, 100),
            'status' => array_rand(array_flip(array_column(BookingPaymentStatuses::cases(), 'value'))),
            'remarks' => fake()->text(50),
            'bill_reference_number' => fake()->word(),
        ];
    }
}
