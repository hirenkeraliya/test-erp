<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPayment>
 */
class OrderPaymentFactory extends Factory
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
            'store_manager_id' => fn () => StoreManager::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'payment_type_id' => fn () => PaymentType::factory()->create()->id,
            'amount' => fake()->randomFloat(6, 0, 100),
        ];
    }
}
