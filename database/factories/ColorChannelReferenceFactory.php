<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Color;
use App\Models\ColorChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColorChannelReference>
 */
class ColorChannelReferenceFactory extends Factory
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
            'color_id' => fn () => Color::factory()->create()->id,
            'external_color_id' => fake()->randomNumber(),
        ];
    }
}
