<?php

declare(strict_types=1);

namespace App\Domains\Currency;

use App\Domains\Country\CountryQueries;
use App\Models\Currency;

class CurrencyQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,symbol,country_id,code,name';
    }

    public function getColumnNamesForUpdate(): string
    {
        return 'id,name,code,symbol';
    }

    public function getByCompanyId(int $companyId): Currency
    {
        $countryQueries = resolve(CountryQueries::class);

        return Currency::select('id', 'symbol', 'country_id', 'code')
            ->whereHas('country', $countryQueries->filterByCompanyId($companyId))
            ->firstOrFail();
    }

    public function getByCompanyIdWithCountry(int $companyId): Currency
    {
        $countryQueries = resolve(CountryQueries::class);

        return Currency::query()
            ->select('id', 'country_id', 'name', 'code', 'symbol')
            ->with(['country:' . $countryQueries->getBasicColumnNames()])
            ->whereHas('country', $countryQueries->filterByCompanyId($companyId))
            ->firstOrFail();
    }

    public function getBySymbolWithCountry(string $symbol): ?Currency
    {
        $countryQueries = resolve(CountryQueries::class);

        return Currency::query()
            ->select('id', 'country_id', 'name', 'code', 'symbol')
            ->with(['country:' . $countryQueries->getBasicColumnNames()])
            ->where('symbol', $symbol)
            ->first();
    }
}
