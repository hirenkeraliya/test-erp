<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Membership;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLoyaltyPoint>
 */
class ProductLoyaltyPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'product_id' => fn () => Product::factory()->create()->id,
            'membership_id' => fn () => Membership::factory()->create()->id,
            'points' => random_int(1, 500),
        ];
    }
}
