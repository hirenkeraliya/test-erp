<?php

declare(strict_types=1);

namespace App\Domains\MemberChannelReference;

use App\Models\MemberChannelReference;
use Illuminate\Support\Collection;

class MemberChannelReferenceQueries
{
    public function addNew(array $memberExternalIdRecords): MemberChannelReference
    {
        return MemberChannelReference::create($memberExternalIdRecords);
    }

    public function getByMemberIdAndSaleChannelId(int $memberId, int $saleChannelId): ?MemberChannelReference
    {
        return MemberChannelReference::select('id', 'sale_channel_id', 'member_id', 'external_member_id')
            ->where('member_id', $memberId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getRecordsByMemberId(int $memberId): Collection
    {
        return MemberChannelReference::query()
            ->select('id', 'sale_channel_id', 'member_id', 'external_member_id')
            ->where('member_id', $memberId)
            ->get();
    }

    public function deleteOldMemberForMerge(int $oldMemberId): void
    {
        MemberChannelReference::query()
            ->where('member_id', $oldMemberId)
            ->delete();
    }

    public function getByExternalMemberIdAndSaleChannelId(
        int $externalMemberId,
        int $saleChannelId
    ): ?MemberChannelReference {
        return MemberChannelReference::select('id', 'sale_channel_id', 'member_id', 'external_member_id')
            ->where('external_member_id', $externalMemberId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function updateOrCreate(array $memberExternalIdRecords): MemberChannelReference
    {
        return MemberChannelReference::firstOrCreate([
            'sale_channel_id' => $memberExternalIdRecords['sale_channel_id'],
            'member_id' => $memberExternalIdRecords['member_id'],
            'external_member_id' => $memberExternalIdRecords['external_member_id'],
        ]
        );
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,member_id,external_member_id';
    }

    public function getByMemberId(int $externalMemberId, int $saleChannelId): ?int
    {
        return MemberChannelReference::select('id', 'sale_channel_id', 'member_id', 'external_member_id')
            ->where('external_member_id', $externalMemberId)
            ->where('sale_channel_id', $saleChannelId)
            ->first()
            ?->member_id;
    }
}
