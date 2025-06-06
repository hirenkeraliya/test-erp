<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DreamPrice;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DreamPriceChannelReference>
 */
class DreamPriceChannelReferenceFactory extends Factory
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
            'dream_price_id' => fn () => DreamPrice::factory()->create()->id,
            'external_dream_price_id' => random_int(1, 100),
        ];
    }
}
