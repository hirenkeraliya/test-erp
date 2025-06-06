<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Product\ProductQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Exports\StockTransferDiscrepancyReportBySummaryExport;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferDiscrepancyBySummaryReportService
{
    public function renderPreparedBySummary(array $filterData, Company $company, Collection $locations): string
    {
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItems = $stockTransferItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);

        [$stockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $stockTransferItems,
            $filterData,
            $locations
        );

        return view('prints.stock_transfer_discrepancy_by_summary', [
            'stockTransfersData' => $stockTransferData,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData, $company->id),
            'transferType' => $this->transferType($filterData),
            'dateSelectionType' => $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type'),
        ])->render();
    }

    public function exportStockTransferReportBySummaryExport(
        int $companyId,
        array $filterData,
        string $filename,
        Collection $locations,
    ): BinaryFileResponse {
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);

        $stockTransferItems = $stockTransferItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);

        [$stockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $stockTransferItems,
            $filterData,
            $locations
        );
        $filterBy = $this->filterBy($filterData, $company->id);
        $transferType = $this->transferType($filterData);

        return Excel::download(
            new StockTransferDiscrepancyReportBySummaryExport(
                $stockTransferData,
                $dateRange,
                $company,
                $columns,
                $filterBy,
                $transferType,
                $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type')
            ),
            $filename
        );
    }

    private function preparedByDocument(Collection $stockTransferItems, array $filterData, Collection $locations): array
    {
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);
        $locationWiseStockTransferRecords = collect([]);

        $groupBy = config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number';

        $articleNumberWiseStockTransferData = $stockTransferItems->groupBy($groupBy)->sortBy($groupBy);

        $articleNumberWiseStockTransferData = $articleNumberWiseStockTransferData->sortBy(
            fn ($item, $key): int|string => $key
        );

        foreach ($locations as $location) {
            $locationStockTransferData = [
                'location' => $location->name . ' [' . $location->code . ']',
                'stock_transfers' => [],
            ];

            $quantity = 0;
            $receivedQuantity = 0;
            $discrepancyQuantity = 0;

            foreach ($articleNumberWiseStockTransferData as $stockTransferItems) {
                $filteredItems = $stockTransferItems->filter(
                    fn ($item): bool => (float) $item->quantity !== (float) $item->received_quantity
                );

                if ($filteredItems->isEmpty()) {
                    continue;
                }

                $stockTransferItem = $stockTransferItems->first();
                $stockTransfer = $stockTransferItem->stockTransfer;

                if (
                    ($location->getKey() === $stockTransfer->source_location_id) ||
                    ($location->getKey() === $stockTransfer->destination_location_id)
                ) {
                }

                $product = $stockTransferItem->product;

                $quantity = CommonFunctions::truncateDecimal($this->getSumOf($stockTransferItems, 'quantity'));
                $receivedQuantity = CommonFunctions::truncateDecimal(
                    $this->getSumOf($stockTransferItems, 'received_quantity')
                );
                $discrepancyQuantity = CommonFunctions::truncateDecimal((float) $quantity - (float) $receivedQuantity);

                $locationStockTransferData['stock_transfers'][] = [
                    'date' => $stockTransferCustomReportService->formatStockTransferDate(
                        $stockTransfer,
                        $filterData
                    ),
                    'upc' => $product->upc,
                    'article_number' => config(
                        'app.product_variant'
                    ) ? $product->masterProduct?->article_number : $product->article_number,
                    'location_name' => $this->getLocationName($stockTransfer, (int) $filterData['transfer_type']),
                    'name' => $product->name,
                    'status' => StatusTypes::getFormattedCaseName($stockTransfer->status),
                    'quantity' => $quantity,
                    'received_quantity' => $receivedQuantity,
                    'discrepancy_quantity' => $discrepancyQuantity,
                ];
            }

            $locationStockTransferData['date'] = 'Total';
            $locationStockTransferData['upc'] = '';
            $locationStockTransferData['article_number'] = '';
            $locationStockTransferData['location_name'] = '';
            $locationStockTransferData['name'] = '';
            $locationStockTransferData['status'] = '';
            $locationStockTransferData['quantity'] = $quantity;
            $locationStockTransferData['received_quantity'] = $receivedQuantity;
            $locationStockTransferData['discrepancy_quantity'] = $discrepancyQuantity;

            $locationWiseStockTransferRecords->push($locationStockTransferData);
        }

        $columns = [
            'Date(' . $stockTransferCustomReportService->formatDateSelectionName(
                $filterData,
                'display_date_type'
            ) . ')' => 'text-left',
            'UPC' => 'text-left',
            'Article Number' => 'text-left',
            'Location Name' => 'text-left',
            'Name' => 'text-left',
            'Status' => 'text-left',
            'Quantity' => 'text-right',
            'Received Quantity' => 'text-right',
            'Discrepancy Quantity' => 'text-right',
        ];

        return [$locationWiseStockTransferRecords->sortBy('date'), $columns, $dateRange];
    }

    private function getLocationName(StockTransfer $stockTransfer, int $transferType): string
    {
        /** @var Location $sourceLocation */
        $sourceLocation = $stockTransfer->sourceLocation;

        /** @var Location $destinationLocation */
        $destinationLocation = $stockTransfer->destinationLocation;

        if ($transferType === TransferTypeForReport::TRANSFER_IN->value) {
            return $sourceLocation->name . ' (' . $sourceLocation->code . ')';
        }

        return $destinationLocation->name . ' (' . $destinationLocation->code . ')';
    }

    private function filterBy(array $filterData, int $companyId): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $productQueries = resolve(ProductQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === StockTransferCustomReportTypes::BY_PRODUCT->value && isset($filterData['product_id']) && '' !== $filterData['product_id']) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(
                StockTransferCustomReportTypes::BY_PRODUCT->value,
                $product->compound_product_name
            );
        }

        if ($filterBy !== StockTransferCustomReportTypes::BY_MASTER_PRODUCT->value) {
            return '';
        }

        if (! isset($filterData['article_number'])) {
            return '';
        }

        if ('' === $filterData['article_number']) {
            return '';
        }

        return $this->formatFilterResult(
            StockTransferCustomReportTypes::BY_MASTER_PRODUCT->value,
            $filterData['article_number']
        );
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return StockTransferCustomReportTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }

    private function transferType(array $filterData): string
    {
        if (! isset($filterData['transfer_type'])) {
            return '';
        }

        $transferType = (int) $filterData['transfer_type'];

        if ($transferType === TransferTypeForReport::TRANSFER_IN->value) {
            return TransferTypeForReport::getFormattedCaseName(TransferTypeForReport::TRANSFER_IN->value);
        }

        if ($transferType === TransferTypeForReport::TRANSFER_OUT->value) {
            return TransferTypeForReport::getFormattedCaseName(TransferTypeForReport::TRANSFER_OUT->value);
        }

        if ($transferType === TransferTypeForReport::TRANSFER_ORDER->value) {
            return TransferTypeForReport::getFormattedCaseName(TransferTypeForReport::TRANSFER_ORDER->value);
        }

        if ($transferType === TransferTypeForReport::REQUEST_ORDER->value) {
            return TransferTypeForReport::getFormattedCaseName(TransferTypeForReport::REQUEST_ORDER->value);
        }

        return '';
    }

    private function getSumOf(Collection $items, string $columnName): float
    {
        return $items->sum(fn ($item) => $item->{$columnName});
    }
}
