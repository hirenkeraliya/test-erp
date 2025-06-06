<?php

declare(strict_types=1);

namespace App\Domains\BannerChannelReference;

use App\Models\BannerChannelReference;

class BannerChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        BannerChannelReference::create($record);
    }

    public function getByBannerIdAndSaleChannelId(int $bannerId, int $saleChannelId): ?BannerChannelReference
    {
        return BannerChannelReference::select('id', 'banner_id', 'external_banner_id')
            ->where('banner_id', $bannerId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }
}
