<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CountryChannelReference>
 */
class CountryChannelReferenceFactory extends Factory
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
            'country_id' => fn () => Country::factory()->create()->id,
            'external_country_id' => random_int(1, 10),
        ];
    }
}
