<?php

declare(strict_types=1);

namespace App\Domains\MemberAddressChannelReference;

use App\Domains\MemberAddress\MemberAddressQueries;
use App\Models\MemberAddressChannelReference;

class MemberAddressChannelReferenceQueries
{
    public function addNew(array $memberAddressExternalIdRecords): MemberAddressChannelReference
    {
        return MemberAddressChannelReference::create($memberAddressExternalIdRecords);
    }

    public function getByMemberAddressIdAndSaleChannelId(
        int $memberAddressId,
        int $saleChannelId
    ): ?MemberAddressChannelReference {
        return MemberAddressChannelReference::select(
            'id',
            'sale_channel_id',
            'member_address_id',
            'external_member_address_id'
        )
            ->where('member_address_id', $memberAddressId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,member_address_id,external_member_address_id';
    }

    public function getByMemberAddressId(int $externalMemberAddressId, int $saleChannelId): ?int
    {
        return MemberAddressChannelReference::select(
            'id',
            'sale_channel_id',
            'member_address_id',
            'external_member_address_id'
        )
            ->where('external_member_address_id', $externalMemberAddressId)
            ->where('sale_channel_id', $saleChannelId)
            ->first()
            ?->member_address_id;
    }

    public function firstOrCreate(array $memberAddressExternalIdRecords): MemberAddressChannelReference
    {
        return MemberAddressChannelReference::firstOrCreate([
            'sale_channel_id' => $memberAddressExternalIdRecords['sale_channel_id'],
            'member_address_id' => $memberAddressExternalIdRecords['member_address_id'],
            'external_member_address_id' => $memberAddressExternalIdRecords['external_member_address_id'],
        ]
        );
    }

    public function deleteById(int $id): void
    {
        $memberAddressChannelReference = MemberAddressChannelReference::select('id', 'member_address_id')->where(
            'external_member_address_id',
            $id
        )->first();

        $memberAddressQueries = resolve(MemberAddressQueries::class);

        if ($memberAddressChannelReference) {
            $memberAddressQueries->deleteAddressById($memberAddressChannelReference->member_address_id);
            $memberAddressChannelReference->delete();
        }
    }
}
