<?php

declare(strict_types=1);

namespace App\Domains\MemberGroupChannelReference;

use App\Models\MemberGroupChannelReference;

class MemberGroupChannelReferenceQueries
{
    public function addNew(array $memberGroupExternalIdRecords): MemberGroupChannelReference
    {
        return MemberGroupChannelReference::create($memberGroupExternalIdRecords);
    }

    public function getByMemberGroupIdAndSaleChannelId(
        int $memberGroupId,
        int $saleChannelId
    ): ?MemberGroupChannelReference {
        return MemberGroupChannelReference::select(
            'id',
            'sale_channel_id',
            'member_group_id',
            'external_member_group_id'
        )
            ->where('member_group_id', $memberGroupId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function firstOrCreate(array $memberGroupExternalIdRecords): MemberGroupChannelReference
    {
        return MemberGroupChannelReference::firstOrCreate([
            'sale_channel_id' => $memberGroupExternalIdRecords['sale_channel_id'],
            'member_group_id' => $memberGroupExternalIdRecords['member_group_id'],
            'external_member_group_id' => $memberGroupExternalIdRecords['external_member_group_id'],
        ]
        );
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,member_group_id,external_member_group_id';
    }

    public function getByMemberGroupId(int $externalMemberGroupId, int $saleChannelId): ?int
    {
        return MemberGroupChannelReference::select(
            'id',
            'sale_channel_id',
            'member_group_id',
            'external_member_group_id'
        )
            ->where('external_member_group_id', $externalMemberGroupId)
            ->where('sale_channel_id', $saleChannelId)
            ->first()
            ?->member_group_id;
    }

    public function updateOrCreate(
        int $saleChannelId,
        int $memberGroupId,
        int $externalMemberGroupId
    ): MemberGroupChannelReference {
        return MemberGroupChannelReference::updateOrCreate([
            'sale_channel_id' => $saleChannelId,
            'member_group_id' => $memberGroupId,
        ], [
            'external_member_group_id' => $externalMemberGroupId,
        ]);
    }
}
