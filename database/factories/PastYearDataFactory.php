<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Location;
use App\Models\PastYearData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PastYearData>
 */
class PastYearDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'date' => now()->subYear()->format('Y-m-d'),
            'sale_amount' => fake()->randomFloat(2, 0, 100),
            'total_sale' => fake()->randomFloat(2, 0, 100),
            'units_sold' => fake()->randomFloat(2, 0, 100),
            'return_amount' => fake()->randomFloat(2, 0, 100),
            'units_return' => fake()->randomFloat(2, 0, 100),
            'net_sales' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
