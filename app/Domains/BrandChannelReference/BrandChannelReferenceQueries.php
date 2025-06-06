<?php

declare(strict_types=1);

namespace App\Domains\BrandChannelReference;

use App\Models\BrandChannelReference;
use Illuminate\Support\Collection;

class BrandChannelReferenceQueries
{
    public function addNew(array $brandExternalIdRecords): BrandChannelReference
    {
        return BrandChannelReference::create($brandExternalIdRecords);
    }

    public function getByBrandIdAndSaleChannelId(int $brandId, int $saleChannelId): ?BrandChannelReference
    {
        return BrandChannelReference::select('id', 'sale_channel_id', 'brand_id', 'external_brand_id')
            ->where('brand_id', $brandId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByBrandIdsAndSaleChannelId(array $brandIds, int $saleChannelId): Collection
    {
        return BrandChannelReference::query()
            ->select('id', 'external_brand_id')
            ->whereIn('brand_id', $brandIds)
            ->where('sale_channel_id', $saleChannelId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,brand_id,external_brand_id';
    }
}
