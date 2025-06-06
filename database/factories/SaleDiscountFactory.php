<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\SaleDiscount\Enums\DiscountableTypes;
use App\Models\Sale;
use App\Models\SaleDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleDiscount>
 */
class SaleDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $discountableType = DiscountableTypes::getDiscountableClass(DiscountableTypes::PROMOTION->value);

        return [
            'sale_id' => fn () => Sale::factory()->create()->id,
            'discountable_type' => $discountableType,
            'discountable_id' => fn () => $discountableType::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
