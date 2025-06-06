<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Category;
use App\Models\CategoryWiseDailyTotal;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryWiseDailyTotal>
 */
class CategoryWiseDailyTotalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'category_id' => fn () => Category::factory()->create()->id,
            'date' => Carbon::now(),
            'total_units_sold' => random_int(1, 9999),
            'total_amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
