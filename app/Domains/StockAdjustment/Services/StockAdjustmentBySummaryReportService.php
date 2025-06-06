<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Services;

use App\CommonFunctions;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Product\ProductQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentFilterType;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Domains\StockAdjustment\Exports\StockAdjustmentReportBySummaryExport;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockAdjustmentBySummaryReportService
{
    public function renderPreparedBySummary(array $filterData, Company $company, array $locations): string
    {
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $stockAdjustmentItems = $stockAdjustmentItemQueries->getItemsByDateAndLocations($filterData, $company->id);

        [$stockAdjustmentRecords, $columns, $dateRange] = $this->preparedBySummary(
            $stockAdjustmentItems,
            $filterData,
            $locations
        );

        return view('prints.stock_adjustment_by_summary', [
            'stockAdjustmentRecords' => $stockAdjustmentRecords,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData, $company->id),
            'stockAdjustmentType' => $this->stockAdjustmentType($filterData),
        ])->render();
    }

    public function exportStockAdjustmentReportBySummaryExport(
        Company $company,
        array $filterData,
        string $filename,
        array $locations,
    ): BinaryFileResponse {
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $stockAdjustmentItems = $stockAdjustmentItemQueries->getItemsByDateAndLocations($filterData, $company->id);

        [$stockAdjustmentRecords, $columns, $dateRange] = $this->preparedBySummary(
            $stockAdjustmentItems,
            $filterData,
            $locations
        );

        return Excel::download(
            new StockAdjustmentReportBySummaryExport(
                $stockAdjustmentRecords,
                $dateRange,
                $company,
                $columns,
                $this->filterBy($filterData, $company->id),
                $this->stockAdjustmentType($filterData)
            ),
            $filename
        );
    }

    private function preparedBySummary(Collection $stockAdjustmentItems, array $filterData, array $locations): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationsStockAdjustments = collect([]);

        foreach ($locations as $location) {
            $locationStockAdjustmentItems = $stockAdjustmentItems->where('location_id', $location['id']);
            $locationStockAdjustments = [
                'location_name' => $location['name'] . ' [' . $location['code'] . ']',
                'stock_adjustment_data' => [],
            ];

            $quantity = 0;

            foreach ($locationStockAdjustmentItems as $stockAdjustmentItem) {
                /** @var StockAdjustment $stockAdjustment */
                $stockAdjustment = $stockAdjustmentItem->stockAdjustment;

                /** @var Carbon $adjustmentDate */
                $adjustmentDate = $stockAdjustment->adjustment_date ? Carbon::createFromFormat(
                    'Y-m-d',
                    $stockAdjustment->adjustment_date
                ) : $stockAdjustment->created_at;

                $quantity += $stockAdjustmentItem->quantity;

                /** @var Product $product */
                $product = $stockAdjustmentItem->product;

                $locationStockAdjustments['stock_adjustment_data'][] = [
                    'adjustment_date' => $adjustmentDate->format('d-m-Y'),
                    'adjustment_type' => StockAdjustmentTypes::getCaseNameByValue($stockAdjustment->type_id),
                    'upc' => $product->upc,
                    'article_number' => $product->article_number,
                    'approved_by' => $this->getApprovedBy($stockAdjustment),
                    'reason' => $stockAdjustment->reason ?? 'N/A',
                    'quantity' => $stockAdjustmentItem->quantity,
                ];
            }

            $locationStockAdjustments['adjustment_date'] = 'Total';
            $locationStockAdjustments['adjustment_type'] = '';
            $locationStockAdjustments['upc'] = '';
            $locationStockAdjustments['article_number'] = '';
            $locationStockAdjustments['approved_by'] = '';
            $locationStockAdjustments['reason'] = '';
            $locationStockAdjustments['quantity'] = CommonFunctions::currencyFormat((float) $quantity);

            $locationsStockAdjustments->push($locationStockAdjustments);
        }

        $columns = [
            'Adjustment Date',
            'Adjustment Type',
            'Upc',
            'Article Number',
            'Approved By',
            'Reason',
            'Quantity',
        ];

        return [$locationsStockAdjustments, $columns, $dateRange];
    }

    private function getApprovedBy(StockAdjustment $stockAdjustment): string
    {
        /** @var Employee $employee */
        $employee = $stockAdjustment->employee;

        return $employee->getFullName();
    }

    private function filterBy(array $filterData, int $companyId): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $productQueries = resolve(ProductQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === StockAdjustmentFilterType::BY_PRODUCT->value && isset($filterData['product_id']) && '' !== $filterData['product_id']) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(
                StockAdjustmentFilterType::BY_PRODUCT->value,
                $product->compound_product_name
            );
        }

        if ($filterBy !== StockAdjustmentFilterType::BY_MASTER_PRODUCT->value) {
            return '';
        }

        if (! isset($filterData['article_number'])) {
            return '';
        }

        if ('' === $filterData['article_number']) {
            return '';
        }

        return $this->formatFilterResult(
            StockAdjustmentFilterType::BY_MASTER_PRODUCT->value,
            $filterData['article_number']
        );
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return StockAdjustmentFilterType::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }

    private function stockAdjustmentType(array $filterData): string
    {
        if ((int) $filterData['stock_adjustment_type'] === StockAdjustmentTypes::STI->value) {
            return StockAdjustmentTypes::getFormattedCaseName(StockAdjustmentTypes::STI->value);
        }

        if ((int) $filterData['stock_adjustment_type'] === StockAdjustmentTypes::STO->value) {
            return StockAdjustmentTypes::getFormattedCaseName(StockAdjustmentTypes::STO->value);
        }

        return '';
    }
}
