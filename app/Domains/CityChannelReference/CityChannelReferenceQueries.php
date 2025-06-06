<?php

declare(strict_types=1);

namespace App\Domains\CityChannelReference;

use App\Models\CityChannelReference;

class CityChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        CityChannelReference::create($record);
    }

    public function getByCityIdAndSaleChannelId(int $cityId, int $saleChannelId): ?CityChannelReference
    {
        return CityChannelReference::select('id', 'city_id', 'external_city_id')
            ->where('city_id', $cityId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }
}
