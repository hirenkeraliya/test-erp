<?php

declare(strict_types=1);

namespace App\Domains\StateChannelReference;

use App\Models\StateChannelReference;

class StateChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        StateChannelReference::create($record);
    }

    public function getByStateIdAndSaleChannelId(int $stateId, int $saleChannelId): ?StateChannelReference
    {
        return StateChannelReference::select('id', 'state_id', 'external_state_id')
            ->where('state_id', $stateId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByExternalStateIds(array $stateIds, int $saleChannelId): ?array
    {
        return StateChannelReference::where('sale_channel_id', $saleChannelId)
            ->whereIn('state_id', $stateIds)
            ->pluck('external_state_id')
            ->toArray();
    }

    public function getByExternalStateId(int $stateId, int $saleChannelId): ?int
    {
        return StateChannelReference::where('sale_channel_id', $saleChannelId)
            ->where('state_id', $stateId)
            ->value('external_state_id');
    }
}
