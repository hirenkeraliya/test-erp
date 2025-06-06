<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HoldBookingPaymentItem;
use App\Models\HoldSaleDetail;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HoldBookingPaymentItem>
 */
class HoldBookingPaymentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'hold_sale_detail_id' => fn () => HoldSaleDetail::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
