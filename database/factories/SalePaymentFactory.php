<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CounterUpdate;
use App\Models\PaymentType;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalePayment>
 */
class SalePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => fn () => Sale::factory()->create()->id,
            'payment_type_id' => fn () => PaymentType::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
