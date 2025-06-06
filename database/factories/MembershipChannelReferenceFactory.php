<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Membership;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipChannelReference>
 */
class MembershipChannelReferenceFactory extends Factory
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
            'membership_id' => fn () => Membership::factory()->create()->id,
            'external_membership_id' => random_int(1, 100),
        ];
    }
}
