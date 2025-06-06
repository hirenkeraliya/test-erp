<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BoxProduct;
use App\Models\BoxProductLoyaltyPoint;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoxProductLoyaltyPoint>
 */
class BoxProductLoyaltyPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'box_product_id' => fn () => BoxProduct::factory()->create()->id,
            'membership_id' => fn () => Membership::factory()->create()->id,
            'points' => random_int(1, 9999),
        ];
    }
}
