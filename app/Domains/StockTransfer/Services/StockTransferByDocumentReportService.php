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
use App\Domains\StockTransfer\Exports\StockTransferReportByDocumentExport;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferByDocumentReportService
{
    public function renderPreparedByDocument(
        array $filterData,
        Company $company,
        Collection $locations,
        bool $displayTotal
    ): string {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransfer($filterData, $company->id);

        [$locationWiseStockTransferRecords, $columns, $dateRange, $statusType] = $this->preparedByDocument(
            $stockTransfers,
            $locations,
            $filterData
        );

        $statusType = null === $filterData['status_type'] ? null : implode(',', $statusType);

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return view('prints.stock_transfer_by_document', [
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

    public function preparedByDocument(Collection $stockTransfers, Collection $locations, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $transferType = (int) $filterData['transfer_type'];

        [$locationWiseStockTransferRecords, $statusType] = $this->getLocationWiseStockTransferRecords(
            $stockTransfers,
            $locations,
            $filterData,
            $transferType,
        );

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        $columns = [
            'Date (' . $stockTransferCustomReportService->formatDateSelectionName(
                $filterData,
                'display_date_type'
            ) . ')',
            'No.',
            'Reference Number',
            'Transfer Type',
            'Status',
            'Location Name',
            'Quantity',
            'Received Quantity',
            'Price',
            'Reason',
            'Remark',
        ];

        return [$locationWiseStockTransferRecords, $columns, $dateRange, $statusType];
    }

    private function getLocationWiseStockTransferRecords(
        Collection $stockTransfers,
        Collection $locations,
        array $filterData,
        int $transferType
    ): array {
        $stockTransferService = resolve(StockTransferService::class);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        $locationWiseStockTransferRecords = [];
        $statusType = collect();

        foreach ($locations as $location) {
            foreach ($stockTransfers as $stockTransfer) {
                if ($location->getKey() === $stockTransfer->source_location_id || $location->getKey() === $stockTransfer->destination_location_id) {
                    $statusType->push(StatusTypes::getFormattedCaseName($stockTransfer->status));
                    $locationWiseStockTransferRecords[$location->getNameWithCode()][] = [
                        'date' => $stockTransferCustomReportService->formatStockTransferDate(
                            $stockTransfer,
                            $filterData
                        ),
                        'no' => $stockTransferService->getStockTransferNumberForSelectedLocation(
                            $stockTransfer,
                            $location->getKey(),
                        ),
                        'reference_number' => $stockTransfer->reference_number,
                        'transfer_type' => $stockTransferService->getTransferTypesByLocation(
                            $stockTransfer,
                            $location->getKey(),
                        ),
                        'status' => StatusTypes::getFormattedCaseName($stockTransfer->status),
                        'location_name' => $this->getLocationName($stockTransfer, $transferType),
                        'quantity' => CommonFunctions::truncateDecimal($stockTransfer->items->sum('quantity')),
                        'received_quantity' => CommonFunctions::truncateDecimal(
                            $stockTransfer->items->sum('received_quantity')
                        ),
                        'total_price' => (float) $this->fetchProductPrice(
                            $stockTransfer->items
                        )->first()['total_price'],
                        'reason' => $stockTransfer->stockTransferReason ? $stockTransfer->stockTransferReason->name : '',
                        'remark' => $stockTransfer->remarks,
                    ];
                }
            }

            if (array_key_exists($location->getNameWithCode(), $locationWiseStockTransferRecords)) {
                $locationWiseCollectionData = collect($locationWiseStockTransferRecords[$location->getNameWithCode()]);
                $locationWiseStockTransferRecords[$location->getNameWithCode()][] = [
                    'date' => 'Total',
                    'no' => '',
                    'reference_number' => '',
                    'transfer_type' => '',
                    'status' => '',
                    'location_name' => '',
                    'quantity' => $locationWiseCollectionData->sum('quantity'),
                    'received_quantity' => $locationWiseCollectionData->sum('received_quantity'),
                    'total_price' => $locationWiseCollectionData->sum('total_price'),
                    'reason' => '',
                    'remark' => '',
                ];
            }
        }

        return [$locationWiseStockTransferRecords, $statusType->unique()->filter()->toArray()];
    }

    public function exportStockTransferReportByDocumentExport(
        int $companyId,
        array $filterData,
        string $filename,
        Collection $locations,
        bool $displayTotal
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransfer($filterData, $company->id);
        [$locationWiseStockTransferRecords, $columns, $dateRange, $statusType] = $this->preparedByDocument(
            $stockTransfers,
            $locations,
            $filterData
        );
        $statusType = null === $filterData['status_type'] ? null : implode(',', $statusType);

        $filterBy = $this->filterBy($filterData, $company->id);
        $transferType = $this->transferType($filterData);

        return Excel::download(
            new StockTransferReportByDocumentExport(
                $locationWiseStockTransferRecords,
                $dateRange,
                $company,
                $columns,
                $displayTotal,
                (bool) $filterData['status_type'],
                $statusType,
                $filterBy,
                $transferType,
                $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type'),
            ),
            $filename
        );
    }

    private function fetchProductPrice(Collection $items): Collection
    {
        return $items->map(function ($item): array {
            /** @var Product $product */
            $product = $item->product;

            return [
                'total_price' => CommonFunctions::currencyFormat($item->quantity * $product->retail_price),
            ];
        });
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
