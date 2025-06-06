<?php

declare(strict_types=1);

namespace App\Domains\MembershipChannelReference;

use App\Models\MembershipChannelReference;
use Illuminate\Support\Collection;

class MembershipChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        MembershipChannelReference::create($record);
    }

    public function getByMembershipIdAndSaleChannelId(
        int $membershipId,
        int $saleChannelId
    ): ?MembershipChannelReference {
        return MembershipChannelReference::select('id', 'membership_id', 'external_membership_id')
            ->where('membership_id', $membershipId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBySaleChannelIdMembershipIds(array $membershipIds, int $saleChannelId): Collection
    {
        return MembershipChannelReference::select('id', 'membership_id', 'external_membership_id')
            ->whereIntegerInRaw('membership_id', $membershipIds)
            ->where('sale_channel_id', $saleChannelId)
            ->get();
    }
}
