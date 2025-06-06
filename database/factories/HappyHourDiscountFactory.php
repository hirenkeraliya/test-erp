<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturn>
 */
class HappyHourDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'company_id' => fn () => Company::factory()->create()->id,
            'product_type_id' => array_rand(array_flip(array_column(ProductTypes::cases(), 'value'))),
            'name' => fake()->name(),
            'new_price' => fake()->randomFloat(2, 0, 100),
            'start_date' => fake()->dateTime(),
            'end_date' => fake()->dateTime(),
        ];
    }
}
