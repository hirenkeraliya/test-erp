<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaymentType;
use App\Models\StoreDayClose;
use App\Models\StoreDayClosePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreDayClosePayment>
 */
class StoreDayClosePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_day_close_id' => fn () => StoreDayClose::factory()->create()->id,
            'payment_type_id' => fn () => PaymentType::factory()->create()->id,
            'total_transactions' => random_int(1, 9999),
            'total_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
