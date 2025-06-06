<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\MemberGroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberGroupMember>
 */
class MemberGroupMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'member_group_id' => fn () => MemberGroup::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'is_synced' => true,
        ];
    }
}
