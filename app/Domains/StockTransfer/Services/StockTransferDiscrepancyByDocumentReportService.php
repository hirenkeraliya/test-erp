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
use App\Domains\StockTransfer\Exports\StockTransferDiscrepancyReportByDocumentExport;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferDiscrepancyByDocumentReportService
{
    public function renderPreparedByDocument(array $filterData, Company $company, Collection $locations): string
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransfer($filterData, $company->id);

        [$stockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $stockTransfers,
            $filterData,
            $locations
        );

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return view('prints.stock_transfer_discrepancy_by_document', [
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

    public function exportStockTransferReportByDocumentExport(
        int $companyId,
        array $filterData,
        string $filename,
        Collection $locations,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransfer($filterData, $company->id);
        [$stockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $stockTransfers,
            $filterData,
            $locations
        );
        $filterBy = $this->filterBy($filterData, $company->id);
        $transferType = $this->transferType($filterData);

        return Excel::download(
            new StockTransferDiscrepancyReportByDocumentExport(
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

    private function preparedByDocument(Collection $stockTransfers, array $filterData, Collection $locations): array
    {
        $customReportService = resolve(CustomReportService::class);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);
        $locationWiseStockTransferRecords = collect([]);

        $stockTransferService = resolve(StockTransferService::class);

        foreach ($locations as $location) {
            $locationStockTransferData = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'stock_transfers' => [],
            ];

            $quantity = 0;
            $receivedQuantity = 0;
            $discrepancyQuantity = 0;

            foreach ($stockTransfers as $stockTransfer) {
                if (
                    ($location->getKey() === $stockTransfer->source_location_id) ||
                    ($location->getKey() === $stockTransfer->destination_location_id)
                ) {
                    $filteredItems = $stockTransfer->items->filter(
                        fn ($item): bool => (float) $item->quantity !== (float) $item->received_quantity
                    );

                    if ($filteredItems->isEmpty()) {
                        continue;
                    }

                    $quantity = CommonFunctions::truncateDecimal($this->getSumOf($filteredItems, 'quantity'));
                    $receivedQuantity = CommonFunctions::truncateDecimal(
                        $this->getSumOf($filteredItems, 'received_quantity')
                    );
                    $discrepancyQuantity = CommonFunctions::truncateDecimal(
                        (float) $quantity - (float) $receivedQuantity
                    );

                    $filterData['location_id'] = $location->getKey();

                    $locationStockTransferData['stock_transfers'][] = [
                        'date' => $stockTransferCustomReportService->formatStockTransferDate(
                            $stockTransfer,
                            $filterData
                        ),
                        'no' => $stockTransferService->getStockTransferNumber($stockTransfer, $filterData),
                        'reference_number' => $stockTransfer->reference_number,
                        'transfer_type' => $stockTransferService->getTransferType($stockTransfer, $filterData),
                        'status' => StatusTypes::getFormattedCaseName($stockTransfer->status),
                        'location_name' => $this->getLocationName($stockTransfer, (int) $filterData['transfer_type']),
                        'quantity' => $quantity,
                        'received_quantity' => $receivedQuantity,
                        'discrepancy_quantity' => $discrepancyQuantity,
                    ];
                }
            }

            $locationStockTransferData['date'] = 'Total';
            $locationStockTransferData['no'] = '';
            $locationStockTransferData['reference_number'] = '';
            $locationStockTransferData['transfer_type'] = '';
            $locationStockTransferData['status'] = '';
            $locationStockTransferData['quantity'] = $quantity;
            $locationStockTransferData['received_quantity'] = $receivedQuantity;
            $locationStockTransferData['discrepancy_quantity'] = $discrepancyQuantity;

            $locationWiseStockTransferRecords->push($locationStockTransferData);
        }

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
            'Discrepancy Quantity',
        ];

        return [$locationWiseStockTransferRecords, $columns, $dateRange];
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
