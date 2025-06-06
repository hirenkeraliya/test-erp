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
use App\Domains\StockTransfer\Exports\StockTransferDiscrepancyReportByDetailsExport;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferDiscrepancyByDetailsReportService
{
    public function renderPreparedByDetails(array $filterData, Company $company, Collection $locations): string
    {
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $company->id
        );

        $stockTransferData = $this->preparedByDetails($stockTransfers, $filterData, $locations);

        $columns = [
            'Date (' . $stockTransferCustomReportService->formatDateSelectionName(
                $filterData,
                'display_date_type'
            ) . ')',
            'Reference Number',
            'Transfer Number',
            'Transfer Type',
            'Status',
            'Location',
            'Transfer From And To',
            'Quantity',
            'Received Quantity',
            'Discrepancy Quantity',
            'Package Type',
        ];

        $customReportService = resolve(CustomReportService::class);

        return view('prints.stock_transfer_discrepancy_by_details', [
            'stockTransfersData' => $stockTransferData,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData, $company->id),
            'transferType' => $this->transferType($filterData),
            'dateSelectionType' => $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type'),
            'displaySelectedDateType' => $stockTransferCustomReportService->formatDateSelectionName(
                $filterData,
                'display_date_type'
            ),
        ])->render();
    }

    public function exportStockTransferReportByDetailsExport(
        int $companyId,
        array $filterData,
        string $filename,
        Collection $locations,
    ): BinaryFileResponse {
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $companyId
        );

        $stockTransferData = $this->preparedByDetails($stockTransfers, $filterData, $locations);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);
        $filterBy = $this->filterBy($filterData, $company->id);
        $transferType = $this->transferType($filterData);

        return Excel::download(
            new StockTransferDiscrepancyReportByDetailsExport(
                $stockTransferData,
                $dateRange,
                $company,
                $filterBy,
                $transferType,
                $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type'),
                $stockTransferCustomReportService->formatDateSelectionName($filterData, 'display_date_type')
            ),
            $filename
        );
    }

    /**
     * @return array<int|string, mixed>
     */
    private function preparedByPackageType(Collection $stockTransferItems): array
    {
        $packageTypeData = [];
        foreach ($stockTransferItems->groupBy('packageType.name') as $key => $collection) {
            $columnName = '' !== $key ? $key : 'N/A';
            $packageTypeData[$columnName] = $collection->sum('package_quantity');
        }

        return $packageTypeData;
    }

    private function preparedByDetails(Collection $stockTransfers, array $filterData, Collection $locations): Collection
    {
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $stockTransferService = resolve(StockTransferService::class);
        $locationWiseStockTransferRecords = collect([]);

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
                        'transfer_date' => $stockTransferCustomReportService->formatStockTransferDate(
                            $stockTransfer,
                            $filterData
                        ),
                        'reference_number' => $stockTransfer->reference_number,
                        'transfer_number' => $stockTransferService->getStockTransferNumber($stockTransfer, $filterData),
                        'transfer_type' => $stockTransferService->getTransferType($stockTransfer, $filterData),
                        'status' => StatusTypes::getFormattedCaseName($stockTransfer->status),
                        'receiver_location' => $this->getLocationName(
                            $stockTransfer,
                            (int) $filterData['transfer_type']
                        ),
                        'transfer_from_and_to' => $this->preparedProductRecords($filteredItems),
                        'quantity' => $quantity,
                        'received_quantity' => $receivedQuantity,
                        'discrepancy_quantity' => $discrepancyQuantity,
                        'package_type' => $this->preparedByPackageType($filteredItems),
                    ];
                }
            }

            $locationStockTransferData['transfer_date'] = 'Total';
            $locationStockTransferData['reference_number'] = '';
            $locationStockTransferData['transfer_number'] = '';
            $locationStockTransferData['transfer_type'] = '';
            $locationStockTransferData['status'] = '';
            $locationStockTransferData['receiver_location'] = '';
            $locationStockTransferData['quantity'] = $quantity;
            $locationStockTransferData['received_quantity'] = $receivedQuantity;
            $locationStockTransferData['discrepancy_quantity'] = $discrepancyQuantity;
            $locationStockTransferData['package_type'] = '';

            $locationWiseStockTransferRecords->push($locationStockTransferData);
        }

        return $locationWiseStockTransferRecords;
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

    private function preparedProductRecords(Collection $stockTransferItems): array
    {
        $groupBy = config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number';

        return $stockTransferItems->groupBy($groupBy)->map(fn ($items): array => [
            'name' => $items->first()->product->name,
            'article_number' => config(
                'app.product_variant'
            ) ? $items->first()->product?->masterProduct?->article_number : $items->first()->product->article_number,
            'total_quantity' => CommonFunctions::truncateDecimal($items->sum('quantity')),
            'total_received_quantity' => CommonFunctions::truncateDecimal($items->sum('received_quantity')),
            'total_discrepancy_quantity' => CommonFunctions::truncateDecimal(
                $items->sum('quantity') - $items->sum('received_quantity')
            ),
            'total_package_quantity' => $this->preparedByPackageType($items),
            'color_wise_products' => $this->preparedProductByColorRecords($items),
        ])->toArray();
    }

    private function preparedProductByColorRecords(Collection $items): array
    {
        $productService = resolve(ProductService::class);

        return $items->map(function ($item) use ($productService): array {
            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($item->product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $item->product->color?->name ?? 'N/A',
                    'size' => $item->product->size?->name ?? 'N/A',
                ];
            }

            return [
                'upc' => $item->product->upc,
                ...$colorSizeOrAttributeData,
                'quantity' => CommonFunctions::truncateDecimal((float) $item->quantity),
                'received_quantity' => CommonFunctions::truncateDecimal((float) $item->received_quantity),
                'discrepancy_quantity' => CommonFunctions::truncateDecimal(
                    (float) $item->quantity - (float) $item->received_quantity
                ),
                'package_type' => $item->packageType ? $item->packageType->name . ':' . CommonFunctions::truncateDecimal(
                    (float) $item->package_quantity
                ) : '',
            ];
        })->toArray();
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
