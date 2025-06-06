<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaleChannel;
use App\Models\Size;
use App\Models\SizeChannelReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SizeChannelReference>
 */
class SizeChannelReferenceFactory extends Factory
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
            'size_id' => fn () => Size::factory()->create()->id,
            'external_size_id' => fake()->randomNumber(),
        ];
    }
}
