<?php

declare(strict_types=1);

namespace App\Domains\PromotionChannelReference;

use App\Models\PromotionChannelReference;

class PromotionChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        PromotionChannelReference::create($record);
    }

    public function getByPromotionIdAndSaleChannelId(
        int $promotionId,
        int $saleChannelId
    ): ?PromotionChannelReference {
        return PromotionChannelReference::select('id', 'promotion_id', 'external_promotion_id')
            ->where('promotion_id', $promotionId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByPromotionId(int $promotionId): ?PromotionChannelReference
    {
        return PromotionChannelReference::query()
            ->select('id', 'external_promotion_id')
            ->where('promotion_id', $promotionId)
            ->first();
    }

    public function getByPromotionIdAsAndSaleChannelId(array $promotionIds, int $saleChannelId): ?array
    {
        return PromotionChannelReference::select('id', 'external_promotion_id', 'promotion_id')
            ->whereIn('external_promotion_id', $promotionIds)
            ->where('sale_channel_id', $saleChannelId)
            ->pluck('promotion_id')->toArray();
    }

    public function getByExternalPromotionIdAndSaleChannelId(
        int $promotionId,
        int $saleChannelId
    ): ?PromotionChannelReference {
        return PromotionChannelReference::select('id', 'external_promotion_id', 'promotion_id')
            ->where('external_promotion_id', $promotionId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }
}
