<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Sale\Exports\SeasonalSalesByComparisonExport;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Models\SaleSeason;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SeasonalCustomComparisonReportService
{
    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $saleSeason = $saleSeasonQueries->getById($filterData['sale_season_id'], $company->id);
        $compareSaleSeason = $saleSeasonQueries->getById($filterData['compare_sale_season_id'], $company->id);

        $seasonalDate = [
            $saleSeason->start_date,
            $saleSeason->end_date,
            $compareSaleSeason->start_date,
            $compareSaleSeason->end_date,
        ];

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSeasonalSalesData($filterData, $seasonalDate, $company->id, 'sale');
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getSeasonalSaleReturnsData(
            $filterData,
            $seasonalDate,
            $company->id,
            'sale_return'
        );

        [$seasonalSalesData, $grandTotal, $columns] = $this->prepareComparisonReport(
            $sales,
            $saleReturns,
            $saleSeason,
            $compareSaleSeason
        );

        return Excel::download(
            new SeasonalSalesByComparisonExport(
                $seasonalSalesData,
                $company,
                $grandTotal,
                $columns,
                $seasonalDate,
                $saleSeason->name,
                $compareSaleSeason->name
            ),
            $filename
        );
    }

    public function prepareComparisonReport(
        Collection $sales,
        Collection $saleReturns,
        SaleSeason $saleSeason,
        SaleSeason $compareSaleSeason
    ): array {
        $brandLocationsSales = [];
        $columns = [
            0 => 'Location Name',
        ];
        $grandTotal = [
            'location_name' => 'Grand Total',
            'total' => 0,
            'total_compare' => 0,
        ];

        $totals = [];

        foreach ($sales->groupBy('brand_id') as $brandSales) {
            $firstBrandSale = $brandSales->first();

            $totals[$firstBrandSale->brand_id] = [
                'location_name' => 'Total',
                'total' => 0,
                'total_compare' => 0,
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
                        'total_compare' => 0,
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
                $totals[$firstBrandSale->brand_id][$dateKey] += $brandSale->sale;
                $grandTotal[$dateKey] += $brandSale->sale;
                if ($this->isDateInRange($brandSale->happened_at, $saleSeason)) {
                    $locations[$brandSale->location_id]['total'] += $brandSale->sale;
                    $totals[$firstBrandSale->brand_id]['total'] += $brandSale->sale ?? 0;
                }

                if ($this->isDateInRange($brandSale->happened_at, $compareSaleSeason)) {
                    $locations[$brandSale->location_id]['total_compare'] += $brandSale->sale;
                    $totals[$firstBrandSale->brand_id]['total_compare'] += $brandSale->sale ?? 0;
                }
            }

            $grandTotal['total'] += $totals[$firstBrandSale->brand_id]['total'];
            $grandTotal['total_compare'] += $totals[$firstBrandSale->brand_id]['total_compare'];

            $brandLocationsSales[$firstBrandSale->brand_id]['locations'] = $locations;
        }

        foreach ($saleReturns->groupBy('brand_id') as $brandSaleReturns) {
            $firstBrandSaleReturn = $brandSaleReturns->first();
            if (! array_key_exists($firstBrandSaleReturn->brand_id, $totals)) {
                $totals[$firstBrandSaleReturn->brand_id] = [
                    'location_name' => 'Total',
                    'total' => 0,
                    'total_compare' => 0,
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
                        'total_compare' => 0,
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

                if ($this->isDateInRange($brandSaleReturn->happened_at, $saleSeason)) {
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations'][$brandSaleReturn->location_id]['total'] -= $brandSaleReturn->sale_return ?? 0;
                }

                if ($this->isDateInRange($brandSaleReturn->happened_at, $compareSaleSeason)) {
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations'][$brandSaleReturn->location_id]['total_compare'] -= $brandSaleReturn->sale_return ?? 0;
                }

                if (array_key_exists('brand_total', $brandLocationsSales[$brandSaleReturn->brand_id]['locations'])) {
                    $brandLocationsSales[$brandSaleReturn->brand_id]['locations']['brand_total'] -= $brandSaleReturn->sale_return ?? 0;
                }

                if ($this->isDateInRange($brandSaleReturn->happened_at, $saleSeason)) {
                    $totals[$firstBrandSaleReturn->brand_id]['total'] -= $brandSaleReturn->sale_return ?? 0;
                    $grandTotal['total'] -= $brandSaleReturn->sale_return ?? 0;
                }

                if ($this->isDateInRange($brandSaleReturn->happened_at, $compareSaleSeason)) {
                    $totals[$firstBrandSaleReturn->brand_id]['total_compare'] -= $brandSaleReturn->sale_return ?? 0;
                    $grandTotal['total_compare'] -= $brandSaleReturn->sale_return ?? 0;
                }

                $totals[$firstBrandSaleReturn->brand_id][$dateKey] -= $brandSaleReturn->sale_return ?? 0;
                $grandTotal[$dateKey] -= $brandSaleReturn->sale_return ?? 0;
            }
        }

        foreach ($totals as $brandId => $total) {
            $brandLocationsSales[$brandId]['locations'][] = $total;
        }

        $columns[] = 'total';
        $columns[] = 'total_compare';
        $columns[] = '%';
        $columns = collect($columns)->sortKeys()->toArray();

        $records = [];
        $recordsCompare = [];
        $compareArray = ['Location Name', 'total', 'total_compare', '%'];
        $newArray = [];

        foreach ($columns as $key => $value) {
            if (strlen((string) $key) === 8) {
                if (in_array($value, $compareArray)) {
                    $newArray[] = $value;
                    continue;
                }

                /** @var Carbon|false $date */
                $date = Carbon::createFromFormat('d/m/Y', $value);

                if ($date) {
                    if ($this->isDateInRange($date->format('Y-m-d'), $saleSeason)) {
                        $records[$key] = $value;
                    }

                    if ($this->isDateInRange($date->format('Y-m-d'), $compareSaleSeason)) {
                        $recordsCompare[$key] = $value;
                    }
                }
            }
        }

        $result = [];

        $count = max(count($recordsCompare), count($records));

        $keys = array_keys($records);
        $keysCompare = array_keys($recordsCompare);

        for ($i = 0; $i < $count; $i++) {
            if (0 === $i) {
                $result[] = 'Location Name';
            }

            if (isset($keys[$i])) {
                $result[$keys[$i]] = $records[$keys[$i]];
            }

            if (isset($keysCompare[$i])) {
                $result[$keysCompare[$i]] = $recordsCompare[$keysCompare[$i]];
            }
        }

        foreach ($newArray as $value) {
            $result[] = $value;
        }

        $columns = $result;

        return [$brandLocationsSales, $grandTotal, $columns];
    }

    public function isDateInRange(string $date, SaleSeason $saleSeason): bool
    {
        $timestamp = strtotime($date);
        $startTimestamp = strtotime($saleSeason->start_date);
        $endTimestamp = strtotime($saleSeason->end_date);

        return $timestamp >= $startTimestamp && $timestamp <= $endTimestamp;
    }
}
