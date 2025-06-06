<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MemberAddress;
use App\Models\MemberAddressChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberAddressChannelReference>
 */
class MemberAddressChannelReferenceFactory extends Factory
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
            'member_address_id' => fn () => MemberAddress::factory()->create()->id,
            'external_member_address_id' => fake()->randomNumber(),
        ];
    }
}
