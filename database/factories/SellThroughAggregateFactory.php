<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\SellThroughAggregate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellThroughAggregate>
 */
class SellThroughAggregateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'date' => now()->format('Y-m-d'),
            'location_id' => fn () => Location::factory()->create()->getKey(),
            'product_id' => fn () => Product::factory()->create()->getKey(),
            'goods_receive_note_in' => 0.0,
            'goods_receive_note_out' => 0.0,
            'stock_adjustment_in' => 0.0,
            'stock_adjustment_out' => 0.0,
            'stock_transfer_in' => 0.0,
            'stock_transfer_out' => 0.0,
            'delivery_order_in' => 0.0,
            'delivery_order_out' => 0.0,
            'foc_sold' => 0.0,
            'sold' => 0.0,
            'return' => 0.0,
        ];
    }
}
