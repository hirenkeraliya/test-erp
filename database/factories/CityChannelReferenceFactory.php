<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CityChannelReference>
 */
class CityChannelReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->id,
            'city_id' => fn () => City::factory()->create()->id,
            'external_city_id' => random_int(1, 10),
        ];
    }
}
