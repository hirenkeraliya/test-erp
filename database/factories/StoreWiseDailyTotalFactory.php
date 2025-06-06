<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Brand;
use App\Models\Company;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\StoreWiseDailyTotal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreWiseDailyTotal>
 */
class StoreWiseDailyTotalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'company_id' => fn () => Company::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'brand_id' => fn () => Brand::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'total_sales_count' => random_int(1, 9999),
            'total_units_sold' => random_int(1, 9999),
            'total_sales_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
