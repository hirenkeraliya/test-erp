<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\OrderDiscount\Enums\DiscountableTypes;
use App\Models\Order;
use App\Models\OrderDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderDiscount>
 */
class OrderDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $discountableType = DiscountableTypes::getDiscountableClass(DiscountableTypes::VOUCHER->value);

        return [
            'order_id' => fn () => Order::factory()->create()->id,
            'discountable_type' => $discountableType,
            'discountable_id' => fn () => $discountableType::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
