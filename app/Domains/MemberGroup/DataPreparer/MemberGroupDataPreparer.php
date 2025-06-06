<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\DataPreparer;

use App\Models\Member;
use App\Models\MemberGroupMember;
use Illuminate\Support\Collection;

class MemberGroupDataPreparer
{
    public function getMemberGroup(Member $member): ?array
    {
        if ($member->memberGroupMembers->isEmpty()) {
            return null;
        }

        return [
            'id' => $member->memberGroupMembers->first()?->memberGroup?->id,
            'name' => $member->memberGroupMembers->first()?->memberGroup?->name,
            'code' => $member->memberGroupMembers->first()?->memberGroup?->code,
        ];
    }

    public function getMemberGroups(Member $member): Collection
    {
        return $member->memberGroupMembers->map(fn (MemberGroupMember $memberGroupMember): array => [
            'id' => $memberGroupMember->memberGroup?->id,
            'name' => $memberGroupMember->memberGroup?->name,
            'code' => $memberGroupMember->memberGroup?->code,
        ]);
    }
}
