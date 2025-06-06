<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Sale\Exports\SeasonalSalesBySummaryExport;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SeasonalCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $saleSeasonQueries = resolve(SaleSeasonQueries::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $saleSeason = $saleSeasonQueries->getById($filterData['sale_season_id'], $company->id);
        $compareSaleSeason = $saleSeasonQueries->getById($filterData['compare_sale_season_id'], $company->id);

        $seasonalDate = [
            $filterData['sale_season_date_range'][0] ?? $saleSeason->start_date,
            $filterData['sale_season_date_range'][1] ?? $saleSeason->end_date,
        ];

        $seasonalDateCompare = [
            $filterData['sale_season_compare_date_range'][0] ?? $compareSaleSeason->start_date,
            $filterData['sale_season_compare_date_range'][1] ?? $compareSaleSeason->end_date,
        ];

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSeasonalSalesData($filterData, $seasonalDate, $company->id, 'sale');
        $salesCompare = $saleQueries->getSeasonalSalesData(
            $filterData,
            $seasonalDateCompare,
            $company->id,
            'sale_compare'
        );
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getSeasonalSaleReturnsData(
            $filterData,
            $seasonalDate,
            $company->id,
            'sale_return'
        );
        $saleReturnsCompare = $saleReturnQueries->getSeasonalSaleReturnsData(
            $filterData,
            $seasonalDateCompare,
            $company->id,
            'sale_return_compare'
        );

        [$seasonalSalesData, $grandTotal, $grandTotalCompare] = $this->prepareSummaryReport(
            $sales,
            $salesCompare,
            $saleReturns,
            $saleReturnsCompare
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.seasonal_sales_by_summary', [
            'seasonalSalesData' => $seasonalSalesData,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'grandTotal' => $grandTotal,
            'grandTotalCompare' => $grandTotalCompare,
            'saleSeasonName' => $saleSeason->name,
            'compareSaleSeasonName' => $compareSaleSeason->name,
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $saleSeason = $saleSeasonQueries->getById($filterData['sale_season_id'], $company->id);
        $compareSaleSeason = $saleSeasonQueries->getById($filterData['compare_sale_season_id'], $company->id);

        $seasonalDate = [$filterData['sale_season_date_range'][0], $filterData['sale_season_date_range'][1]];

        $seasonalDateCompare = [
            $filterData['sale_season_compare_date_range'][0],
            $filterData['sale_season_compare_date_range'][1],
        ];

        $saleQueries = resolve(SaleQueries::class);

        $sales = $saleQueries->getSeasonalSalesData($filterData, $seasonalDate, $company->id, 'sale');
        $salesCompare = $saleQueries->getSeasonalSalesData(
            $filterData,
            $seasonalDateCompare,
            $company->id,
            'sale_compare'
        );

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getSeasonalSaleReturnsData(
            $filterData,
            $seasonalDate,
            $company->id,
            'sale_return'
        );
        $saleReturnsCompare = $saleReturnQueries->getSeasonalSaleReturnsData(
            $filterData,
            $seasonalDateCompare,
            $company->id,
            'sale_return_compare'
        );

        [$seasonalSalesData, $grandTotal, $grandTotalCompare] = $this->prepareSummaryReport(
            $sales,
            $salesCompare,
            $saleReturns,
            $saleReturnsCompare
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return Excel::download(
            new SeasonalSalesBySummaryExport(
                $seasonalSalesData,
                $company,
                $grandTotal,
                $grandTotalCompare,
                $saleSeason->name,
                $compareSaleSeason->name,
                $currency->getSymbol()
            ),
            $filename
        );
    }

    public function prepareSummaryReport(
        Collection $sales,
        Collection $salesCompare,
        Collection $saleReturns,
        Collection $saleReturnsCompare
    ): array {
        $brandLocationsSales = [];
        $grandTotal = 0;
        $grandTotalCompare = 0;

        $salesAndReturns = $sales->merge($salesCompare)->concat($saleReturns)->concat($saleReturnsCompare);

        foreach ($salesAndReturns->groupBy('brand_id') as $brandId => $brandTransactions) {
            $brandTotal = 0;
            $brandTotalCompare = 0;
            $locations = [];

            foreach ($brandTransactions as $transaction) {
                $locationId = $transaction->location_id ?? null;

                $locationIndex = array_search($locationId, array_column($locations, 'location_id'), true);
                if (false === $locationIndex) {
                    $locationIndex = count($locations);
                    $locations[] = [
                        'location_id' => $locationId,
                        'location_name' => $transaction->location_name,
                        'total_amount' => 0,
                        'total_amount_compare' => 0,
                    ];
                }

                $locations[$locationIndex]['total_amount'] += array_key_exists(
                    'sale',
                    (array) $transaction
                ) ? $transaction->sale : 0;
                $locations[$locationIndex]['total_amount_compare'] += array_key_exists(
                    'sale_compare',
                    (array) $transaction
                ) ? $transaction->sale_compare : 0;
                $locations[$locationIndex]['total_amount'] -= array_key_exists(
                    'sale_return',
                    (array) $transaction
                ) ? $transaction->sale_return : 0;
                $locations[$locationIndex]['total_amount_compare'] -= array_key_exists(
                    'sale_return_compare',
                    (array) $transaction
                ) ? $transaction->sale_return_compare : 0;

                $brandTotal += array_key_exists('sale', (array) $transaction) ? $transaction->sale : 0;
                $brandTotalCompare += array_key_exists(
                    'sale_compare',
                    (array) $transaction
                ) ? $transaction->sale_compare : 0;
                $brandTotal -= array_key_exists(
                    'sale_return',
                    (array) $transaction
                ) ? $transaction->sale_return : 0;
                $brandTotalCompare -= array_key_exists(
                    'sale_return_compare',
                    (array) $transaction
                ) ? $transaction->sale_return_compare : 0;
            }

            $grandTotal += $brandTotal;
            $grandTotalCompare += $brandTotalCompare;

            $brandLocationsSales[$brandId] = [
                'brand_id' => $brandId,
                'brand_name' => $brandTransactions->first()->brand_name,
                'brand_total' => $brandTotal,
                'brand_total_compare' => $brandTotalCompare,
                'locations' => $locations,
            ];
        }

        return [$brandLocationsSales, $grandTotal, $grandTotalCompare];
    }
}
