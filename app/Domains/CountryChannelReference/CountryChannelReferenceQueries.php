<?php

declare(strict_types=1);

namespace App\Domains\CountryChannelReference;

use App\Models\CountryChannelReference;

class CountryChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        CountryChannelReference::create($record);
    }

    public function getByCountryIdAndSaleChannelId(int $countryId, int $saleChannelId): ?CountryChannelReference
    {
        return CountryChannelReference::select('id', 'country_id', 'external_country_id')
            ->where('country_id', $countryId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByExternalCountryId(int $countryId, int $saleChannelId): ?int
    {
        return CountryChannelReference::where('country_id', $countryId)
            ->where('sale_channel_id', $saleChannelId)
            ->value('external_country_id');
    }
}
