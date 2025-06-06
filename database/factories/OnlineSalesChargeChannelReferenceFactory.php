<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OnlineSalesChargeChannelReference;
use App\Models\OnlineSalesCharges;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnlineSalesChargeChannelReference>
 */
class OnlineSalesChargeChannelReferenceFactory extends Factory
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
            'online_sales_charges_id' => fn () => OnlineSalesCharges::factory()->create()->id,
            'external_online_sales_charges_id' => random_int(1, 100),
        ];
    }
}
