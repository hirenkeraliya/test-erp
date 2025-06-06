<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPickingList;
use App\Models\OrderPickingListItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPickingListItem>
 */
class OrderPickingListItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_picking_list_id' => fn () => OrderPickingList::factory()->create()->id,
            'order_id' => fn () => Order::factory()->create()->id,
        ];
    }
}
