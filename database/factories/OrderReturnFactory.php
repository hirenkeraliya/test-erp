<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'store_manager_id' => fn () => StoreManager::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'receipt_number' => fake()->numberBetween(111_111_111, 999_999_999),
            'original_order_id' => fn () => Order::factory()->create()->id,
            'total_tax_amount' => fake()->randomFloat(6, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(6, 0, 100),
            'item_discount_amount' => fake()->randomFloat(6, 0, 100),
            'total_discount_amount' => fake()->randomFloat(6, 0, 100),
            'total_amount_before_round_off' => fake()->randomFloat(6, 0, 100),
            'round_off_amount' => fake()->randomFloat(6, 0, 100),
            'total_price_paid' => fake()->randomFloat(6, 0, 100),
        ];
    }
}
