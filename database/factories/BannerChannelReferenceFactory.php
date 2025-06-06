<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Banner;
use App\Models\BannerChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BannerChannelReference>
 */
class BannerChannelReferenceFactory extends Factory
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
            'banner_id' => fn () => Banner::factory()->create()->id,
            'external_banner_id' => random_int(1, 100),
        ];
    }
}
