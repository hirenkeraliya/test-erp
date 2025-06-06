<?php

declare(strict_types=1);

namespace App\Domains\SizeChannelReference;

use App\Models\SizeChannelReference;

class SizeChannelReferenceQueries
{
    public function addNew(array $sizeExternalIdRecords): SizeChannelReference
    {
        return SizeChannelReference::create($sizeExternalIdRecords);
    }

    public function getBySizeIdAndSaleChannelId(int $sizeId, int $saleChannelId): ?SizeChannelReference
    {
        return SizeChannelReference::select('id', 'sale_channel_id', 'size_id', 'external_size_id')
            ->where('size_id', $sizeId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,size_id,external_size_id';
    }
}
