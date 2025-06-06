<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MysteryGiftUsageFactory extends Factory
{
    public function definition()
    {
        return [
            'coupon_code' => $this->faker->unique()->word,
            'product_id' => $this->faker->randomDigitNotNull,
            'member_id' => $this->faker->randomDigitNotNull,
            'used_at' => null,
        ];
    }
}
