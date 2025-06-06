<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
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
            'name' => fake()->text(),
            'country_id' => fn () => Country::factory()->create()->id,
        ];
    }
}
