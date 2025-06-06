<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Order\Enums\OrderStatus;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleChannelInventoryRollbackOrderStatus>
 */
class SaleChannelInventoryRollbackOrderStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->getKey(),
            'order_status' => array_rand(array_flip(array_column(OrderStatus::cases(), 'value'))),
        ];
    }
}
