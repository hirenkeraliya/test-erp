<?php

declare(strict_types=1);

namespace App\Domains\ColorChannelReference;

use App\Models\ColorChannelReference;

class ColorChannelReferenceQueries
{
    public function addNew(array $colorExternalIdRecords): ColorChannelReference
    {
        return ColorChannelReference::create($colorExternalIdRecords);
    }

    public function getByColorIdAndSaleChannelId(int $colorId, int $saleChannelId): ?ColorChannelReference
    {
        return ColorChannelReference::select('id', 'sale_channel_id', 'color_id', 'external_color_id')
            ->where('color_id', $colorId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,color_id,external_color_id';
    }
}
