<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaleChannel;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZoneChannelReference>
 */
class ShippingZoneChannelReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create(),
            'shipping_zone_id' => fn () => ShippingZone::factory()->create(),
            'external_shipping_zone_id' => random_int(1, 10),
        ];
    }
}
