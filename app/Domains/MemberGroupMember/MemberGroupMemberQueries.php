<?php

declare(strict_types=1);

namespace App\Domains\MemberGroupMember;

use App\Domains\Media\MediaQueries;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Models\MemberGroupMember;
use Illuminate\Support\Collection;

class MemberGroupMemberQueries
{
    public function addNew(array $data): void
    {
        MemberGroupMember::create($data);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,member_id,member_group_id,is_synced';
    }

    public function getByMemberGroupId(int $memberGroupId): Collection
    {
        return MemberGroupMember::select('id', 'is_synced')
            ->where('is_synced', false)
            ->where('member_group_id', $memberGroupId)
            ->get();
    }

    public function checkExitsOrNot(int $memberId, int $memberGroupId): bool
    {
        return MemberGroupMember::where('member_id', $memberId)
            ->where('member_group_id', $memberGroupId)
            ->exists();
    }

    public function removeByMemberId(int $memberId, int $companyId): void
    {
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroupMembers = MemberGroupMember::select('id')
            ->whereHas('memberGroup', $memberGroupQueries->filterByCompany($companyId))
            ->where('member_id', $memberId)
            ->get();

        foreach ($memberGroupMembers as $memberGroupMember) {
            $memberGroupMember->delete();
        }
    }

    public function getMemberAndMemberGroupById(int $memberGroupMemberId): ?MemberGroupMember
    {
        $memberQueries = resolve(MemberQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return MemberGroupMember::select('id', 'member_id', 'member_group_id')
            ->with([
                'member' => function ($query) use ($memberQueries, $mediaQueries): void {
                    $query->select($memberQueries->getBasicColumnNamesForSaleChannelInArray())
                        ->with('media:' . $mediaQueries->getBasicColumnNames());
                },
            ])
            ->whereHas('member', $memberGroupQueries->filterByType(GroupTypes::MANUAL_GROUP->value))
            ->where('id', $memberGroupMemberId)
            ->first();
    }

    public function getMemberGroupIdByMemberId(int $memberId): ?int
    {
        $memberQueries = resolve(MemberQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        return MemberGroupMember::select('id', 'member_id', 'member_group_id')
            ->with('member:' . $memberQueries->getBasicColumnNamesForSaleChannel())
            ->whereHas('member', $memberGroupQueries->filterByType(GroupTypes::MANUAL_GROUP->value))
            ->where('member_id', $memberId)
            ->first()?->member_group_id;
    }

    public function refresh(MemberGroupMember $memberGroupMember): MemberGroupMember
    {
        return $memberGroupMember->refresh();
    }

    public function getEmailsByGroupId(int $memberGroupId, int $companyId): Collection
    {
        $memberQueries = resolve(MemberQueries::class);

        return MemberGroupMember::select('id', 'member_id', 'member_group_id')
            ->where('member_group_id', $memberGroupId)
            ->with('member:' . $memberQueries->getBasicColumnNamesForSaleChannel())
            ->whereHas('member', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->whereNotNull('email')
                    ->where('status', Status::ACTIVE->value);
            })
            ->get();
    }

    public function getMembersByMemberGroupIds(array $memberGroupIds, int $companyId): Collection
    {
        return MemberGroupMember::select('id', 'member_id', 'member_group_id')
            ->whereIntegerInRaw('member_group_id', $memberGroupIds)
            ->whereHas('member', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->where('status', Status::ACTIVE->value);
            })
            ->get();
    }
}
