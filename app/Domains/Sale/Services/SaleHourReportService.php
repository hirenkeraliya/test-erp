<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\HappyHourDiscount\Exports\SaleHourExport;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleHourReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$saleHours, $columns, $dateRange, $company, $location, $currencySymbol] = $this->preparedSalesByHour(
            $filterData,
            $companyId
        );

        return view('prints.sale_hour', [
            'saleHours' => $saleHours,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'locationName' => $location,
            'currencySymbol' => $currencySymbol,
        ])->render();
    }

    public function export(array $filterData, string $filename): BinaryFileResponse
    {
        [$saleHours, $columns, $dateRange, $company, $location, $currencySymbol] = $this->preparedSalesByHour(
            $filterData,
            $filterData['company_id']
        );

        return Excel::download(
            new SaleHourExport($saleHours, $company, $dateRange, $columns, $location, $currencySymbol),
            $filename
        );
    }

    public function preparedSalesByHour(array $filterData, int $companyId): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $dateRange = $this->prepareDateRange($filterData);

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSaleHourForPrint($filterData, $companyId);

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getSaleReturnHourForPrint($filterData, $companyId);

        $sales = $sales->merge($saleReturns);

        $location = 'All Locations';
        if (isset($filterData['location_id'])) {
            $locationQueries = resolve(LocationQueries::class);
            $location = $locationQueries->getStoreNameById((int) $filterData['location_id'], $companyId);
        }

        $saleHours = [
            'sales' => [],
        ];
        $columns = [
            'date' => 'Date',
        ];
        foreach ($sales->sortBy('happened_at') as $sale) {
            /** @var Carbon $happenedAt */
            $happenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);

            $date = $happenedAt->format('d/m/Y');
            $hour = $happenedAt->format('gA');

            if (! isset($saleHours['totals'][$hour])) {
                $saleHours['totals'][$hour] = 0;
            }

            if (! isset($saleHours['sales'][$date][$hour])) {
                $saleHours['sales'][$date][$hour] = 0;
                $columns[$hour] = $hour;
            }

            $total = 0;
            if ($sale instanceof Sale && $sale->offline_sale_id) {
                $saleHours['sales'][$date][$hour] += $sale->total_amount_paid;
                $saleHours['totals'][$hour] += $sale->total_amount_paid;
                $total += $sale->total_amount_paid;
            }

            if ($sale instanceof SaleReturn && $sale->offline_sale_return_id) {
                $saleHours['sales'][$date][$hour] -= $sale->total_price_paid;
                $saleHours['totals'][$hour] -= $sale->total_price_paid;
                $total -= $sale->total_price_paid;
            }

            $saleHours['sales'][$date]['grand_total'] = ($saleHours['sales'][$date]['grand_total'] ?? 0) + $total;
        }

        if (isset($saleHours['totals'])) {
            $saleHours['grand_total'] = array_sum($saleHours['totals']);
        }

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return [$saleHours, $columns, $dateRange, $company, $location, $currency->getSymbol()];
    }

    /**
     * @return mixed[]
     */
    public function prepareDateRange(array $filterData): array
    {
        if (isset($filterData['date_range'][0]) && is_string(
            $filterData['date_range'][0]
        ) && $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $filterData['date_range'][0])) {
            $filterData['date_range'][0] = $carbonDate->format('d-m-Y') . ' (' . $carbonDate->format('l') . ')';
        }

        if (isset($filterData['date_range'][1]) && is_string(
            $filterData['date_range'][1]
        ) && $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $filterData['date_range'][1])) {
            $filterData['date_range'][1] = $carbonDate->format('d-m-Y') . ' (' . $carbonDate->format('l') . ')';
        }

        return $filterData['date_range'];
    }
}
