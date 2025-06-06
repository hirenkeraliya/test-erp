<?php

declare(strict_types=1);

namespace App\Domains\MembershipAssignment;

use App\Models\MembershipAssignment;

class MembershipAssignmentQueries
{
    public function addNew(int $membershipId, int $memberId, string $happenedAt): void
    {
        MembershipAssignment::create([
            'membership_id' => $membershipId,
            'member_id' => $memberId,
            'happened_at' => $happenedAt,
        ]);
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $memberships = MembershipAssignment::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($memberships as $membership) {
            $membership->member_id = $newMemberId;
            $membership->save();
        }
    }
}
