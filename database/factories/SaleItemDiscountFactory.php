<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\SaleItemDiscount\Enums\DiscountableTypes;
use App\Models\SaleItem;
use App\Models\SaleItemDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemDiscount>
 */
class SaleItemDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $discountableType = array_rand(array_flip(array_column(DiscountableTypes::cases(), 'value')));

        $discountableClass = DiscountableTypes::getDiscountableClass($discountableType);

        return [
            'sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'discountable_type' => $discountableType,
            'discountable_id' => fn () => $discountableClass::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
