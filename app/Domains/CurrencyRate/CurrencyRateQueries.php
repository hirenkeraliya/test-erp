<?php

declare(strict_types=1);

namespace App\Domains\CurrencyRate;

use App\Domains\Company\DataObjects\CurrencyRateData;
use App\Domains\Currency\CurrencyQueries;
use App\Models\CurrencyRate;
use Illuminate\Support\Collection;

class CurrencyRateQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,rate,currency_id';
    }

    public function add(array $data): void
    {
        CurrencyRate::create($data);
    }

    public function deleteOldRate(int $companyId): void
    {
        CurrencyRate::select('id')
            ->where('company_id', $companyId)
            ->delete();
    }

    public function getByCompanyId(int $companyId, int $currencyId): Collection
    {
        $currencyQueries = new CurrencyQueries();

        return CurrencyRate::select('id', 'currency_id', 'rate')
            ->with(['currency:' .$currencyQueries->getColumnNamesForUpdate()])
            ->whereNot('currency_id', $currencyId)
            ->where('company_id', $companyId)
            ->get();
    }

    public function currencyRateUpdateByCompanyId(CurrencyRateData $currencyRateData): void
    {
        foreach ($currencyRateData->currency_data as $currency) {
            /** @var CurrencyRate $currencyRate */
            $currencyRate = CurrencyRate::select('id')
                ->where('company_id', $currencyRateData->company_id)
                ->findOrFail($currency['id']);

            $currencyRate->rate = $currency['rate'];
            $currencyRate->save();
        }
    }
}
