<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductAging;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAging>
 */
class ProductAgeingFactory extends Factory
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
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'product_created_at' => fake()->date,
            'last_selling_date' => fake()->date,
            'first_transfer_in' => fake()->date,
            'first_goods_received_note' => fake()->date,
            'quantity_sold' => random_int(0, 100),
            'quantity_remaining' => random_int(0, 100),
            'first_month_sold' => random_int(0, 100),
            'second_month_sold' => random_int(0, 100),
            'third_month_sold' => random_int(0, 100),
            'fourth_month_sold' => random_int(0, 100),
            'fifth_month_sold' => random_int(0, 100),
            'sixth_month_sold' => random_int(0, 100),
            'seventh_month_sold' => random_int(0, 100),
            'eighth_month_sold' => random_int(0, 100),
            'ninth_month_sold' => random_int(0, 100),
            'tenth_month_sold' => random_int(0, 100),
            'eleventh_month_sold' => random_int(0, 100),
            'twelfth_month_sold' => random_int(0, 100),
        ];
    }
}
