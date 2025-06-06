<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePlanItem>
 */
class PurchasePlanItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'purchase_plan_id' => fn () => PurchasePlan::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => fake()->randomFloat(2, 1, 1000),
            'transferred_quantity' => fake()->randomFloat(2, 1, 1000),
            'cost_price' => fake()->randomFloat(2, 1, 1000),
            'total_price' => fake()->randomFloat(2, 1, 1000),
            'remarks' => fake()->word(),
        ];
    }
}
