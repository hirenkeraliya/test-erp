<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandChannelReference>
 */
class BrandChannelReferenceFactory extends Factory
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
            'brand_id' => fn () => Brand::factory()->create()->id,
            'external_brand_id' => fake()->randomNumber(),
        ];
    }
}
