<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaleChannel;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateChannelReference>
 */
class StateChannelReferenceFactory extends Factory
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
            'state_id' => fn () => State::factory()->create()->id,
            'external_state_id' => random_int(1, 10),
        ];
    }
}
