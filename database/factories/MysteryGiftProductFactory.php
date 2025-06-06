<?php

namespace Database\Factories;

use App\Models\MysteryGift;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class MysteryGiftProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mystery_gift_id' => MysteryGift::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
