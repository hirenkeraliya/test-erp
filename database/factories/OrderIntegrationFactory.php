<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\OrderIntegration\Enums\IntegrationStatuses;
use App\Models\Courier;
use App\Models\Order;
use App\Models\OrderIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderIntegration>
 */
class OrderIntegrationFactory extends Factory
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
            'courier_id' => fn () => Courier::factory()->create()->id,
            'status' => array_rand(array_flip(array_column(IntegrationStatuses::cases(), 'value'))),
        ];
    }
}
