<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipAssignment>
 */
class MembershipAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_id' => fn () => Membership::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'happened_at' => fake()->dateTime(),
        ];
    }
}
