<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLoyaltyPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderLoyaltyPoint>
 */
class OrderLoyaltyPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_id' => fn () => Order::factory()->create()->id,
            'loyalty_points' => fake()->randomNumber(),
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
