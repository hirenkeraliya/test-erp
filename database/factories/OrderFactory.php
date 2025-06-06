<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderTypes;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\StoreManager;
use App\Models\VoidSaleReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
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
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'order_return_id' => fn () => OrderReturn::factory()->create()->id,
            'receipt_number' => fake()->numberBetween(111_111_111, 999_999_999),
            'total_tax_amount' => fake()->randomFloat(6, 0, 100),
            'cart_discount_amount' => fake()->randomFloat(6, 0, 100),
            'item_discount_amount' => fake()->randomFloat(6, 0, 100),
            'total_discount_amount' => fake()->randomFloat(6, 0, 100),
            'total_amount_before_round_off' => fake()->randomFloat(6, 0, 100),
            'round_off' => fake()->randomFloat(6, 0, 100),
            'total_amount_paid' => fake()->randomFloat(6, 0, 100),
            'type_id' => array_rand(array_flip(array_column(OrderTypes::cases(), 'value'))),
            'channel_id' => array_rand(array_flip(array_column(OrderChannels::cases(), 'value'))),
            'cancel_order_reason_id' => fn () => VoidSaleReason::factory()->create()->id,
            'pickup_location_id' => null,
            'tracking_number' => fake()->randomNumber(),
            'tracking_url' => fake()->url(),
            'shipment_order_number' => fake()->text(10),
            'courier_name' => fake()->company,
        ];
    }
}
