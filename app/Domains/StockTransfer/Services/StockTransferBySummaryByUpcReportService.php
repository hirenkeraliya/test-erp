<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Exports\StockTransferReportBySummaryByUpcExport;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferBySummaryByUpcReportService
{
    public function renderPreparedBySummary(
        array $filterData,
        Company $company,
        Collection $locations,
        bool $displayTotal
    ): string {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransfers = $stockTransferItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);

        [$locationWiseStockTransferRecords, $columns, $dateRange, $statusType] = $this->preparedBySummaryUpc(
            $stockTransfers,
            $locations,
            $filterData
        );

        $statusType = null === $filterData['status_type'] ? null : implode(',', $statusType);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return view('prints.stock_transfer_by_summary_by_upc', [
            'stockTransfersData' => $locationWiseStockTransferRecords,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'displayTotal' => $displayTotal,
            'isStatusAllowed' => (bool) $filterData['status_type'],
            'status' => $statusType,
            'filterBy' => $this->filterBy($filterData, $company->id),
            'transferType' => $this->transferType($filterData),
            'dateSelectionType' => $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type'),
        ])->render();
    }

    public function preparedBySummaryUpc(
        Collection $stockTransferItems,
        Collection $locations,
        array $filterData
    ): array {
        $customReportService = resolve(CustomReportService::class);
        $productService = resolve(ProductService::class);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationWiseStockTransferRecords = [];
        $statusType = collect();

        foreach ($locations as $location) {
            $keyName = $location->getNameWithCode();
            foreach ($stockTransferItems as $stockTransferItem) {
                $stockTransfer = $stockTransferItem->stockTransfer;
                if ($location->getKey() === $stockTransfer->source_location_id || $location->getKey() === $stockTransfer->destination_location_id) {
                    $product = $stockTransferItem->product;
                    $colorSizeOrAttributeData = [];
                    if (config('app.product_variant')) {
                        $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
                    } else {
                        $colorSizeOrAttributeData = [
                            'color' => $product->color?->name ?? 'N/A',
                            'size' => $product->size?->name ?? 'N/A',
                        ];
                    }

                    $statusType->push(StatusTypes::getFormattedCaseName($stockTransfer->status));
                    $locationWiseStockTransferRecords[$keyName][] = [
                        'date' => $stockTransferCustomReportService->formatStockTransferDate(
                            $stockTransfer,
                            $filterData
                        ),
                        'upc' => $product->upc,
                        'name' => $product->name,
                        'status' => StatusTypes::getFormattedCaseName($stockTransfer->status),
                        ...$colorSizeOrAttributeData,
                        'quantity' => CommonFunctions::truncateDecimal((float) $stockTransferItem->quantity),
                        'received_quantity' => CommonFunctions::truncateDecimal(
                            (float) $stockTransferItem->received_quantity
                        ),
                        'total_price' => (float) $stockTransferItem->quantity * (float) $product->retail_price,
                        'location_name' => $this->getLocationName($stockTransfer, (int) $filterData['transfer_type']),
                    ];
                }
            }

            if (array_key_exists($keyName, $locationWiseStockTransferRecords)) {
                $locationWiseStockTransferRecords[$keyName][] = [
                    'date' => 'Total',
                    'upc' => '',
                    'location_name' => '',
                    'name' => '',
                    'status' => '',
                    ...config('app.product_variant') ? [
                        'attributes' => '',
                    ] : [
                        'color' => '',
                        'size' => '',
                    ],
                    'quantity' => CommonFunctions::truncateDecimal(
                        array_sum(array_column($locationWiseStockTransferRecords[$keyName], 'quantity'))
                    ),
                    'received_quantity' => CommonFunctions::truncateDecimal(
                        array_sum(array_column($locationWiseStockTransferRecords[$keyName], 'received_quantity'))
                    ),
                    'total_price' => CommonFunctions::currencyFormat(
                        array_sum(array_column($locationWiseStockTransferRecords[$keyName], 'total_price'))
                    ),
                ];
            }
        }

        $columns = [
            'Date (' . $stockTransferCustomReportService->formatDateSelectionName(
                $filterData,
                'display_date_type'
            ) . ')',
            'UPC',
            'Location Name',
            'Name',
            'Status',
            ...config('app.product_variant') ? ['Attributes'] : ['Color', 'Size'],
            'Quantity',
            'Received Quantity',
            'Price',
        ];

        return [
            collect($locationWiseStockTransferRecords)->sortBy('date')->toArray(),
            $columns,
            $dateRange,
            $statusType->unique()->filter()->toArray(),
        ];
    }

    public function exportStockTransferReportBySummaryByUpcExport(
        int $companyId,
        array $filterData,
        string $filename,
        Collection $locations,
        bool $displayTotal
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        $stockTransferItems = $stockTransferItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);
        [$locationWiseStockTransferRecords, $columns, $dateRange, $statusType] = $this->preparedBySummaryUpc(
            $stockTransferItems,
            $locations,
            $filterData
        );

        $statusType = null === $filterData['status_type'] ? null : implode(',', $statusType);
        $filterBy = $this->filterBy($filterData, $company->id);
        $transferType = $this->transferType($filterData);

        return Excel::download(
            new StockTransferReportBySummaryByUpcExport(
                $locationWiseStockTransferRecords,
                $dateRange,
                $company,
                $columns,
                $displayTotal,
                (bool) $filterData['status_type'],
                $statusType,
                $filterBy,
                $transferType,
                $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type')
            ),
            $filename
        );
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
}
