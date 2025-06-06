<?php

declare(strict_types=1);

namespace App\Domains\StockSummary\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockSummary\Enums\StockSummaryByModuleReportBy;
use App\Domains\StockSummary\Enums\StockSummaryByModuleReportType;
use App\Domains\StockSummary\Exports\StockSummaryByModuleReportExport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockSummaryByModuleReportService
{
    public function print(array $filterData, int $companyId, Collection $locations): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $reportType = $filterData['report_type'];
        $reportBy = $filterData['report_by'];

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $sellThroughAggregateData = $sellThroughAggregateQueries->getStockSummaryByModuleForExport(
            $filterData,
            $reportType,
            $reportBy,
        );

        $formattedData = $this->preparedByDetails($sellThroughAggregateData, $filterData);
        $grandTotals = $this->calculateGrandTotals($formattedData);

        $locationQueries = resolve(LocationQueries::class);
        $filteredLocation = $locationQueries->getNameByIds($locations->toArray());

        return view('prints.stock_summary_by_module', [
            'sellThroughAggregate' => $formattedData,
            'grandTotals' => [
                'totals' => $grandTotals,
            ],
            'dateRange' => $filterData['date_range'],
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filteredLocation' => $filteredLocation,
            'report_by' => $filterData['report_by'],
            'report_type' => $filterData['report_type'],
        ])->render();
    }

    private function preparedByDetails(Collection $sellThroughAggregates, array $filterData): array
    {
        $productService = resolve(ProductService::class);

        $data = [];
        $locationCodes = [];
        $data['location_codes'] = [];

        if (in_array($filterData['report_by'], [
            StockSummaryByModuleReportBy::SALES->value,
            StockSummaryByModuleReportBy::GRN_IN->value,
            StockSummaryByModuleReportBy::GRN_OUT->value,
            StockSummaryByModuleReportBy::TRANSFER_OUT->value,
            StockSummaryByModuleReportBy::DELIVERY_OUT->value,
            StockSummaryByModuleReportBy::TRANSFER_IN->value,
            StockSummaryByModuleReportBy::DELIVERY_IN->value,
            StockSummaryByModuleReportBy::STOCK_ADJUSTMENT_IN->value,
            StockSummaryByModuleReportBy::STOCK_ADJUSTMENT_OUT->value,
        ])) {
            foreach ($sellThroughAggregates as $sellThroughAggregate) {
                $key = $filterData['report_type'] === StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value
                    ? $sellThroughAggregate['article_number']
                    : $sellThroughAggregate['product_id'];

                if (! isset($data[$key])) {
                    if (config('app.product_variant')) {
                        $variantColumns = [
                            'attributes' => $productService->getAttributesForPrint($sellThroughAggregate->product),
                        ];
                    } else {
                        $variantColumns = [
                            'color_name' => $filterData['report_type'] !== StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value ? $sellThroughAggregate['color_name'] ?? 'N/A' : 'N/A',
                            'size_name' => $filterData['report_type'] !== StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value ? $sellThroughAggregate['size_name'] ?? 'N/A' : 'N/A',
                        ];
                    }

                    $data[$key] = [
                        'product_name' => $sellThroughAggregate['product_name'],
                        'article_number' => $sellThroughAggregate['article_number'] ?? 'N/A',
                        ...$variantColumns,
                        'locations' => [],
                    ];
                }

                $metric = match ($filterData['report_by']) {
                    StockSummaryByModuleReportBy::SALES->value => 'sales',
                    StockSummaryByModuleReportBy::GRN_IN->value => 'grn_in',
                    StockSummaryByModuleReportBy::GRN_OUT->value => 'grn_out',
                    StockSummaryByModuleReportBy::TRANSFER_OUT->value => 'stock_transfer_out',
                    StockSummaryByModuleReportBy::DELIVERY_OUT->value => 'delivery_order_out',
                    StockSummaryByModuleReportBy::TRANSFER_IN->value => 'stock_transfer_in',
                    StockSummaryByModuleReportBy::DELIVERY_IN->value => 'delivery_order_in',
                    StockSummaryByModuleReportBy::STOCK_ADJUSTMENT_IN->value => 'stock_adjustment_in',
                    StockSummaryByModuleReportBy::STOCK_ADJUSTMENT_OUT->value => 'stock_adjustment_out',
                    default => throw new InvalidArgumentException('Invalid report_by value'),
                };

                $data[$key]['locations'][$sellThroughAggregate['location_code']] = $sellThroughAggregate[$metric] ?? 0;
                $locationCodes[] = $sellThroughAggregate['location_code'];
            }

            $data['location_codes'] = array_unique($locationCodes);
        }

        return $data;
    }

    private function calculateGrandTotals(array $sellThroughAggregate): array
    {
        $grandTotals = [];

        foreach ($sellThroughAggregate['location_codes'] as $locationCode) {
            $grandTotals[$locationCode] = 0;
        }

        foreach ($sellThroughAggregate as $key => $data) {
            if ('location_codes' !== $key) {
                foreach ($sellThroughAggregate['location_codes'] as $locationCode) {
                    $grandTotals[$locationCode] += $data['locations'][$locationCode] ?? 0;
                }
            }
        }

        return $grandTotals;
    }

    public function export(
        array $filterData,
        int $companyId,
        string $filename,
        Collection $locations
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $reportType = $filterData['report_type'];
        $reportBy = $filterData['report_by'];

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $sellThroughAggregateData = $sellThroughAggregateQueries->getStockSummaryByModuleForExport(
            $filterData,
            $reportType,
            $reportBy,
        );

        $locationQueries = resolve(LocationQueries::class);
        $filteredLocation = $locationQueries->getNameByIds($locations->toArray());

        $formattedData = $this->preparedByDetails($sellThroughAggregateData, $filterData);

        $grandTotals = [
            'totals' => $this->calculateGrandTotals($formattedData),
        ];

        return Excel::download(
            new StockSummaryByModuleReportExport(
                $formattedData,
                $filterData['date_range'],
                $company,
                $filteredLocation,
                $filterData['report_by'],
                $filterData['report_type'],
                $grandTotals
            ),
            $filename
        );
    }
}
