<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Sale\Enums\SalesOverallReportTypes;
use App\Domains\Sale\Exports\SalesOverallByStoreTotalOrReceiptExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesOverallReportService
{
    public function printSaleOverall(int $companyId, array $filterData, ?int $locationId = null): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        [$locationSaleDetails, $columns] = $this->generateSalesReport($filterData, $locationId);

        return view('prints.sales_overall_by_store', [
            'allLocations' => $locationSaleDetails,
            'dateRange' => $dateRange,
            'reportType' => SalesOverallReportTypes::getFormattedCaseName((int) $filterData['report_by']),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
        ])->render();
    }

    public function exportSaleOverall(
        int $companyId,
        array $filterData,
        string $filename,
        ?int $locationId = null
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        [$saleCollections, $columns] = $this->generateSalesReport($filterData, $locationId);

        return Excel::download(
            new SalesOverallByStoreTotalOrReceiptExport(
                $saleCollections,
                $columns,
                $company,
                $dateRange,
                SalesOverallReportTypes::getFormattedCaseName((int) $filterData['report_by'])
            ),
            $filename
        );
    }

    public function generateSalesReport(array $filterData, ?int $locationId = null): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $sales = $counterUpdateQueries->getForSalesOverallByFilter($filterData, $locationId);

        $monthNumbers = $sales->pluck('month')->unique()->filter()->sort()->toArray();

        $monthAbbreviations = [];

        foreach ($monthNumbers as $monthNumber) {
            $date = Carbon::createFromDate((int) date('Y'), $monthNumber, 1);
            $monthAbbreviations[] = $date->format('F');
        }

        $columns = ['Location Name', ...$monthAbbreviations, 'Grand Total'];

        $locationSaleDetails = [];
        $totals = [
            'location_name' => 'Grand Total',
            'total_collection' => [],
            'grand_total' => 0,
        ];

        foreach ($sales->sortBy('month')->groupBy(
            ['location_id', 'month'],
            true
        )->toArray() as $salesByMonthsAndStores) {
            $sale = current(current($salesByMonthsAndStores));
            $locationSaleDetails[$sale['location_id']] = [
                'location_name' => $sale['location_name'],
                'total_collection' => [],
                'grand_total' => 0,
            ];

            foreach (array_diff(
                array_values($monthNumbers),
                array_keys($salesByMonthsAndStores)
            ) as $handleMissingMonth) {
                $salesByMonthsAndStores[$handleMissingMonth] = [
                    [
                        'location_id' => $sale['location_id'],
                        'location_name' => $sale['location_name'],
                        'month' => $handleMissingMonth,
                        'sale_collection_amount' => 0,
                        'total_sales' => 0,
                        'total_sale_returns' => 0,
                    ],
                ];
            }

            ksort($salesByMonthsAndStores);

            foreach ($salesByMonthsAndStores as $sales) {
                $sales = current($sales);

                $totalSales = ((int) $filterData['report_by'] === SalesOverallReportTypes::BY_NET_TOTAL->value)
                    ? (float) $sales['sale_collection_amount']
                    : (float) ($sales['total_sales'] + $sales['total_sale_returns']);

                if (! array_key_exists($sales['month'], $totals['total_collection'])) {
                    $totals['total_collection'][$sales['month']] = 0;
                }

                $locationSaleDetails[$sales['location_id']]['total_collection'][] = $totalSales;
                $locationSaleDetails[$sales['location_id']]['grand_total'] += $totalSales;
                $totals['total_collection'][$sales['month']] += $totalSales;
                $totals['grand_total'] += $totalSales;
            }
        }

        $locationSaleDetails['totals'] = $totals;

        return [$locationSaleDetails, $columns];
    }
}
