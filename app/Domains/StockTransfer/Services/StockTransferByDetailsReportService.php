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
use App\Domains\StockTransfer\Exports\StockTransferReportByDetailsExport;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferByDetailsReportService
{
    public function renderPreparedByDetails(
        array $filterData,
        Company $company,
        Collection $locations,
        bool $displayTotal
    ): string {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $company->id
        );

        [$locationWiseStockTransferRecords, $statusType] = $this->preparedByDetails(
            $stockTransfers,
            $locations,
            $filterData
        );

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        $statusType = null === $filterData['status_type'] ? null : implode(',', $statusType);

        $columns = [
            'Date',
            'Reference Number',
            'Transfer Number',
            'Transfer Type',
            'Status',
            'Location',
            'Transfer From And To',
            'Reason',
            'Quantity',
            'Rec. Quantity',
            'Price',
            'Package Type',
            'Remark',
            'Requested By',
        ];

        $customReportService = resolve(CustomReportService::class);

        return view('prints.stock_transfer_by_details', [
            'stockTransfersData' => $locationWiseStockTransferRecords,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'displayTotal' => $displayTotal,
            'isStatusAllowed' => (bool) $filterData['status_type'],
            'status' => $statusType,
            'filterBy' => $this->filterBy($filterData, $company->id),
            'transferType' => $this->transferType($filterData),
            'dateSelectionType' => $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type'),
            'displayDateSelectionType' => $stockTransferCustomReportService->formatDateSelectionName(
                $filterData,
                'display_date_type'
            ),
        ])->render();
    }

    public function preparedByDetails(Collection $stockTransfers, Collection $locations, array $filterData): array
    {
        $stockTransferService = resolve(StockTransferService::class);
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        $locationWiseStockTransferRecords = [];
        $statusType = collect();

        foreach ($locations as $location) {
            $keyName = $location->getNameWithCode();
            foreach ($stockTransfers as $stockTransfer) {
                if ($location->getKey() === $stockTransfer->source_location_id || $location->getKey() === $stockTransfer->destination_location_id) {
                    $statusType->push(StatusTypes::getFormattedCaseName($stockTransfer->status));
                    $locationWiseStockTransferRecords[$keyName][] = [
                        'transfer_date' => $stockTransferCustomReportService->formatStockTransferDate(
                            $stockTransfer,
                            $filterData
                        ),
                        'reference_number' => $stockTransfer->reference_number,
                        'transfer_number' => $stockTransferService->getStockTransferNumberForSelectedLocation(
                            $stockTransfer,
                            $location->getKey(),
                        ),
                        'transfer_type' => $stockTransferService->getTransferTypesByLocation(
                            $stockTransfer,
                            $location->getKey(),
                        ),
                        'status' => StatusTypes::getFormattedCaseName($stockTransfer->status),
                        'receiver_location' => $this->getLocationName(
                            $stockTransfer,
                            (int) $filterData['transfer_type']
                        ),
                        'transfer_from_and_to' => $this->preparedProductRecords($stockTransfer->items),
                        'reason' => $stockTransfer->stockTransferReason ? $stockTransfer->stockTransferReason->name : '',
                        'quantity' => CommonFunctions::truncateDecimal($stockTransfer->items->sum('quantity')),
                        'received_quantity' => CommonFunctions::truncateDecimal(
                            $stockTransfer->items->sum('received_quantity')
                        ),
                        'total_price' => (float) $this->fetchProductPrice(
                            $stockTransfer->items
                        )->first()['total_price'],
                        'package_type' => $this->preparedByPackageType($stockTransfer->items),
                        'remark' => $stockTransfer->remarks,
                        'requested_by' => $stockTransfer->requestedBy->employee->getFullName() . '(' . $stockTransfer->requestedBy->employee->staff_id . ')',
                        'source_location_id' => $stockTransfer->source_location_id,
                        'destination_location_id' => $stockTransfer->destination_location_id,
                    ];
                }
            }

            if (array_key_exists($keyName, $locationWiseStockTransferRecords)) {
                $locationWiseStockTransferRecords[$keyName][] = [
                    'transfer_date' => 'Total',
                    'reference_number' => '',
                    'transfer_number' => '',
                    'transfer_type' => '',
                    'status' => '',
                    'receiver_location' => '',
                    'transfer_from_and_to' => [],
                    'reason' => '',
                    'quantity' => array_sum(array_column($locationWiseStockTransferRecords[$keyName], 'quantity')),
                    'received_quantity' => array_sum(
                        array_column($locationWiseStockTransferRecords[$keyName], 'received_quantity')
                    ),
                    'total_price' => array_sum(
                        array_column($locationWiseStockTransferRecords[$keyName], 'total_price')
                    ),
                    'package_type' => [],
                    'remark' => '',
                    'requested_by' => '',
                    'source_location_id' => '',
                    'destination_location_id' => '',
                ];
            }
        }

        return [$locationWiseStockTransferRecords, $statusType->unique()->filter()->toArray()];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function preparedByPackageType(Collection $stockTransferItems): array
    {
        $packageTypeData = [];
        foreach ($stockTransferItems->groupBy('packageType.name') as $key => $collection) {
            $packageTypeData['' !== $key ? $key : 'N/A'] = $collection->sum('package_quantity');
        }

        return $packageTypeData;
    }

    public function exportStockTransferReportByDetailsExport(
        int $companyId,
        array $filterData,
        string $filename,
        Collection $locations,
        bool $displayTotal,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $companyId
        );

        [$locationWiseStockTransferRecords, $statusType] = $this->preparedByDetails(
            $stockTransfers,
            $locations,
            $filterData
        );

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);
        $statusType = null === $filterData['status_type'] ? null : implode(',', $statusType);
        $filterBy = $this->filterBy($filterData, $company->id);
        $transferType = $this->transferType($filterData);

        return Excel::download(
            new StockTransferReportByDetailsExport(
                $locationWiseStockTransferRecords,
                $dateRange,
                $company,
                $displayTotal,
                (bool) $filterData['status_type'],
                $statusType,
                $filterBy,
                $transferType,
                $stockTransferCustomReportService->formatDateSelectionName($filterData, 'date_type'),
                $stockTransferCustomReportService->formatDateSelectionName($filterData, 'display_date_type')
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

    private function preparedProductRecords(Collection $stockTransferItems): array
    {
        $groupBy = config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number';

        return $stockTransferItems->groupBy($groupBy)->map(fn ($items): array => [
            'name' => $items->first()->product->name,
            'article_number' => config(
                'app.product_variant'
            ) ? $items->first()->product?->masterProduct?->article_number : $items->first()->product->article_number,
            'total_quantity' => CommonFunctions::truncateDecimal($items->sum('received_quantity')),
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
}
