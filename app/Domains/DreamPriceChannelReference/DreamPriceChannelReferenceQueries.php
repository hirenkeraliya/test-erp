<?php

declare(strict_types=1);

namespace App\Domains\DreamPriceChannelReference;

use App\Models\DreamPriceChannelReference;

class DreamPriceChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        DreamPriceChannelReference::create($record);
    }

    public function getByDreamPriceIdAndSaleChannelId(
        int $dreamPriceId,
        int $saleChannelId
    ): ?DreamPriceChannelReference {
        return DreamPriceChannelReference::select('id', 'dream_price_id', 'external_dream_price_id')
            ->where('dream_price_id', $dreamPriceId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByDreamPriceIdAsAndSaleChannelId(array $dreamPriceIds, int $saleChannelId): ?array
    {
        return DreamPriceChannelReference::select('id', 'dream_price_id', 'external_dream_price_id')
            ->whereIn('external_dream_price_id', $dreamPriceIds)
            ->where('sale_channel_id', $saleChannelId)
            ->pluck('dream_price_id')->toArray();
    }

    public function getByExternalDreamPriceIdAndSaleChannelId(
        int $dreamPriceId,
        int $saleChannelId
    ): ?DreamPriceChannelReference {
        return DreamPriceChannelReference::select('id', 'dream_price_id', 'external_dream_price_id')
            ->where('external_dream_price_id', $dreamPriceId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getDreamPriceIdIdForEcommerce(int $productCollectionId): ?DreamPriceChannelReference
    {
        return DreamPriceChannelReference::query()
            ->select('id', 'dream_price_id', 'external_dream_price_id')
            ->where('dream_price_id', $productCollectionId)
            ->first();
    }
}
