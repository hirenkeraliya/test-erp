<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Sale\Enums\SeasonalReportTypes;
use App\Domains\Sale\Exports\SeasonalSalesByDetailsExport;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SeasonalCustomDetailReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $saleSeasonQueries = resolve(SaleSeasonQueries::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $saleSeason = $saleSeasonQueries->getById($filterData['sale_season_id'], $company->id);
        $seasonalDateRange = [$saleSeason->start_date, $saleSeason->end_date];
        $reportType = SeasonalReportTypes::BY_SEASON->value;

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSeasonalSalesData($filterData, $seasonalDateRange, $company->id, 'sale');

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getSeasonalSaleReturnsData(
            $filterData,
            $seasonalDateRange,
            $company->id,
            'sale_return'
        );

        [$seasonalSalesData, $grandTotal, $columns] = $this->prepareDetailsReport($sales, $saleReturns);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.seasonal_sales_by_details', [
            'seasonalSalesData' => $seasonalSalesData,
            'columns' => $columns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'dateRange' => $seasonalDateRange,
            'company' => $company,
            'grandTotal' => $grandTotal,
            'reportType' => SeasonalReportTypes::getFormattedCaseName($reportType),
            'saleSeasonName' => $saleSeason->name,
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $saleSeasonQueries = resolve(SaleSeasonQueries::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $saleSeason = $saleSeasonQueries->getById($filterData['sale_season_id'], $company->id);
        $seasonalDateRange = [$saleSeason->start_date, $saleSeason->end_date];
        $reportType = SeasonalReportTypes::BY_SEASON->value;

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSeasonalSalesData($filterData, $seasonalDateRange, $company->id, 'sale');

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getSeasonalSaleReturnsData(
            $filterData,
            $seasonalDateRange,
            $company->id,
            'sale_return'
        );

        [$seasonalSalesData, $grandTotal, $columns] = $this->prepareDetailsReport($sales, $saleReturns);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return Excel::download(
            new SeasonalSalesByDetailsExport(
                $seasonalSalesData,
                $company,
                $grandTotal,
                $columns,
                $seasonalDateRange,
                $reportType,
                $saleSeason->name,
                $currency->getSymbol()
            ),
            $filename
        );
    }

    public function prepareDetailsReport(Collection $sales, Collection $saleReturns): array
    {
        $brandLocationsSales = [];
        $columns = [
            0 => 'Location Name',
        ];
        $grandTotal = [
            'location_name' => 'Grand Total',
            'total' => 0,
        ];

        $totals = [];

        foreach ($sales->groupBy('brand_id') as $brandSales) {
            $firstBrandSale = $brandSales->first();

            $totals[$firstBrandSale->brand_id] = [
                'location_name' => 'Total',
                'total' => 0,
            ];

            if (! array_key_exists($firstBrandSale->brand_id, $brandLocationsSales)) {
                $brandLocationsSales[$firstBrandSale->brand_id] = [
                    'brand_name' => $firstBrandSale->brand_name,
                    'brand_total' => 0,
                    'locations' => [],
                ];
            }

            /** @var array $locations */
            $locations = [];

            foreach ($brandSales as $brandSale) {
                if (! array_key_exists($brandSale->location_id, $locations)) {
                    $locations[$brandSale->location_id] = [
                        'location_name' => $brandSale->location_name,
                        'total' => 0,
                    ];
                }

                /** @var int|string $dateKey */
                $dateKey = str_replace('-', '', $brandSale->happened_at);
                if (! array_key_exists($dateKey, $locations[$brandSale->location_id])) {
                    $locations[$brandSale->location_id][$dateKey] = 0;
                }

                if (! array_key_exists($dateKey, $columns)) {
                    /** @var Carbon $dateFormat */
                    $dateFormat = Carbon::createFromFormat('Y-m-d', $brandSale->happened_at);
                    $columns[$dateKey] = $dateFormat->format('d/m/Y');
                }

                if (! array_key_exists($dateKey, $totals[$firstBrandSale->brand_id])) {
                    $totals[$firstBrandSale->brand_id][$dateKey] = 0;
                }

                if (! array_key_exists($dateKey, $grandTotal)) {
                    $grandTotal[$dateKey] = 0;
                }

                $locations[$brandSale->location_id][$dateKey] += $brandSale->sale;
                $locations[$brandSale->location_id]['total'] += $brandSale->sale;
                $totals[$firstBrandSale->brand_id][$dateKey] += $brandSale->sale;
                $grandTotal[$dateKey] += $brandSale->sale;

                $totals[$firstBrandSale->brand_id]['total'] += $brandSale->sale ?? 0;
            }

            $grandTotal['total'] += $totals[$firstBrandSale->brand_id]['total'];
            $brandLocationsSales[$firstBrandSale->brand_id]['locations'] = $locations;
        }

        foreach ($saleReturns->groupBy('brand_id') as $brandSaleReturns) {
            $firstBrandSaleReturn = $brandSaleReturns->first();
            if (! array_key_exists($firstBrandSaleReturn->brand_id, $totals)) {
                $totals[$firstBrandSaleReturn->brand_id] = [
                    'location_name' => 'Total',
                    'total' => 0,
                ];
            }

            if (! array_key_exists($firstBrandSaleReturn->brand_id, $brandLocationsSales)) {
                $brandLocationsSales[$firstBrandSaleReturn->brand_id] = [
                    'brand_name' => $firstBrandSaleReturn->brand_name,
                    'brand_total' => 0,
                    'locations' => [],
                ];
            }

            foreach ($brandSaleReturns as $brandSaleReturn) {
                if (! array_key_exists(
                    $brandSaleReturn->location_id,
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations']
                )) {
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations'][$brandSaleReturn->location_id] = [
                        'location_name' => $brandSaleReturn->location_name,
                        'total' => 0,
                    ];
                }

                /** @var int|string $dateKey */
                $dateKey = str_replace('-', '', $brandSaleReturn->happened_at);

                if (! array_key_exists(
                    $dateKey,
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations'][$brandSaleReturn->location_id]
                )) {
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations'][$brandSaleReturn->location_id][$dateKey] = 0;
                }

                if (! array_key_exists($dateKey, $columns)) {
                    /** @var Carbon $dateFormat */
                    $dateFormat = Carbon::createFromFormat('Y-m-d', $brandSaleReturn->happened_at);
                    $columns[$dateKey] = $dateFormat->format('d/m/Y');
                }

                if (! array_key_exists($dateKey, $totals[$firstBrandSaleReturn->brand_id])) {
                    $totals[$firstBrandSaleReturn->brand_id][$dateKey] = 0;
                }

                if (! array_key_exists($dateKey, $grandTotal)) {
                    $grandTotal[$dateKey] = 0;
                }

                $brandLocationsSales[$brandSaleReturn->brand_id]['locations'][$brandSaleReturn->location_id][$dateKey] -= $brandSaleReturn->sale_return ?? 0;

                $brandLocationsSales[$brandSaleReturn->brand_id]['locations'][$brandSaleReturn->location_id]['total'] -= $brandSaleReturn->sale_return ?? 0;

                if (array_key_exists('brand_total', $brandLocationsSales[$brandSaleReturn->brand_id]['locations'])) {
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations']['brand_total'] -= $brandSaleReturn->sale_return ?? 0;
                }

                $totals[$firstBrandSaleReturn->brand_id]['total'] -= $brandSaleReturn->sale_return ?? 0;
                $grandTotal['total'] -= $brandSaleReturn->sale_return ?? 0;

                $totals[$firstBrandSaleReturn->brand_id][$dateKey] -= $brandSaleReturn->sale_return ?? 0;
                $grandTotal[$dateKey] -= $brandSaleReturn->sale_return ?? 0;
            }
        }

        foreach ($totals as $brandId => $total) {
            $brandLocationsSales[$brandId]['locations'][] = $total;
        }

        $columns[] = 'Total';
        $columns = collect($columns)->sortKeys()->toArray();

        return [$brandLocationsSales, $grandTotal, $columns];
    }
}
