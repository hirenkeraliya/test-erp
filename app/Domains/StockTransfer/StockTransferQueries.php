<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\BarcodePrintModuleTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Enums\TransferTypes;
use App\Domains\StockTransferAverageLeadDays\StockTransferAverageLeadDaysQueries;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\StockTransferItemBatch\StockTransferItemBatchQueries;
use App\Domains\StockTransferItemTransaction\StockTransferItemTransactionQueries;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\StockTransferReason\StockTransferReasonQueries;
use App\Domains\StockTransferTransaction\StockTransferTransactionQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Models\StockTransfer;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockTransferQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);

        if (! $filterData['location_id']) {
            return $this->stockTransferQuery($filterData, $companyId)
                ->with([
                    'stockTransferAverageLeadDay:' . $stockTransferAverageLeadDaysQueries->getAverageDaysColumn(),
                ])
                ->paginate($filterData['per_page']);
        }

        $locationQuery = function ($query) use ($filterData): void {
            $query->where(function ($query) use ($filterData): void {
                $query->where('source_location_id', $filterData['location_id']);
            })
                ->orWhere(function ($query) use ($filterData): void {
                    $query->where('destination_location_id', $filterData['location_id']);
                });
        };

        return $this->commonListQuery($filterData, $companyId)
            ->with(['stockTransferAverageLeadDay:' . $stockTransferAverageLeadDaysQueries->getAverageDaysColumn()])
            ->where(function ($query) use ($locationQuery, $filterData): void {
                $query->where(function ($query) use ($locationQuery): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where($locationQuery);
                })
                    ->orWhere(function ($query) use ($locationQuery): void {
                        $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                            ->where($locationQuery);
                    })->orWhere(function ($query) use ($locationQuery, $filterData): void {
                        $query->whereNot('status', StatusTypes::DRAFT->value)
                            ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                            ->where(function ($query) use ($locationQuery, $filterData): void {
                                $query->where($locationQuery)
                                    ->orWhere(function ($query) use ($filterData): void {
                                        $query->where('transit_location_id', $filterData['location_id']);
                                    });
                            });
                    });
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value && (int) $filterData['location_id'] === 0) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value && (int) $filterData['location_id'] === 0) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value && $filterData['location_id'] > 0) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value && $filterData['location_id'] > 0) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value && $filterData['location_id'] > 0) {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_OUT->value,
                            ]);
                        })
                            ->where(function ($query) use ($filterData): void {
                                $query->where(function ($query) use ($filterData): void {
                                    $query->where('destination_location_id', $filterData['location_id']);
                                })
                                    ->orWhere(function ($query) use ($filterData): void {
                                        $query->where('transit_location_id', $filterData['location_id']);
                                    });
                            });
                    });
                }

                if ((int) $filterData['transfer_type'] !== TransferTypes::TRANSFER_OUT->value) {
                    return;
                }

                if ($filterData['location_id'] <= 0) {
                    return;
                }

                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query): void {
                        $query->whereNotIn('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                            StatusTypes::TRANSIT_IN->value,
                            StatusTypes::TRANSIT->value,
                        ]);
                    })
                        ->where(function ($query) use ($filterData): void {
                            $query->where(function ($query) use ($filterData): void {
                                $query->where('source_location_id', $filterData['location_id']);
                            });
                        });
                });
            })
            ->when($filterData['dashboard_transfer_type'] && $filterData['location_id'] > 0, function ($query) use (
                $filterData
            ): void {
                if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                }

                if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                }
            })
            ->paginate($filterData['per_page']);
    }

    public function storeManagerListQuery(array $filterData, int $companyId, int $locationId): LengthAwarePaginator
    {
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);

        return $this->storeManagerStockTransferQuery($filterData, $companyId, $locationId)
            ->with(['stockTransferAverageLeadDay:' . $stockTransferAverageLeadDaysQueries->getAverageDaysColumn()])
            ->paginate($filterData['per_page']);
    }

    public function warehouseManagerListQuery(
        array $filterData,
        int $companyId,
        int $locationId,
    ): LengthAwarePaginator {
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);

        return $this->warehouseManagerStockTransferQuery($filterData, $companyId, $locationId)
            ->with(['stockTransferAverageLeadDay:' . $stockTransferAverageLeadDaysQueries->getAverageDaysColumn()])
            ->paginate($filterData['per_page']);
    }

    public function getWarehouseManagerStockTransfersExport(
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        return $this->warehouseManagerStockTransferQuery($filterData, $companyId, $locationId)->get();
    }

    public function searchLocationByName(string $searchText): Closure
    {
        return fn ($query) => $query->where('name', 'like', '%' . $searchText . '%');
    }

    public function addNew(array $stockTransferDetails): StockTransfer
    {
        return StockTransfer::create($stockTransferDetails);
    }

    public function getByIdForPrint(int $stockTransferId, int $companyId): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $stockTransferReasonQueries = resolve(StockTransferReasonQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        if (config('app.product_variant')) {
            return StockTransfer::query()
                ->select(
                    'id',
                    'company_id',
                    'transfer_type',
                    'stock_transfer_reason_id',
                    'source_location_id',
                    'destination_location_id',
                    'reference_number',
                    'transfer_date',
                    'received_date',
                    'attention',
                    'requested_by_type',
                    'requested_by_id',
                    'transfer_out_number',
                    'request_order_number',
                    'transfer_order_number',
                    'transfer_in_number',
                    'shipped_at',
                    'remarks',
                    'status',
                    'transit_location_id',
                    'created_at'
                )
                ->with(
                    'requestedBy:' . $this->requestedByColumnName(),
                    'receivedBy:' . $stockTransferTransactionQueries->getMorphUserColumns(),
                    'receivedBy.user:' . $this->requestedByColumnName(),
                    'receivedBy.user.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                    'stockTransferReason:' . $stockTransferReasonQueries->getBasicColumn(),
                    'requestedBy.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'transactions:' . $stockTransferTransactionQueries->getMorphUserColumns(),
                    'transactions.user:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                    'transactions.user.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                    'items:' . $stockTransferItemQueries->getColumnNamesForPrint(),
                    'items.unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                    'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
                    'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                    'items.batches.batch:' . $batchQueries->getBasicColumnNames(),
                    'sourceLocation:' . $this->getLocationColumnNameForPrint(),
                    'destinationLocation:' . $this->getLocationColumnNameForPrint(),
                    'company.media:' . $mediaQueries->getBasicColumnNames(),
                    'company:' . $companyQueries->getBasicColumnNamesForStockTransferPrint(),
                )
                ->where('company_id', $companyId)
                ->findOrFail($stockTransferId);
        }

        return StockTransfer::query()
            ->select(
                'id',
                'company_id',
                'transfer_type',
                'stock_transfer_reason_id',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'transfer_date',
                'received_date',
                'attention',
                'requested_by_type',
                'requested_by_id',
                'transfer_out_number',
                'request_order_number',
                'transfer_order_number',
                'transfer_in_number',
                'shipped_at',
                'remarks',
                'status',
                'transit_location_id',
                'created_at'
            )
            ->with(
                'requestedBy:' . $this->requestedByColumnName(),
                'receivedBy:' . $stockTransferTransactionQueries->getMorphUserColumns(),
                'receivedBy.user:' . $this->requestedByColumnName(),
                'receivedBy.user.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'stockTransferReason:' . $stockTransferReasonQueries->getBasicColumn(),
                'requestedBy.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'transactions:' . $stockTransferTransactionQueries->getMorphUserColumns(),
                'transactions.user:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'transactions.user.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'items:' . $stockTransferItemQueries->getColumnNamesForPrint(),
                'items.unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
                'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnsForPrint(),
                'items.product.color:' . $colorQueries->getBasicColumnNames(),
                'items.product.size:' . $sizeQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                'items.batches.batch:' . $batchQueries->getBasicColumnNames(),
                'sourceLocation:' . $this->getLocationColumnNameForPrint(),
                'destinationLocation:' . $this->getLocationColumnNameForPrint(),
                'company.media:' . $mediaQueries->getBasicColumnNames(),
                'company:' . $companyQueries->getBasicColumnNamesForStockTransferPrint(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function getByIdWithItems(int $stockTransferId, int $companyId): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return StockTransfer::query()
                ->select(
                    'id',
                    'transfer_type',
                    'source_location_id',
                    'destination_location_id',
                    'transfer_date',
                    'require_date',
                    'attention',
                    'reference_number',
                    'remarks',
                    'stock_transfer_reason_id',
                    'created_by_location_id',
                    'status'
                )
                ->with([
                    'sourceLocation:' . $this->getLocationColumnName(),
                    'destinationLocation:' . $this->getLocationColumnName(),
                    'items:' . $stockTransferItemQueries->getColumnNamesForRequestEdit(),
                    'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
                    'items.product:' . $productQueries->getColumnsForStockTransferEdit(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->where('company_id', $companyId)
                ->findOrFail($stockTransferId);
        }

        return StockTransfer::query()
            ->select(
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'transfer_date',
                'require_date',
                'attention',
                'reference_number',
                'remarks',
                'stock_transfer_reason_id',
                'created_by_location_id',
                'status'
            )
            ->with([
                'sourceLocation:' . $this->getLocationColumnName(),
                'destinationLocation:' . $this->getLocationColumnName(),
                'items:' . $stockTransferItemQueries->getColumnNamesForRequestEdit(),
                'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
                'items.product:' . $productQueries->getColumnsForStockTransferEdit(),
                'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function getByIdWithItemsAndBatches(int $stockTransferId, int $companyId): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);

        return StockTransfer::query()
            ->select(
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'transit_location_id',
                'transfer_date',
                'require_date',
                'attention',
                'reference_number',
                'remarks',
                'stock_transfer_reason_id',
                'created_by_location_id',
                'status',
                'transfer_order_number',
                'request_order_number'
            )
            ->with([
                'items:' . $stockTransferItemQueries->getColumnNamesForPrint(),
                'items.unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                'items.batches.batch:' . $batchQueries->getBasicColumnNames(),
                'items.units:' . $stockTransferItemUnitQueries->getColumnNames(),
                'items.units.batch:' . $batchQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function getByIdWithItemsForEditRequestOrder(int $stockTransferId, int $companyId): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return StockTransfer::query()
                ->select(
                    'id',
                    'transfer_type',
                    'source_location_id',
                    'destination_location_id',
                    'attention',
                    'reference_number',
                    'remarks',
                    'status'
                )
                ->with([
                    'items:' . $stockTransferItemQueries->getColumnNamesForRequestEdit(),
                    'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
                    'items.product:' . $productQueries->getColumnsForStockTransferEdit(),
                    'sourceLocation:' . $this->getLocationColumnName(),
                    'destinationLocation:' . $this->getLocationColumnName(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->where('company_id', $companyId)
                ->findOrFail($stockTransferId);
        }

        return StockTransfer::query()
            ->select(
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'attention',
                'reference_number',
                'remarks',
                'status'
            )
            ->with([
                'items:' . $stockTransferItemQueries->getColumnNamesForRequestEdit(),
                'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
                'items.product:' . $productQueries->getColumnsForStockTransferEdit(),
                'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'sourceLocation:' . $this->getLocationColumnName(),
                'destinationLocation:' . $this->getLocationColumnName(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function update(array $stockTransferDetails, int $stockTransferId, int $companyId): StockTransfer
    {
        $stockTransfer = StockTransfer::where('company_id', $companyId)->lockForUpdate()->findOrFail($stockTransferId);
        $stockTransfer->update($stockTransferDetails);

        return $stockTransfer;
    }

    public function updateReceivedDateAndStatus(StockTransfer $stockTransfer, string $receivedDate): void
    {
        $isTransitTargetAchieved = $this->getIsTransitTargetAchieved(
            (string) $stockTransfer->transfer_date,
            $receivedDate,
            (int) $stockTransfer->average_days,
        );

        $stockTransfer->status = StatusTypes::RECEIVED->value;
        $stockTransfer->received_at = Carbon::now()->format('Y-m-d H:i:s');
        $stockTransfer->received_date = $receivedDate;
        $stockTransfer->is_transit_target_achieved = $isTransitTargetAchieved;
        $stockTransfer->save();
    }

    private function getIsTransitTargetAchieved(string $transferDate, string $receivedDate, int $averageDays): bool
    {
        $transferDate = Carbon::parse($transferDate);
        $receivedDate = Carbon::parse($receivedDate);
        $diff = $receivedDate->diffInDays($transferDate);

        return $diff <= $averageDays;
    }

    public function updateStatus(StockTransfer $stockTransfer, int $statusId): void
    {
        $stockTransfer->status = $statusId;
        if ($statusId === StatusTypes::OPEN->value) {
            $stockTransfer->opened_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($statusId === StatusTypes::SHIPPED->value) {
            $stockTransfer->shipped_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($statusId === StatusTypes::DISCREPANCY->value) {
            $stockTransfer->discrepancy_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($statusId === StatusTypes::CLOSED->value) {
            $stockTransfer->closed_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($statusId === StatusTypes::CANCELLED->value) {
            $stockTransfer->cancelled_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($statusId === StatusTypes::REJECTED->value) {
            $stockTransfer->rejected_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($statusId === StatusTypes::APPROVED->value) {
            $stockTransfer->approved_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        $stockTransfer->save();
    }

    public function getLocationAndStatusById(int $stockTransferId, int $companyId): StockTransfer
    {
        $locationQueries = resolve(LocationQueries::class);

        return StockTransfer::query()
            ->select(
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'transit_location_id',
                'status',
                'request_order_number',
                'transfer_order_number',
                'is_transit_target_achieved',
                'average_days',
            )
            ->with([
                'sourceLocation:' . $locationQueries->getNameColumnName(),
                'destinationLocation:' . $locationQueries->getNameColumnName(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function getLocationById(int $stockTransferId, int $companyId): StockTransfer
    {
        return StockTransfer::query()
            ->select('id', 'source_location_id', 'destination_location_id')
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function getStatusById(int $stockTransferId, int $companyId): StockTransfer
    {
        return StockTransfer::query()
            ->select('id', 'status')
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function getByIdForRequestOrder(int $stockTransferId, int $companyId): StockTransfer
    {
        return StockTransfer::query()
            ->select('id', 'transfer_type', 'source_location_id', 'destination_location_id', 'status')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($stockTransferId);
    }

    public function updateApproveAndTransferNumber(
        int $stockTransferId,
        int $companyId,
        string $sequenceInNumber,
        string $sequenceOutNumber,
    ): void {
        $stockTransfer = StockTransfer::query()
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
        $stockTransfer->transfer_in_number = $sequenceInNumber;
        $stockTransfer->transfer_out_number = $sequenceOutNumber;
        $stockTransfer->approved_at = now()->format('Y-m-d H:i:s');
        $stockTransfer->status = StatusTypes::APPROVED->value;
        $stockTransfer->save();
    }

    public function updateShippedAndTransferNumber(
        int $stockTransferId,
        int $companyId,
        string $sequenceInNumber,
        string $sequenceOutNumber,
        StockTransferShippedData $stockTransferShippedData,
    ): void {
        $stockTransfer = StockTransfer::query()
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);

        $stockTransfer->transfer_in_number = $sequenceInNumber;
        $stockTransfer->transfer_out_number = $sequenceOutNumber;
        $stockTransfer->shipped_at = now()->format('Y-m-d H:i:s');

        if ($stockTransferShippedData->shipped_type === ShippedTypes::TRANSIT->value) {
            /** @var int $transitLocationId */
            $transitLocationId = $stockTransferShippedData->location_id;

            $stockTransfer->status = StatusTypes::TRANSIT->value;
            $stockTransfer->transit_location_id = $transitLocationId;
            $stockTransfer->save();

            return;
        }

        $stockTransfer->transit_location_id = null;
        $stockTransfer->status = StatusTypes::SHIPPED->value;
        $stockTransfer->save();
    }

    public function getByIdWithItemsAndUnits(int $stockTransferId, int $companyId): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);

        return StockTransfer::query()
            ->select(
                'id',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'status',
                'request_order_number',
                'transfer_order_number',
                'received_date',
            )
            ->with([
                'items:' . $stockTransferItemQueries->getColumnNames(),
                'items.units:' . $stockTransferItemUnitQueries->getColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function getByIdWithItemsBatchesAndUnits(int $stockTransferId, int $companyId): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return StockTransfer::query()
            ->select(
                'id',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'transfer_type',
                'transfer_order_number',
                'request_order_number',
                'received_date',
                'status'
            )
            ->with(
                'items:' . $stockTransferItemQueries->getColumnNames(),
                'items.unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                'items.batches.batch:' . $batchQueries->getBasicColumnNames(),
                'items.units:' . $stockTransferItemUnitQueries->getColumnNames(),
                'items.units.batch:' . $batchQueries->getBasicColumnNames(),
                'destinationLocation:' . $locationQueries->getNameColumnName(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferId);
    }

    public function loadItemsAndUnits(StockTransfer $stockTransfer): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);

        return $stockTransfer->load(
            'items:' . $stockTransferItemQueries->getColumnNames(),
            'items.units:' . $stockTransferItemUnitQueries->getColumnNames(),
        );
    }

    public function loadItemsUnitsAndBatches(StockTransfer $stockTransfer): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);

        return $stockTransfer->load(
            'items:' . $stockTransferItemQueries->getColumnNames(),
            'items.units:' . $stockTransferItemUnitQueries->getColumnNames(),
            'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
        );
    }

    public function loadSourceLocationStoreAndStoreManagers(StockTransfer $stockTransfer): StockTransfer
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);

        return $stockTransfer->load(
            'sourceLocation:' . $locationQueries->getNameColumnName(),
            'sourceLocation.storeManagers:' . $storeManagerQueries->getIdColumnName(),
        );
    }

    public function loadSourceLocationWarehouseAndWarehouseManagers(StockTransfer $stockTransfer): StockTransfer
    {
        $locationQueries = resolve(LocationQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);

        return $stockTransfer->load(
            'sourceLocation:' . $locationQueries->getNameColumnName(),
            'sourceLocation.warehouseManagers:' . $warehouseManagerQueries->getIdColumnName(),
        );
    }

    public function loadDestinationLocationStoreAndStoreManagers(StockTransfer $stockTransfer): StockTransfer
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);

        return $stockTransfer->load(
            'destinationLocation:' . $locationQueries->getNameColumnName(),
            'destinationLocation.storeManagers:' . $storeManagerQueries->getIdColumnName(),
        );
    }

    public function loadDestinationLocationWarehouseAndWarehouseManagers(StockTransfer $stockTransfer): StockTransfer
    {
        $locationQueries = resolve(LocationQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);

        return $stockTransfer->load(
            'destinationLocation:' . $locationQueries->getNameColumnName(),
            'destinationLocation.warehouseManagers:' . $warehouseManagerQueries->getIdColumnName(),
        );
    }

    public function loadTransitLocationWarehouseAndWarehouseManagers(StockTransfer $stockTransfer): StockTransfer
    {
        $locationQueries = resolve(LocationQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);

        return $stockTransfer->load(
            'transitLocation:' . $locationQueries->getNameColumnName(),
            'transitLocation.warehouseManagers:' . $warehouseManagerQueries->getIdColumnName(),
        );
    }

    public function loadTransitLocationStoreAndStoreManagers(StockTransfer $stockTransfer): StockTransfer
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);

        return $stockTransfer->load(
            'transitLocation:' . $locationQueries->getNameColumnName(),
            'transitLocation.storeManagers:' . $storeManagerQueries->getIdColumnName(),
        );
    }

    public function getWithItemsAndBatchDetailsById(int $stockTransferId): StockTransfer
    {
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return StockTransfer::query()
            ->select('id', 'source_location_id', 'status')
            ->with([
                'items' => function ($query): void {
                    $query->select(
                        'id',
                        'stock_transfer_id',
                        'product_id',
                        'quantity',
                        'received_quantity',
                        'discrepancy_type',
                        'package_type_id',
                        'unit_of_measure_derivative_id',
                        'package_quantity',
                        'is_extra_item'
                    )->lockForUpdate();
                },
                'items.unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.batches' => function ($query): void {
                    $query->select('id', 'stock_transfer_item_id', 'batch_id', 'quantity')->lockForUpdate();
                },
            ])
            ->lockForUpdate()
            ->findOrFail($stockTransferId);
    }

    public function loadItemsAndBatches(StockTransfer $stockTransfer): StockTransfer
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return $stockTransfer->load(
            'items:' . $stockTransferItemQueries->getColumnNames(),
            'items.unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
        );
    }

    public function loadItemsBatchesAndProduct(StockTransfer $stockTransfer): StockTransfer
    {
        $productQueries = resolve(ProductQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return $stockTransfer->load(
                'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
                'items.batches.batch:' . $batchQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getColumnsForStockTransferEdit(),
                'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            );
        }

        return $stockTransfer->load(
            'items.batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
            'items.transaction:' . $stockTransferItemTransactionQueries->getRemarksColumn(),
            'items.batches.batch:' . $batchQueries->getBasicColumnNames(),
            'items.product:' . $productQueries->getColumnsForStockTransferEdit(),
            'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
            'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
        );
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function filterByCompanyAndDiscrepancyStatus(int $companyId): Closure
    {
        return fn ($query) => $query->select(
            'id'
        )->where('company_id', $companyId)->where('status', StatusTypes::DISCREPANCY->value);
    }

    public function filterForStockOutReport(int $companyId, array $filterData): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->where('source_location_id', $filterData['location_id']);
    }

    public function filterForStockInReport(int $companyId, array $filterData): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->where('destination_location_id', $filterData['location_id']);
    }

    public function getStockTransferColumns(): string
    {
        return 'id,transfer_out_number,transfer_in_number,source_location_id,destination_location_id,request_order_number,transfer_order_number';
    }

    public function getStockTransferColumnsForStockCardPrint(): string
    {
        return 'id,transfer_type,transfer_out_number,transfer_in_number,source_location_id,destination_location_id,request_order_number,transfer_order_number,remarks,created_at,status';
    }

    public function getStockTransferColumnsForReport(): string
    {
        return 'id,transfer_type,status,stock_transfer_reason_id,reference_number,remarks,transfer_out_number,transfer_in_number,transfer_order_number,source_location_id,destination_location_id,created_at,transfer_date,require_date,received_date,opened_at,approved_at,shipped_at,received_at,discrepancy_at,closed_at,cancelled_at,rejected_at';
    }

    public function getColumnsForReport(): string
    {
        return 'id,source_location_id,destination_location_id';
    }

    public function getReferenceNumberColumns(): string
    {
        return 'id,transfer_out_number,transfer_in_number,transfer_order_number,request_order_number';
    }

    public function getByDateAndLocationWithStockTransfer(array $filterData, int $companyId): Collection
    {
        $stockTransferReasonQueries = resolve(StockTransferReasonQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $locationQuery = function ($query) use ($filterData): void {
            $query->where(function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('source_location_id', $filterData['location_ids']);
            })
                ->orWhere(function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('destination_location_id', $filterData['location_ids']);
                });
        };

        $relations = [
            'stockTransferReason:' . $stockTransferReasonQueries->getBasicColumn(),
            'items' => function ($query) use ($filterData): void {
                $query
                    ->select(
                        'id',
                        'stock_transfer_id',
                        'product_id',
                        'quantity',
                        'received_quantity',
                        'discrepancy_type',
                        'package_type_id',
                        'package_quantity',
                    )
                    ->whereHas('product', function ($query): void {
                        if (config('app.product_variant')) {
                            $query->select('id', 'master_product_id')
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_selling_item', false);
                                });
                        } else {
                            $query->select('id')
                                ->where('is_non_selling_item', false);
                        }
                    })
                    ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->select('products.id')
                                    ->from('products')
                                    ->join(
                                        'master_products',
                                        'products.master_product_id',
                                        '=',
                                        'master_products.id'
                                    )
                                    ->where('master_products.article_number', $filterData['article_number']);
                            } else {
                                $query->select('products.id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            }
                        });
                    })
                    ->when(
                        array_key_exists(
                            'product_collection_id',
                            $filterData
                        ) && null !== $filterData['product_collection_id'],
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    );
            },
            'items.product' => $productQueries->getSellingProduct(),
            'sourceLocation:' . $this->getLocationColumnName(),
            'destinationLocation:' . $this->getLocationColumnName(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'items.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'items.product.color:' . $colorQueries->getBasicColumnNames(),
                'items.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return StockTransfer::query()
            ->select(
                'id',
                'transfer_type',
                'stock_transfer_reason_id',
                'reference_number',
                'remarks',
                'transfer_out_number',
                'transfer_in_number',
                'transfer_order_number',
                'source_location_id',
                'destination_location_id',
                'created_at',
                'status',
                'request_order_number',
                'transfer_date',
                'require_date',
                'received_date',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
            )
            ->with($relations)
            ->whereHas('items', function ($query): void {
                $query->select('id', 'product_id')
                    ->whereHas('product', function ($query): void {
                        if (config('app.product_variant')) {
                            $query->select('id', 'master_product_id')
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_selling_item', false);
                                });
                        } else {
                            $query->select('id')
                                ->isSellingProduct();
                        }
                    });
            })
            ->where('company_id', $companyId)
            ->where($this->filterByDateType($filterData))
            ->where(function ($query) use ($locationQuery): void {
                $query->where(function ($query) use ($locationQuery): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where($locationQuery);
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->whereNot('status', StatusTypes::DRAFT->value)
                        ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                });
            })
            ->whereHas('items', function ($query) use ($filterData): void {
                $query->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                    $query->where('product_id', $filterData['product_id']);
                })
                    ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->select('products.id')
                                    ->from('products')
                                    ->join(
                                        'master_products',
                                        'products.master_product_id',
                                        '=',
                                        'master_products.id'
                                    )
                                    ->where('master_products.article_number', $filterData['article_number']);
                            } else {
                                $query->select('products.id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            }
                        });
                    })
                    ->when(
                        array_key_exists(
                            'product_collection_id',
                            $filterData
                        ) && null !== $filterData['product_collection_id'],
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    );
            })
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::REQUEST_ORDER->value,
                function ($query): void {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_ORDER->value,
                function ($query): void {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_IN->value,
                function ($query) use ($filterData): void {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->whereIntegerInRaw('destination_location_id', $filterData['location_ids'])
                        ->when(
                            $filterData['additional_location_id'],
                            function ($query) use ($filterData): void {
                                $query->where('source_location_id', $filterData['additional_location_id']);
                            }
                        );
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_OUT->value,
                function ($query) use ($filterData): void {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->whereIntegerInRaw('source_location_id', $filterData['location_ids'])
                        ->when(
                            $filterData['additional_location_id'],
                            function ($query) use ($filterData): void {
                                $query->where('destination_location_id', $filterData['additional_location_id']);
                            }
                        );
                }
            )
            ->when($filterData['status_type'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('status', (array) $filterData['status_type']);
            })
            ->get();
    }

    public function getByDateAndLocationWithStockTransferAndProducts(array $filterData, int $companyId): Collection
    {
        $stockTransferReasonQueries = resolve(StockTransferReasonQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $locationQuery = function ($query) use ($filterData): void {
            $query->where(function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('source_location_id', $filterData['location_ids']);
            })
                ->orWhere(function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('destination_location_id', $filterData['location_ids']);
                });
        };

        $relations = [
            'stockTransferReason:' . $stockTransferReasonQueries->getBasicColumn(),
            'items' => function ($query) use ($filterData): void {
                $query
                    ->select(
                        'id',
                        'stock_transfer_id',
                        'product_id',
                        'quantity',
                        'received_quantity',
                        'discrepancy_type',
                        'package_type_id',
                        'package_quantity',
                    )
                    ->whereHas('product', function ($query): void {
                        if (config('app.product_variant')) {
                            $query->select('id', 'master_product_id')
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_selling_item', false);
                                });
                        } else {
                            $query->select('id')
                                ->where('is_non_selling_item', false);
                        }
                    })
                    ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->select('products.id')
                                    ->from('products')
                                    ->join(
                                        'master_products',
                                        'products.master_product_id',
                                        '=',
                                        'master_products.id'
                                    )
                                    ->where('master_products.article_number', $filterData['article_number']);
                            } else {
                                $query->select('products.id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            }
                        });
                    })
                    ->when(
                        array_key_exists(
                            'product_collection_id',
                            $filterData
                        ) && null !== $filterData['product_collection_id'],
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    );
            },
            'requestedBy:' . $this->getRequestedByColumns(),
            'requestedBy.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
            'items.product:' . $productQueries->getBasicColumnNames(),
            'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'sourceLocation:' . $this->getLocationColumnName(),
            'destinationLocation:' . $this->getLocationColumnName(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'items.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'items.product.color:' . $colorQueries->getBasicColumnNames(),
                'items.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return StockTransfer::query()
            ->select(
                'id',
                'transfer_type',
                'stock_transfer_reason_id',
                'reference_number',
                'remarks',
                'transfer_out_number',
                'transfer_in_number',
                'transfer_order_number',
                'source_location_id',
                'destination_location_id',
                'created_at',
                'requested_by_id',
                'requested_by_type',
                'status',
                'request_order_number',
                'transfer_date',
                'require_date',
                'received_date',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
            )
            ->with($relations)
            ->where('company_id', $companyId)
            ->where($this->filterByDateType($filterData))
            ->whereHas('items', function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query): void {
                    if (config('app.product_variant')) {
                        $query->select('id', 'master_product_id')
                            ->whereHas('masterProduct', function ($query): void {
                                $query->where('is_non_selling_item', false);
                            });
                    } else {
                        $query->select('id')
                            ->where('is_non_selling_item', false);
                    }
                })
                    ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->select('products.id')
                                    ->from('products')
                                    ->join(
                                        'master_products',
                                        'products.master_product_id',
                                        '=',
                                        'master_products.id'
                                    )
                                    ->where('master_products.article_number', $filterData['article_number']);
                            } else {
                                $query->select('products.id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            }
                        });
                    })
                    ->when(
                        array_key_exists(
                            'product_collection_id',
                            $filterData
                        ) && null !== $filterData['product_collection_id'],
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    );
            })
            ->where(function ($query) use ($locationQuery): void {
                $query->where(function ($query) use ($locationQuery): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where($locationQuery);
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->whereNot('status', StatusTypes::DRAFT->value)
                        ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                });
            })
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::REQUEST_ORDER->value,
                function ($query): void {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_ORDER->value,
                function ($query): void {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_IN->value,
                function ($query) use ($filterData): void {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->whereIntegerInRaw('destination_location_id', $filterData['location_ids'])
                        ->when(
                            $filterData['additional_location_id'],
                            function ($query) use ($filterData): void {
                                $query->where('source_location_id', $filterData['additional_location_id']);
                            }
                        );
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_OUT->value,
                function ($query) use ($filterData): void {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->whereIntegerInRaw('source_location_id', $filterData['location_ids'])
                        ->when(
                            $filterData['additional_location_id'],
                            function ($query) use ($filterData): void {
                                $query->where('destination_location_id', $filterData['additional_location_id']);
                            }
                        );
                }
            )
            ->when($filterData['status_type'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('status', $filterData['status_type']);
            })
            ->get();
    }

    public function getStockTransfersExport(array $filterData, int $companyId): Collection
    {
        if ($filterData['location_id']) {
            $locationQuery = function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('source_location_id', $filterData['location_id']);
                })
                    ->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    });
            };

            return $this->commonListQuery($filterData, $companyId)
                ->where(function ($query) use ($locationQuery, $filterData): void {
                    $query->where(function ($query) use ($locationQuery): void {
                        $query->where('status', StatusTypes::DRAFT->value)
                            ->where($locationQuery);
                    })->orWhere(function ($query) use ($locationQuery): void {
                        $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                            ->where($locationQuery);
                    })->orWhere(function ($query) use ($locationQuery, $filterData): void {
                        $query->whereNot('status', StatusTypes::DRAFT->value)
                            ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                            ->where($locationQuery)
                            ->orWhere(function ($query) use ($filterData): void {
                                $query->where('transit_location_id', $filterData['location_id']);
                            });
                    });
                })
                ->when($filterData['transfer_type'], function ($query) use ($filterData): void {
                    if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value && (int) $filterData['location_id'] === 0) {
                        $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                            ]);
                    }

                    if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value && (int) $filterData['location_id'] === 0) {
                        $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                            ]);
                    }

                    if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value && $filterData['location_id'] > 0) {
                        $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                            ]);
                    }

                    if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value && $filterData['location_id'] > 0) {
                        $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                            ]);
                    }

                    if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value && $filterData['location_id'] > 0) {
                        $query->where(function ($query) use ($filterData): void {
                            $query->where(function ($query): void {
                                $query->whereNotIn('status', [
                                    StatusTypes::DRAFT->value,
                                    StatusTypes::SYSTEM_GENERATED->value,
                                    StatusTypes::OPEN->value,
                                    StatusTypes::CANCELLED->value,
                                    StatusTypes::REJECTED->value,
                                    StatusTypes::APPROVED->value,
                                    StatusTypes::TRANSIT_OUT->value,
                                ]);
                            })
                                ->where(function ($query) use ($filterData): void {
                                    $query->where(function ($query) use ($filterData): void {
                                        $query->where('destination_location_id', $filterData['location_id']);
                                    })
                                        ->orWhere(function ($query) use ($filterData): void {
                                            $query->where('transit_location_id', $filterData['location_id']);
                                        });
                                });
                        });
                    }

                    if ((int) $filterData['transfer_type'] !== TransferTypes::TRANSFER_OUT->value) {
                        return;
                    }

                    if ($filterData['location_id'] <= 0) {
                        return;
                    }

                    $query->where(function ($query) use ($filterData): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_IN->value,
                                StatusTypes::TRANSIT->value,
                            ]);
                        })
                            ->where(function ($query) use ($filterData): void {
                                $query->where(function ($query) use ($filterData): void {
                                    $query->where('source_location_id', $filterData['location_id']);
                                });
                            });
                    });
                })
                ->when(
                    $filterData['dashboard_transfer_type'] && $filterData['location_id'] > 0,
                    function ($query) use ($filterData): void {
                        if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::REQUEST_ORDER->value) {
                            $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                        }

                        if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::TRANSFER_ORDER->value) {
                            $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                        }
                    }
                )
                ->get();
        }

        return $this->stockTransferQuery($filterData, $companyId)->get();
    }

    public function getStoreManagerStockTransfersExport(
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        return $this->storeManagerStockTransferQuery($filterData, $companyId, $locationId)->get();
    }

    public function getLocationColumnName(): string
    {
        return 'id,name,code,type_id';
    }

    public function getStatusColumn(): string
    {
        return 'id,status';
    }

    public function getCountByReferenceNumber(string $referenceNumber, int $selectedModuleFilter, int $companyId): int
    {
        return StockTransfer::query()
            ->when($selectedModuleFilter === BarcodePrintModuleTypes::TRANSFER_ORDER->value, function ($query) use (
                $referenceNumber
            ): void {
                $query->where('transfer_order_number', $referenceNumber);
            })
            ->when($selectedModuleFilter === BarcodePrintModuleTypes::TRANSFER_IN->value, function ($query) use (
                $referenceNumber
            ): void {
                $query->where('transfer_in_number', $referenceNumber);
            })
            ->when($selectedModuleFilter === BarcodePrintModuleTypes::TRANSFER_OUT->value, function ($query) use (
                $referenceNumber
            ): void {
                $query->where('transfer_out_number', $referenceNumber);
            })
            ->when($selectedModuleFilter === BarcodePrintModuleTypes::REQUEST_ORDER->value, function ($query) use (
                $referenceNumber
            ): void {
                $query->where('request_order_number', $referenceNumber);
            })
            ->where('company_id', $companyId)
            ->count();
    }

    public function transferOrderStatusCount(int $transferType, array $filterData, int $companyId): Collection
    {
        return $this->getStatusCount($transferType, $filterData, $companyId)->get();
    }

    public function requestOrderStatusCount(int $transferType, array $filterData, int $companyId): Collection
    {
        return $this->getStatusCount($transferType, $filterData, $companyId)->get();
    }

    public function storeManagerTransferOrderStatusCount(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        return $this->getStoreManagerStatusCount($filterData, $companyId, $locationId)
            ->whereIntegerInRaw('transfer_type', $transferType)
            ->get();
    }

    public function storeManagerTransferOrRequestOrderStatusCount(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        $locationQuery = function ($query) use ($locationId): void {
            $query->where(function ($query) use ($locationId): void {
                $query->where('source_location_id', $locationId);
            })
                ->orWhere(function ($query) use ($locationId): void {
                    $query->where('destination_location_id', $locationId);
                });
        };

        return StockTransfer::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->groupBy('status')
            ->where(function ($query) use ($locationQuery, $locationId, $transferType): void {
                $query->where(function ($query) use ($locationId, $transferType): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where(function ($query) use ($locationId, $transferType): void {
                            $query->where(function ($query) use ($locationId, $transferType): void {
                                $query->where('source_location_id', $locationId)
                                    ->whereIntegerInRaw('transfer_type', $transferType);
                            })
                                ->orWhere(function ($query) use ($locationId, $transferType): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->whereIntegerInRaw('transfer_type', $transferType);
                                });
                        });
                })->orWhere(function ($query) use ($locationId, $transferType): void {
                    $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where(function ($query) use ($locationId, $transferType): void {
                            $query->where(function ($query) use ($locationId, $transferType): void {
                                $query->where('source_location_id', $locationId)
                                    ->whereIntegerInRaw('transfer_type', $transferType);
                            })
                                ->orWhere(function ($query) use ($locationId, $transferType): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->whereIntegerInRaw('transfer_type', $transferType);
                                });
                        });
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->whereNot('status', StatusTypes::DRAFT->value)
                        ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                });
            })
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('source_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    });
                });
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData, $locationId): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value) {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->where('destination_location_id', $locationId);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_OUT->value) {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->where('source_location_id', $locationId);
                }
            })->get();
    }

    public function transferOrderStatusCountForWarehouseManager(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        $locationQuery = function ($query) use ($locationId, $transferType): void {
            $query->where(function ($query) use ($locationId, $transferType): void {
                $query->where('source_location_id', $locationId)
                    ->whereIntegerInRaw('transfer_type', $transferType);
            })
                ->orWhere(function ($query) use ($locationId, $transferType): void {
                    $query->where('destination_location_id', $locationId)
                        ->whereIntegerInRaw('transfer_type', $transferType);
                });
        };

        return $this->getWarehouseManagerStatusCount($transferType, $filterData, $companyId, $locationId)
            ->whereIntegerInRaw('transfer_type', $transferType)
            ->where($locationQuery)
            ->get();
    }

    public function requestOrderStatusCountForWarehouseManager(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        $locationQuery = function ($query) use ($locationId, $transferType): void {
            $query->where(function ($query) use ($locationId, $transferType): void {
                $query->where('source_location_id', $locationId)
                    ->whereIntegerInRaw('transfer_type', $transferType);
            })
                ->orWhere(function ($query) use ($locationId, $transferType): void {
                    $query->where('destination_location_id', $locationId)
                        ->whereIntegerInRaw('transfer_type', $transferType);
                });
        };

        return $this->getWarehouseManagerStatusCount($transferType, $filterData, $companyId, $locationId)
            ->whereIntegerInRaw('transfer_type', $transferType)
            ->where($locationQuery)
            ->get();
    }

    public function storeManagerRequestOrderStatusCount(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        $locationQuery = function ($query) use ($locationId, $transferType): void {
            $query->where(function ($query) use ($locationId, $transferType): void {
                $query->where('source_location_id', $locationId)
                    ->whereIntegerInRaw('transfer_type', $transferType);
            })
                ->orWhere(function ($query) use ($locationId, $transferType): void {
                    $query->where('destination_location_id', $locationId)
                        ->whereIntegerInRaw('transfer_type', $transferType);
                });
        };

        return $this->getStoreManagerStatusCount($filterData, $companyId, $locationId)
            ->whereIntegerInRaw('transfer_type', $transferType)
            ->where($locationQuery)
            ->get();
    }

    public function storeManagerTransferInAndOutStatusCount(
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        return $this->getStoreManagerStatusCount($filterData, $companyId, $locationId)->get();
    }

    public function warehouseManagerTransferOrderStatusCount(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        return $this->getWarehouseManagerStatusCount($transferType, $filterData, $companyId, $locationId)->get();
    }

    public function warehouseManagerTransferOrRequestOrderStatusCount(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        return $this->getWarehouseManagerTransferOrRequestOrderStatusCount(
            $transferType,
            $filterData,
            $companyId,
            $locationId
        )->get();
    }

    public function setUpdatedAtById(int $stockTransferId): void
    {
        $stockTransfer = StockTransfer::select('id')
            ->findOrFail($stockTransferId);

        $this->setUpdatedAt($stockTransfer);
    }

    public function setUpdatedAt(StockTransfer $stockTransfer): void
    {
        $stockTransfer->touch();
    }

    public function storeManagerListQueryForApi(array $filterData, int $companyId): LengthAwarePaginator
    {
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);

        return $this->storeManagerStockTransferQuery($filterData, $companyId, (int) $filterData['location_id'])
            ->with(['stockTransferAverageLeadDay:' . $stockTransferAverageLeadDaysQueries->getAverageDaysColumn()])
            ->paginate($filterData['per_page']);
    }

    public function warehouseManagerListQueryForApi(array $filterData, int $companyId): LengthAwarePaginator
    {
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);

        return $this->warehouseManagerStockTransferQuery($filterData, $companyId, (int) $filterData['location_id'])
            ->with(['stockTransferAverageLeadDay:' . $stockTransferAverageLeadDaysQueries->getAverageDaysColumn()])
            ->paginate($filterData['per_page']);
    }

    public function getBasicColumns(): string
    {
        return 'id,source_location_id,destination_location_id,status,transfer_type';
    }

    public function getGroupBySourceLocationIdAndType(): Collection
    {
        return StockTransfer::query()
            ->select('id', 'source_location_id')
            ->where($this->statusFilter())
            ->groupBy('source_location_id')
            ->get();
    }

    public function getStockTransferListWithAverageDayBySourceLocationAndType(int $sourceLocationId): Collection
    {
        return StockTransfer::query()
            ->select(
                'id',
                'stock_transfer_average_lead_day_id',
                'source_location_id',
                'destination_location_id',
                DB::raw('AVG(DATEDIFF(received_at, shipped_at)) AS average')
            )
            ->having('average', '>', 0)
            ->where($this->statusFilter())
            ->where('source_location_id', $sourceLocationId)
            ->groupBy('source_location_id', 'destination_location_id')
            ->get();
    }

    private function getRequestedByColumns(): string
    {
        return 'id,employee_id';
    }

    private function warehouseManagerStockTransferQuery(
        array $filterData,
        int $companyId,
        int $locationId,
    ): Builder {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);

        $locationQuery = function ($query) use ($locationId): void {
            $query->where(function ($query) use ($locationId): void {
                $query->where('source_location_id', $locationId);
            })
                ->orWhere(function ($query) use ($locationId): void {
                    $query->where('destination_location_id', $locationId);
                })
                ->orWhere(function ($query) use ($locationId): void {
                    $query->where('transit_location_id', $locationId);
                });
        };

        return StockTransfer::query()
            ->with([
                'sourceLocation:' . $this->getLocationColumnName(),
                'destinationLocation:' . $this->getLocationColumnName(),
                'items:' . $stockTransferItemQueries->getColumnNames(),
                'transitLocation:' . $this->getLocationColumnName(),
            ])
            ->select(
                'id',
                'transfer_type',
                'stock_transfer_average_lead_day_id',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'requested_by_type',
                'requested_by_id',
                'created_by_location_id',
                'status',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
                'created_at',
                'updated_at',
                'transfer_date',
                'require_date',
                'transfer_out_number',
                'transfer_in_number',
                'request_order_number',
                'transfer_order_number',
                'transit_location_id',
                'average_days',
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('status', StatusTypes::getMatchingCases($filterData['search_text']))
                        ->orWhereHas('sourceLocation', $this->searchLocationByName($filterData['search_text']))
                        ->orWhereHas('destinationLocation', $this->searchLocationByName($filterData['search_text']))
                        ->orWhereAny(
                            [
                                'reference_number',
                                'id',
                                'transfer_out_number',
                                'transfer_in_number',
                                'request_order_number',
                                'transfer_order_number',
                            ],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->where(function ($query) use ($locationId, $locationQuery): void {
                $query->where(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->whereNot('status', StatusTypes::DRAFT->value)
                        ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData, $locationId): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value) {
                    $query->where(function ($query) use ($locationId): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_OUT->value,
                            ]);
                        })
                            ->where(function ($query) use ($locationId): void {
                                $query->where(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId);
                                })
                                    ->orWhere(function ($query) use ($locationId): void {
                                        $query->where('transit_location_id', $locationId);
                                    });
                            });
                    });
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_OUT->value) {
                    $query->where(function ($query) use ($locationId): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_IN->value,
                                StatusTypes::TRANSIT->value,
                            ]);
                        })
                            ->where(function ($query) use ($locationId): void {
                                $query->where(function ($query) use ($locationId): void {
                                    $query->where('source_location_id', $locationId);
                                });
                            });
                    });
                }
            })
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('source_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    })
                        ->orWhere(function ($query) use ($filterData): void {
                            $query->where('transit_location_id', $filterData['location_id']);
                        });
                });
            })
            ->when($filterData['stock_transfer_date'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['stock_transfer_date'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['stock_transfer_date'][1]));
            })
            ->when($filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            })
            ->when($filterData['stock_transfer_id'], function ($query) use ($filterData): void {
                $query->where('id', $filterData['stock_transfer_id']);
            })
            ->when($filterData['dashboard_transfer_type'], function ($query) use ($filterData): void {
                if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                }

                if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                }
            });
    }

    private function storeManagerStockTransferQuery(array $filterData, int $companyId, int $locationId): Builder
    {
        $locationQuery = function ($query) use ($locationId): void {
            $query->where(function ($query) use ($locationId): void {
                $query->where('source_location_id', $locationId);
            })
                ->orWhere(function ($query) use ($locationId): void {
                    $query->where('destination_location_id', $locationId);
                })
                ->orWhere(function ($query) use ($locationId): void {
                    $query->where('transit_location_id', $locationId);
                });
        };

        return $this->commonListQuery($filterData, $companyId)
            ->where(function ($query) use ($locationQuery, $locationId): void {
                $query->where(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->whereNot('status', StatusTypes::DRAFT->value)
                        ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                });
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData, $locationId): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value) {
                    $query->where(function ($query) use ($locationId): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_OUT->value,
                            ]);
                        })
                            ->where(function ($query) use ($locationId): void {
                                $query->where(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId);
                                })
                                    ->orWhere(function ($query) use ($locationId): void {
                                        $query->where('transit_location_id', $locationId);
                                    });
                            });
                    });
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_OUT->value) {
                    $query->where(function ($query) use ($locationId): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_IN->value,
                                StatusTypes::TRANSIT->value,
                            ]);
                        })
                            ->where(function ($query) use ($locationId): void {
                                $query->where(function ($query) use ($locationId): void {
                                    $query->where('source_location_id', $locationId);
                                });
                            });
                    });
                }
            })
            ->when($filterData['dashboard_transfer_type'], function ($query) use ($filterData): void {
                if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                }

                if ((int) $filterData['dashboard_transfer_type'] === StockTransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                }
            });
    }

    private function stockTransferQuery(array $filterData, int $companyId): Builder
    {
        return $this->commonListQuery($filterData, $companyId)
            ->when($filterData['transfer_type'], function ($query) use ($filterData): void {
                if ((int) $filterData['transfer_type'] === StockTransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                }

                if ((int) $filterData['transfer_type'] === StockTransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                }
            });
    }

    private function commonListQuery(array $filterData, int $companyId): Builder
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);

        return StockTransfer::query()
            ->with([
                'sourceLocation:' . $this->getLocationColumnName(),
                'destinationLocation:' . $this->getLocationColumnName(),
                'transitLocation:' . $this->getLocationColumnName(),
                'items:' . $stockTransferItemQueries->getColumnNames(),
            ])
            ->select(
                'id',
                'transfer_type',
                'stock_transfer_average_lead_day_id',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'requested_by_type',
                'requested_by_id',
                'created_by_location_id',
                'status',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
                'created_at',
                'updated_at',
                'transfer_date',
                'require_date',
                'transfer_out_number',
                'transfer_in_number',
                'request_order_number',
                'transfer_order_number',
                'transit_location_id',
                'average_days',
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], $this->searchTextFilter($filterData))
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('source_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    })
                        ->orWhere(function ($query) use ($filterData): void {
                            $query->where('transit_location_id', $filterData['location_id']);
                        });
                });
            })
            ->when($filterData['stock_transfer_date'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['stock_transfer_date'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['stock_transfer_date'][1]));
            })
            ->when($filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            })
            ->when($filterData['stock_transfer_id'], function ($query) use ($filterData): void {
                $query->where('id', $filterData['stock_transfer_id']);
            });
    }

    private function getStatusCount(int $transferType, array $filterData, int $companyId): Builder
    {
        return StockTransfer::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('transfer_type', $transferType)
            ->where('company_id', $companyId)
            ->groupBy('status')
            ->when($filterData['search_text'], $this->searchTextFilter($filterData))
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('source_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('transit_location_id', $filterData['location_id']);
                    });
                });
            })
            ->when($filterData['stock_transfer_date'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', CommonFunctions::addStartTime($filterData['stock_transfer_date'][0]))
                    ->where('updated_at', '<=', CommonFunctions::addEndTime($filterData['stock_transfer_date'][1]));
            })
            ->when($filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value && 0 === $filterData['location_id']) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::CLOSED->value,
                            StatusTypes::SHIPPED->value,
                            StatusTypes::RECEIVED->value,
                            StatusTypes::DISCREPANCY->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value && 0 === $filterData['location_id']) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::CLOSED->value,
                            StatusTypes::SHIPPED->value,
                            StatusTypes::RECEIVED->value,
                            StatusTypes::DISCREPANCY->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value && $filterData['location_id'] > 0) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value && $filterData['location_id'] > 0) {
                    $query->where(function ($query): void {
                        $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                            ]);
                    });
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value && $filterData['location_id'] > 0) {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_OUT->value,
                            ]);
                        })
                            ->where(function ($query) use ($filterData): void {
                                $query->where(function ($query) use ($filterData): void {
                                    $query->where('destination_location_id', $filterData['location_id']);
                                })
                                    ->orWhere(function ($query) use ($filterData): void {
                                        $query->where('transit_location_id', $filterData['location_id']);
                                    });
                            });
                    });
                }

                if ((int) $filterData['transfer_type'] !== TransferTypes::TRANSFER_OUT->value) {
                    return;
                }

                if ($filterData['location_id'] <= 0) {
                    return;
                }

                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query): void {
                        $query->whereNotIn('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                            StatusTypes::TRANSIT_IN->value,
                            StatusTypes::TRANSIT->value,
                        ]);
                    })
                        ->where(function ($query) use ($filterData): void {
                            $query->where(function ($query) use ($filterData): void {
                                $query->where('source_location_id', $filterData['location_id'])
                                    ->where('status', StatusTypes::TRANSIT_OUT->value);
                            });
                        });
                });
            });
    }

    private function getStoreManagerStatusCount(array $filterData, int $companyId, int $locationId): Builder
    {
        return StockTransfer::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->groupBy('status')
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('source_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('transit_location_id', $filterData['location_id']);
                    });
                });
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData, $locationId): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value) {
                    $query->where(function ($query): void {
                        $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                            ]);
                    });
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value) {
                    $query->where(function ($query) use ($locationId): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_OUT->value,
                            ]);
                        })
                            ->where(function ($query) use ($locationId): void {
                                $query->where(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId);
                                })
                                    ->orWhere(function ($query) use ($locationId): void {
                                        $query->where('transit_location_id', $locationId);
                                    });
                            });
                    });
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_OUT->value) {
                    $query->where(function ($query) use ($locationId): void {
                        $query->where(function ($query): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                                StatusTypes::TRANSIT_IN->value,
                                StatusTypes::TRANSIT->value,
                            ]);
                        })
                            ->where(function ($query) use ($locationId): void {
                                $query->where(function ($query) use ($locationId): void {
                                    $query->where('source_location_id', $locationId);
                                })
                                    ->orWhere(function ($query) use ($locationId): void {
                                        $query->where('transit_location_id', $locationId)
                                            ->where('status', StatusTypes::TRANSIT_OUT->value);
                                    });
                            });
                    });
                }
            });
    }

    private function getWarehouseManagerStatusCount(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Builder {
        $locationQuery = function ($query) use ($locationId, $transferType): void {
            $query->where(function ($query) use ($locationId, $transferType): void {
                $query->where('source_location_id', $locationId)
                    ->whereIntegerInRaw('transfer_type', $transferType);
            })
                ->orWhere(function ($query) use ($locationId, $transferType): void {
                    $query->where('destination_location_id', $locationId)
                        ->whereIntegerInRaw('transfer_type', $transferType);
                });
        };

        return StockTransfer::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereIntegerInRaw('transfer_type', $transferType)
            ->where('company_id', $companyId)
            ->where(function ($query) use ($locationId, $locationQuery): void {
                $query->where(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->whereNot('status', StatusTypes::DRAFT->value)
                        ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                });
            })
            ->groupBy('status')
            ->when($filterData['search_text'], $this->searchTextFilter($filterData))
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('source_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    });
                });
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData, $locationId): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value) {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->where('destination_location_id', $locationId);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_OUT->value) {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->where('source_location_id', $locationId);
                }
            })
            ->when($filterData['stock_transfer_date'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', CommonFunctions::addStartTime($filterData['stock_transfer_date'][0]))
                    ->where('updated_at', '<=', CommonFunctions::addEndTime($filterData['stock_transfer_date'][1]));
            })
            ->when($filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            });
    }

    private function getWarehouseManagerTransferOrRequestOrderStatusCount(
        array $transferType,
        array $filterData,
        int $companyId,
        int $locationId,
    ): Builder {
        $locationQuery = function ($query) use ($locationId, $transferType): void {
            $query->where(function ($query) use ($locationId, $transferType): void {
                $query->where('source_location_id', $locationId)
                    ->whereIntegerInRaw('transfer_type', $transferType);
            })
                ->orWhere(function ($query) use ($locationId, $transferType): void {
                    $query->where('destination_location_id', $locationId)
                        ->whereIntegerInRaw('transfer_type', $transferType);
                });
        };

        return StockTransfer::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->groupBy('status')
            ->when($filterData['search_text'], $this->searchTextFilter($filterData))
            ->where(function ($query) use ($locationQuery, $locationId): void {
                $query->where(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::DRAFT->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationId): void {
                    $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where(function ($query) use ($locationId): void {
                            $query->where(function ($query) use ($locationId): void {
                                $query->where('source_location_id', $locationId)
                                    ->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value);
                            })
                                ->orWhere(function ($query) use ($locationId): void {
                                    $query->where('destination_location_id', $locationId)
                                        ->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value);
                                });
                        });
                })->orWhere(function ($query) use ($locationQuery): void {
                    $query->whereNot('status', StatusTypes::DRAFT->value)
                        ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                        ->where($locationQuery);
                });
            })
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('source_location_id', $filterData['location_id']);
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('destination_location_id', $filterData['location_id']);
                    });
                });
            })
            ->when($filterData['transfer_type'], function ($query) use ($filterData, $locationId): void {
                if ((int) $filterData['transfer_type'] === TransferTypes::REQUEST_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_ORDER->value) {
                    $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                        ->whereIntegerInRaw('status', [
                            StatusTypes::DRAFT->value,
                            StatusTypes::SYSTEM_GENERATED->value,
                            StatusTypes::OPEN->value,
                            StatusTypes::CANCELLED->value,
                            StatusTypes::REJECTED->value,
                            StatusTypes::APPROVED->value,
                        ]);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_IN->value) {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->where('destination_location_id', $locationId);
                }

                if ((int) $filterData['transfer_type'] === TransferTypes::TRANSFER_OUT->value) {
                    $query->whereNotIn('status', [
                        StatusTypes::DRAFT->value,
                        StatusTypes::SYSTEM_GENERATED->value,
                        StatusTypes::OPEN->value,
                        StatusTypes::CANCELLED->value,
                        StatusTypes::REJECTED->value,
                        StatusTypes::APPROVED->value,
                    ])
                        ->where('source_location_id', $locationId);
                }
            })
            ->when($filterData['stock_transfer_date'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', CommonFunctions::addStartTime($filterData['stock_transfer_date'][0]))
                    ->where('updated_at', '<=', CommonFunctions::addEndTime($filterData['stock_transfer_date'][1]));
            })
            ->when($filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            });
    }

    private function getLocationColumnNameForPrint(): string
    {
        return 'id,name,phone,fax,address_line_1,address_line_2,city_id';
    }

    private function requestedByColumnName(): string
    {
        return 'id,employee_id,username';
    }

    private function searchTextFilter(array $filterData): Closure
    {
        return function ($query) use ($filterData): void {
            $query->where(function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('status', StatusTypes::getMatchingCases($filterData['search_text']))
                    ->orWhereHas('sourceLocation', $this->searchLocationByName($filterData['search_text']))
                    ->orWhereHas('destinationLocation', $this->searchLocationByName($filterData['search_text']))
                    ->orWhereAny(
                        [
                            'reference_number',
                            'transfer_out_number',
                            'id',
                            'transfer_in_number',
                            'request_order_number',
                            'transfer_order_number',
                        ],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    );
            });
        };
    }

    public function statusFilter(): Closure
    {
        return fn ($query) => $query->whereNotNull('received_at')
            ->whereIntegerInRaw(
                'status',
                [StatusTypes::RECEIVED->value, StatusTypes::DISCREPANCY->value, StatusTypes::CLOSED->value]
            );
    }

    public function filterByDateType(array $filterData): Closure
    {
        return fn ($query) => $query
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::CREATED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::REJECTED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('rejected_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('rejected_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::CANCELLED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('cancelled_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('cancelled_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::CLOSED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('closed_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('closed_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::DISCREPANCY_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('discrepancy_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('discrepancy_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::SYSTEM_RECEIVED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('received_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('received_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::MANUAL_RECEIVED_DATE->value,
                function ($query) use ($filterData): void {
                    $query->where('received_date', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('received_date', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::SHIPPED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('shipped_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('shipped_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::APPROVED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('approved_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('approved_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::OPENED_AT->value,
                function ($query) use ($filterData): void {
                    $query->where('opened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('opened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::REQUIRE_DATE->value,
                function ($query) use ($filterData): void {
                    $query->where('require_date', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('require_date', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            )
            ->when(
                (int) $filterData['date_type'] === StockTransferCustomReportDateTypes::TRANSFER_DATE->value,
                function ($query) use ($filterData): void {
                    $query->where('transfer_date', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('transfer_date', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                }
            );
    }

    public function updateAverageLeadyDay(int $stockTransferAverageLeadDaysId, StockTransfer $stockTransfer): void
    {
        $stockTransfer->stock_transfer_average_lead_day_id = $stockTransferAverageLeadDaysId;
        $stockTransfer->save();
    }

    public function getStockTransferByStatusSummary(array $filterData, int $companyId): Collection
    {
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return StockTransfer::select(
            'id',
            'company_id',
            'destination_location_id',
            'transfer_out_number',
            'transfer_in_number',
            'request_order_number',
            'transfer_order_number',
            'status',
            'created_at',
            'updated_at'
        )
            ->where('company_id', $companyId)
            ->with([
                'transactions:' . $stockTransferTransactionQueries->getBasicColumns(),
                'destinationLocation:' . $locationQueries->getNameColumnName(),
            ])
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addStartTime($filterData['date_range'][1]))
            ->when(isset($filterData['location_ids']), function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('source_location_id', $filterData['location_ids']);
                });
            })->get();
    }

    public function getSuccessRatio(array $validationData): float
    {
        $successTransferCount = $this->getSuccessCount($validationData);
        $totalTransferCount = $this->getTotalCount($validationData);

        if (0 === $totalTransferCount) {
            return 0.0;
        }

        return round(($successTransferCount / $totalTransferCount) * 100, 2);
    }

    private function getSuccessCount(array $validationData): int
    {
        return StockTransfer::where([
            'source_location_id' => $validationData['source_location_id'],
            'destination_location_id' => $validationData['destination_location_id'],
        ])
            ->where('company_id', session('admin_company_id'))
            ->where('is_transit_target_achieved', 1)
            ->count();
    }

    private function getTotalCount(array $validationData): int
    {
        return StockTransfer::where([
            'source_location_id' => $validationData['source_location_id'],
            'destination_location_id' => $validationData['destination_location_id'],
        ])
            ->where('company_id', session('admin_company_id'))
            ->whereNotNull('is_transit_target_achieved')
            ->count();
    }
}
