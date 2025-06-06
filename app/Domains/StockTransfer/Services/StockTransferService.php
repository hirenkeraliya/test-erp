<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\StockTransferInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StockTransfer\DataObjects\StockTransferData;
use App\Domains\StockTransfer\DataObjects\StockTransferRequestOrderData;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferAverageLeadDays\StockTransferAverageLeadDaysQueries;
use App\Domains\StockTransferItem\Enums\StockTransferDiscrepancyTypes;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\StockTransferItemBatch\StockTransferItemBatchQueries;
use App\Domains\StockTransferItemTransaction\StockTransferItemTransactionQueries;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\StockTransferTransaction\StockTransferTransactionQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sequence;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StoreManager;
use App\Models\UnitOfMeasureDerivative;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class StockTransferService
{
    public function moveSourceLocationStockInTransit(
        Collection $products,
        Collection $sourceInventories,
        StockTransfer $stockTransfer,
        User $user,
    ): void {
        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        foreach ($stockTransfer->getItems() as $stockTransferItem) {
            $product = $products->firstWhere('id', $stockTransferItem->product_id);
            $inventory = $sourceInventories->firstWhere('product_id', $stockTransferItem->product_id);
            if ($product instanceof Product && $product->has_batch) {
                foreach ($stockTransferItem->batches as $stockTransferItemBatch) {
                    $stockTransferInventoryService->updateInventoryUnits(
                        $inventory,
                        $product,
                        $stockTransfer->source_location_id,
                        $stockTransferItem,
                        $user,
                        (float) $stockTransferItemBatch->quantity,
                        $stockTransferItemBatch->batch_id
                    );
                }

                continue;
            }

            $stockTransferInventoryService->removeReservationStock($stockTransferItem, $user);
            $stockTransferInventoryService->addTransitStock(
                $stockTransfer->destination_location_id,
                $stockTransferItem
            );
        }
    }

    public function reserveStockTransferItemStocks(
        Collection $products,
        Collection $sourceInventories,
        StockTransfer $stockTransfer,
    ): void {
        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);

        foreach ($stockTransfer->getItems() as $stockTransferItem) {
            $product = $products->firstWhere('id', $stockTransferItem->product_id);
            $inventory = $sourceInventories->firstWhere('product_id', $stockTransferItem->product_id);
            if (config(
                'app.product_variant'
            ) && ($product instanceof Product && $product->masterProduct && $product->masterProduct->has_batch)) {
                continue;
            }

            if ($product instanceof Product && $product->has_batch) {
                continue;
            }

            $stockTransferInventoryService->updateInventoryUnitsWithReserved(
                $inventory,
                $product,
                $stockTransferItem,
                (float) $stockTransferItem->quantity
            );
        }

        $stockTransferQueries->setUpdatedAt($stockTransfer);
    }

    public function closeTransfer(StockTransfer $stockTransfer, User $user, int $companyId, int $oldStatus): void
    {
        $this->updateDestinationInventory($stockTransfer, $user);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);

        $this->addNotification($companyId, $stockTransfer, $user, 'source', 'closed');

        $closeStatus = StatusTypes::CLOSED->value;
        $stockTransferQueries->updateStatus($stockTransfer, $closeStatus);
        $stockTransferTransactionQueries->addNew($stockTransfer->id, $oldStatus, $closeStatus, $user);
    }

    public function updateDiscrepancySourceInventory(
        StockTransfer $stockTransfer,
        array $validatedData,
        User $user,
        int $companyId,
        Collection $products,
        Collection $batches,
    ): void {
        if ($this->hasNegativeDiscrepancy($stockTransfer)) {
            $this->revertSourceInventory($stockTransfer, $validatedData, $user, $products);
        }

        if ($this->hasPositiveDiscrepancy($stockTransfer)) {
            $this->updateExtraDiscrepancyDetails($stockTransfer, $validatedData, $user, $companyId, $products);
        }

        if ($this->hasAdditionalItems($stockTransfer)) {
            $this->updateAdditionalItemsSourceInventory($stockTransfer, $user, $products, $batches, $validatedData);
        }
    }

    /**
     * @return mixed[]
     */
    public function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $stores = $locationQueries->getStoreWithBasicColumns($companyId);
        $warehouses = $locationQueries->getWithBasicColumnsOfWarehouse($companyId);

        return [$stores, $warehouses];
    }

    /**
     * @return mixed[]
     */
    public function prepareActiveBatchesProductsAndInventories(
        array $productIds,
        int $companyId,
        int $sourceLocationId,
    ): array {
        $products = $this->fetchProducts($productIds, $companyId);

        $batches = $this->fetchBatches($products, $companyId);

        if (config('app.product_variant')) {
            $derivatives = $this->fetchDerivatives(
                $products->pluck('masterProduct.unit_of_measure_id')->unique()->filter()->toArray()
            );
        } else {
            $derivatives = $this->fetchDerivatives(
                $products->pluck('unit_of_measure_id')->unique()->filter()->toArray()
            );
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventories = $inventoryQueries->getByProductIdsAndLocationWithInventoryUnits(
            $sourceLocationId,
            $productIds
        );

        return [$products, $batches, $inventories, $derivatives];
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareStockTransferDetails(
        StockTransferData $stockTransferData,
        int $companyId,
        Admin|StoreManager|WarehouseManager $user,
        int $transferType,
        ?Sequence $sequence,
        ?int $locationId = null,
    ): array {
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);
        $stockTransferAverageLeadDaysId = $stockTransferAverageLeadDaysQueries->getIdByLocation(
            $stockTransferData->source_location_id,
            $stockTransferData->destination_location_id
        );

        return [
            'company_id' => $companyId,
            'stock_transfer_average_lead_day_id' => $stockTransferAverageLeadDaysId,
            'source_location_id' => $stockTransferData->source_location_id,
            'destination_location_id' => $stockTransferData->destination_location_id,
            'transfer_date' => $stockTransferData->transfer_date,
            'require_date' => $stockTransferData->require_date,
            'average_days' => $stockTransferData->aggregate_average_days,
            'attention' => $stockTransferData->attention,
            'requested_by_type' => ModelMapping::getCaseName($user::class),
            'requested_by_id' => $user->id,
            'reference_number' => $stockTransferData->reference_number,
            'remarks' => $stockTransferData->remarks,
            'stock_transfer_reason_id' => $stockTransferData->stock_transfer_reason_id,
            'status' => StatusTypes::DRAFT->value,
            'created_by_location_id' => $locationId,
            'transfer_type' => $transferType,
            'request_order_number' => $transferType === SequenceTypes::RO->value && $sequence instanceof Sequence ?
                $sequence->getCompleteNumber() :
                null,
            'transfer_order_number' => $transferType === SequenceTypes::TO->value && $sequence instanceof Sequence ?
                $sequence->getCompleteNumber() :
                null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareStockTransferDetailsForUpdate(StockTransferData $stockTransferData): array
    {
        return [
            'source_location_id' => $stockTransferData->source_location_id,
            'destination_location_id' => $stockTransferData->destination_location_id,
            'transfer_date' => $stockTransferData->transfer_date,
            'require_date' => $stockTransferData->require_date,
            'attention' => $stockTransferData->attention,
            'reference_number' => $stockTransferData->reference_number,
            'remarks' => $stockTransferData->remarks,
            'stock_transfer_reason_id' => $stockTransferData->stock_transfer_reason_id,
        ];
    }

    public function saveStockTransferItems(
        StockTransferData|StockTransferRequestOrderData $stockTransferData,
        int $stockTransferId,
        User $user,
        int $status,
        Collection $derivatives,
    ): void {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        foreach ($stockTransferData->transfer_items as $item) {
            $records = [
                'stock_transfer_id' => $stockTransferId,
                'product_id' => $item['product_id'],
                'quantity' => $item['transfer_stock'],
            ];

            if ($this->derivativeExist($item)) {
                $derivative = $derivatives->firstWhere('id', $item['unit_of_measure_derivative_id']);
                $records['unit_of_measure_derivative_id'] = $item['unit_of_measure_derivative_id'];
                $records['derivative_ratio'] = $derivative->ratio;
            }

            $stockTransferItem = $stockTransferItemQueries->addNew($records);

            if (! array_key_exists('remarks', $item)) {
                continue;
            }

            if (! $item['remarks']) {
                continue;
            }

            $stockTransferItemTransactionQueries->addNew($stockTransferItem->id, $item['remarks'], $status, $user);
        }
    }

    /**
     * @return mixed[]
     */
    public function getStocks(StockTransfer $stockTransfer): array
    {
        $inventoryQueries = resolve(InventoryQueries::class);

        $stockTransferItems = collect($stockTransfer->getItems());

        $sourceInventories = $inventoryQueries->getInventoriesByProductIds(
            $stockTransfer->getSourceLocationId(),
            $stockTransferItems->pluck('product_id')->toArray()
        );
        $destinationInventories = $inventoryQueries->getInventoriesByProductIds(
            $stockTransfer->getDestinationLocationId(),
            $stockTransferItems->pluck('product_id')->toArray()
        );

        return [$sourceInventories, $destinationInventories];
    }

    /**
     * @return mixed[]
     */
    public function fetchProductsAndSourceInventories(StockTransfer $stockTransfer, int $companyId): array
    {
        [$products, $batches, $derivatives] = $this->fetchProductsBatchesAndDerivatives($stockTransfer, $companyId);

        $productIds = $stockTransfer->items->pluck('product_id')->unique()->filter()->toArray();

        $inventoryQueries = resolve(InventoryQueries::class);
        $sourceInventories = $inventoryQueries->getByProductIdsAndLocationWithInventoryUnits(
            $stockTransfer->getSourceLocationId(),
            $productIds
        );

        return [$products, $sourceInventories, $batches];
    }

    /**
     * @return mixed[]
     */
    public function fetchProductsBatchesAndDerivatives(StockTransfer $stockTransfer, int $companyId): array
    {
        $productIds = $stockTransfer->items->pluck('product_id')->unique()->filter()->toArray();

        $products = $this->fetchProducts($productIds, $companyId);

        $batches = $this->fetchBatches($products, $companyId);

        if (config('app.product_variant')) {
            $derivatives = $this->fetchDerivatives(
                $products->pluck('masterProduct.unit_of_measure_id')->unique()->filter()->toArray()
            );
        } else {
            $derivatives = $this->fetchDerivatives(
                $products->pluck('unit_of_measure_id')->unique()->filter()->toArray()
            );
        }

        return [$products, $batches, $derivatives];
    }

    /**
     * @return mixed[]
     */
    public function fetchProductsWithArchivedAndSourceInventories(StockTransfer $stockTransfer, int $companyId): array
    {
        $productIds = $stockTransfer->items->pluck('product_id')->toArray();

        $inventoryQueries = resolve(InventoryQueries::class);
        $products = $this->fetchProductsWithArchived($productIds, $companyId);

        $sourceInventories = $inventoryQueries->getByProductIdsAndLocationWithInventoryUnits(
            $stockTransfer->getSourceLocationId(),
            $productIds
        );

        $batches = $this->fetchBatches($products, $companyId);

        return [$products, $sourceInventories, $batches];
    }

    /**
     * @return int[]
     */
    public function prepareLocationIdAndTransferType(StockTransferData $stockTransferData): array
    {
        if (Str::lower(StockTransferTypes::REQUEST_ORDER->name) === $stockTransferData->transfer_type) {
            $transferType = SequenceTypes::RO->value;
            $locationId = $stockTransferData->destination_location_id;

            return [$transferType, $locationId];
        }

        $transferType = SequenceTypes::TO->value;
        $locationId = $stockTransferData->source_location_id;

        return [$transferType, $locationId];
    }

    public function saveStockTransferItemAndBatchRecords(
        StockTransferData $stockTransferData,
        int $stockTransferId,
        Collection $products,
        int $companyId,
        User $user,
        int $status,
        Collection $derivatives,
    ): void {
        $batches = $this->fetchBatches($products, $companyId);

        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);

        foreach ($stockTransferData->transfer_items as $item) {
            $records = [
                'stock_transfer_id' => $stockTransferId,
                'product_id' => $item['product_id'],
                'package_type_id' => $item['package_type_id'] ?? null,
                'package_quantity' => $item['package_quantity'] ?? null,
                'package_total_quantity' => $item['package_total_quantity'] ?? null,
                'quantity' => $item['transfer_stock'],
            ];

            $derivative = null;

            if ($this->derivativeExist($item)) {
                $derivative = $derivatives->firstWhere('id', $item['unit_of_measure_derivative_id']);
            }

            if ($derivative) {
                $records['unit_of_measure_derivative_id'] = $item['unit_of_measure_derivative_id'];
                $records['derivative_ratio'] = $derivative->ratio;
            }

            $stockTransferItem = $stockTransferItemQueries->addNew($records);

            if (array_key_exists('remarks', $item) && $item['remarks']) {
                $stockTransferItemTransactionQueries->addNew($stockTransferItem->id, $item['remarks'], $status, $user);
            }

            if (! array_key_exists('has_batch', $item)) {
                continue;
            }

            if (false === $item['has_batch']) {
                continue;
            }

            foreach ($item['batch_details'] as $batchDetails) {
                $batch = $batches->firstWhere('number', $batchDetails['batch_number']);

                $stockTransferItemBatchQueries->addNew([
                    'stock_transfer_item_id' => $stockTransferItem->getKey(),
                    'batch_id' => $batch->id,
                    'quantity' => $derivative ? CommonFunctions::numberFormat(
                        (float) $batchDetails['quantity'] / (float) $derivative->ratio
                    ) : $batchDetails['quantity'],
                ]);
            }
        }
    }

    public function addNotification(
        int $companyId,
        StockTransfer $stockTransfer,
        Admin|User $sourceAdmin,
        string $notificationSendTo,
        string $status,
    ): void {
        $notificationQueries = resolve(NotificationQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferNumber = $stockTransfer->transfer_order_number ?? $stockTransfer->request_order_number;

        /** @var Location $sourceLocation */
        $sourceLocation = $stockTransfer->sourceLocation;

        if (LocationTypes::getCaseNameByValue(
            $sourceLocation->type_id
        ) === LocationTypes::WAREHOUSE->name && 'source' === $notificationSendTo) {
            $stockTransfer = $stockTransferQueries->loadSourceLocationWarehouseAndWarehouseManagers($stockTransfer);

            /** @var Collection $warehouseManagers */
            $warehouseManagers = $sourceLocation->warehouseManagers;

            $route = route('warehouse_manager.stock_transfers.index', [
                'stock_transfer_number' => $stockTransferNumber,
            ]);

            $message = 'Stock Transfer Number:<a href=' . $route . ' class="text-primary underline">' . $stockTransferNumber . '</a> is ' . $status;
            $textMessage = 'Stock Transfer Number: ' . $stockTransferNumber . ' is ' . $status;

            $payload = [
                'id' => $stockTransfer->id,
                'type' => ModelMapping::STOCK_TRANSFER->name,
                'stock_transfer_number' => $stockTransferNumber,
            ];

            foreach ($warehouseManagers as $warehouseManager) {
                $notificationQueries->addNew(
                    $companyId,
                    ModelMapping::getCaseName($sourceAdmin::class),
                    $sourceAdmin->getKey(),
                    ModelMapping::WAREHOUSE_MANAGER->name,
                    $warehouseManager->getKey(),
                    $message,
                    null,
                    $textMessage,
                    $payload,
                );
            }
        }

        /** @var Location $destinationLocation */
        $destinationLocation = $stockTransfer->destinationLocation;

        if (LocationTypes::getCaseNameByValue(
            $destinationLocation->type_id
        ) === LocationTypes::WAREHOUSE->name && 'destination' === $notificationSendTo) {
            $stockTransfer = $stockTransferQueries->loadDestinationLocationWarehouseAndWarehouseManagers(
                $stockTransfer
            );

            /** @var Collection $warehouseManagers */
            $warehouseManagers = $destinationLocation->warehouseManagers;

            $route = route('warehouse_manager.stock_transfers.index', [
                'stock_transfer_number' => $stockTransferNumber,
            ]);

            $message = 'Stock Transfer Number:<a href=' . $route . ' class="text-primary underline">' . $stockTransferNumber . '</a> is ' . $status;
            $textMessage = 'Stock Transfer Number: ' . $stockTransferNumber . ' is ' . $status;

            $payload = [
                'stock_transfer_number' => $stockTransferNumber,
                'type' => ModelMapping::STOCK_TRANSFER->name,
                'id' => $stockTransfer->id,
            ];

            foreach ($warehouseManagers as $warehouseManager) {
                $notificationQueries->addNew(
                    $companyId,
                    ModelMapping::getCaseName($sourceAdmin::class),
                    $sourceAdmin->getKey(),
                    ModelMapping::WAREHOUSE_MANAGER->name,
                    $warehouseManager->getKey(),
                    $message,
                    null,
                    $textMessage,
                    $payload,
                );
            }
        }

        /** @var Location $sourceLocation */
        $sourceLocation = $stockTransfer->sourceLocation;

        if (LocationTypes::getCaseNameByValue(
            $sourceLocation->type_id
        ) === LocationTypes::STORE->name && 'source' === $notificationSendTo) {
            $stockTransfer = $stockTransferQueries->loadSourceLocationStoreAndStoreManagers($stockTransfer);

            /** @var Collection $storeManagers */
            $storeManagers = $sourceLocation->storeManagers;

            $route = route('store_manager.stock_transfers.index', [
                'stock_transfer_number' => $stockTransferNumber,
            ]);

            $message = 'Stock Transfer Number:<a href=' . $route . ' class="text-primary underline">' . $stockTransferNumber . '</a> is ' . $status;
            $textMessage = 'Stock Transfer Number: ' . $stockTransferNumber . ' is ' . $status;

            $payload = [
                'stock_transfer_number' => $stockTransferNumber,
                'type' => ModelMapping::STOCK_TRANSFER->name,
                'id' => $stockTransfer->id,
            ];

            foreach ($storeManagers as $storeManager) {
                $notificationQueries->addNew(
                    $companyId,
                    ModelMapping::getCaseName($sourceAdmin::class),
                    $sourceAdmin->getKey(),
                    ModelMapping::STORE_MANAGER->name,
                    $storeManager->getKey(),
                    $message,
                    null,
                    $textMessage,
                    $payload,
                );
            }
        }

        /** @var Location $destinationLocation */
        $destinationLocation = $stockTransfer->destinationLocation;

        if (LocationTypes::getCaseNameByValue(
            $destinationLocation->type_id
        ) === LocationTypes::STORE->name && 'destination' === $notificationSendTo) {
            $stockTransfer = $stockTransferQueries->loadDestinationLocationStoreAndStoreManagers($stockTransfer);

            /** @var Collection $storeManagers */
            $storeManagers = $destinationLocation->storeManagers;

            $route = route('store_manager.stock_transfers.index', [
                'stock_transfer_number' => $stockTransferNumber,
            ]);

            $message = 'Stock Transfer Number:<a href=' . $route . ' class="text-primary underline">' . $stockTransferNumber . '</a> is ' . $status;
            $textMessage = 'Stock Transfer Number: ' . $stockTransferNumber . ' is ' . $status;

            $payload = [
                'stock_transfer_number' => $stockTransferNumber,
                'type' => ModelMapping::STOCK_TRANSFER->name,
                'id' => $stockTransfer->id,
            ];

            foreach ($storeManagers as $storeManager) {
                $notificationQueries->addNew(
                    $companyId,
                    ModelMapping::getCaseName($sourceAdmin::class),
                    $sourceAdmin->getKey(),
                    ModelMapping::STORE_MANAGER->name,
                    $storeManager->getKey(),
                    $message,
                    null,
                    $textMessage,
                    $payload,
                );
            }
        }

        $stockTransfer->refresh();

        /** @var Location $transitLocation */
        $transitLocation = $stockTransfer->transitLocation;

        if ($stockTransfer->transitLocation && LocationTypes::getCaseNameByValue(
            $transitLocation->type_id
        ) === LocationTypes::WAREHOUSE->name) {
            $stockTransfer = $stockTransferQueries->loadTransitLocationWarehouseAndWarehouseManagers($stockTransfer);

            /** @var Collection $warehouseManagers */
            $warehouseManagers = $transitLocation->warehouseManagers;

            $route = route('warehouse_manager.stock_transfers.index', [
                'stock_transfer_number' => $stockTransferNumber,
                'stock_transfer_id' => $stockTransfer->id,
            ]);

            $message = 'Stock Transfer Number:<a href=' . $route . ' class="text-primary underline">' . $stockTransferNumber . '</a> is ' . $status;
            $textMessage = 'Stock Transfer Number: ' . $stockTransferNumber . ' is ' . $status;

            $payload = [
                'stock_transfer_number' => $stockTransferNumber,
                'type' => ModelMapping::STOCK_TRANSFER->name,
                'id' => $stockTransfer->id,
            ];

            foreach ($warehouseManagers as $warehouseManager) {
                $notificationQueries->addNew(
                    $companyId,
                    ModelMapping::getCaseName($sourceAdmin::class),
                    $sourceAdmin->getKey(),
                    ModelMapping::WAREHOUSE_MANAGER->name,
                    $warehouseManager->getKey(),
                    $message,
                    null,
                    $textMessage,
                    $payload,
                );
            }
        }

        if ($stockTransfer->transitLocation && LocationTypes::getCaseNameByValue(
            $transitLocation->type_id
        ) === LocationTypes::STORE->name) {
            $stockTransfer = $stockTransferQueries->loadTransitLocationStoreAndStoreManagers($stockTransfer);

            /** @var Collection $storeManagers */
            $storeManagers = $transitLocation->storeManagers;

            $route = route('store_manager.stock_transfers.index', [
                'stock_transfer_number' => $stockTransferNumber,
            ]);

            $message = 'Stock Transfer Number:<a href=' . $route . ' class="text-primary underline">' . $stockTransferNumber . '</a> is ' . $status;
            $textMessage = 'Stock Transfer Number: ' . $stockTransferNumber . ' is ' . $status;

            $payload = [
                'stock_transfer_number' => $stockTransferNumber,
                'type' => ModelMapping::STOCK_TRANSFER->name,
                'id' => $stockTransfer->id,
            ];

            foreach ($storeManagers as $storeManager) {
                $notificationQueries->addNew(
                    $companyId,
                    ModelMapping::getCaseName($sourceAdmin::class),
                    $sourceAdmin->getKey(),
                    ModelMapping::STORE_MANAGER->name,
                    $storeManager->getKey(),
                    $message,
                    null,
                    $textMessage,
                    $payload,
                );
            }
        }
    }

    public function getTransferType(StockTransfer $stockTransfer, array $filterData): string
    {
        $transferType = StockTransferTypes::getFormattedCaseName($stockTransfer->transfer_type);

        if ($this->isStatusNotApproved($stockTransfer)) {
            $transferType = ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value)
                ? 'Transfer Order'
                : 'Request Order';
        } elseif ($this->destinationIsSelectedStore($stockTransfer, $filterData)) {
            $transferType = 'Transfer In';
        } elseif ($this->sourceIsSelectedStore($stockTransfer, $filterData)) {
            $transferType = 'Transfer Out';
        }

        return $transferType;
    }

    public function getTransferTypesByLocation(StockTransfer $stockTransfer, int $locationId): string
    {
        $transferType = StockTransferTypes::getFormattedCaseName($stockTransfer->transfer_type);

        if ($this->isStatusNotApproved($stockTransfer)) {
            $transferType = ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value)
                ? 'Transfer Order'
                : 'Request Order';
        } elseif ($this->destinationForSelectedLocation($stockTransfer, $locationId)) {
            $transferType = 'Transfer In';
        } elseif ($this->sourceForSelectedLocation($stockTransfer, $locationId)) {
            $transferType = 'Transfer Out';
        }

        return $transferType;
    }

    public function getStockTransferNumber(StockTransfer $stockTransfer, array $filterData): ?string
    {
        $transferType = StockTransferTypes::getFormattedCaseName($stockTransfer->transfer_type);

        if ($this->isStatusNotApproved($stockTransfer)) {
            $transferType = ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value)
                ? $stockTransfer->transfer_order_number
                : $stockTransfer->request_order_number;
        } elseif ($this->destinationIsSelectedStore($stockTransfer, $filterData)) {
            $transferType = $stockTransfer->transfer_in_number;
        } elseif ($this->sourceIsSelectedStore($stockTransfer, $filterData)) {
            $transferType = $stockTransfer->transfer_out_number;
        }

        return $transferType;
    }

    public function getStockTransferNumberForSelectedLocation(
        StockTransfer $stockTransfer,
        int $locationId,
    ): ?string {
        $transferType = StockTransferTypes::getFormattedCaseName($stockTransfer->transfer_type);

        if ($this->isStatusNotApproved($stockTransfer)) {
            $transferType = ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value)
                ? $stockTransfer->transfer_order_number
                : $stockTransfer->request_order_number;
        } elseif ($this->destinationForSelectedLocation($stockTransfer, $locationId)) {
            $transferType = $stockTransfer->transfer_in_number;
        } elseif ($this->sourceForSelectedLocation($stockTransfer, $locationId)) {
            $transferType = $stockTransfer->transfer_out_number;
        }

        return $transferType;
    }

    public function markAsOpen(int $stockTransferId, int $companyId, int $statusId, User $user): void
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfer = $stockTransferQueries->getByIdWithItemsAndBatches($stockTransferId, $companyId);

        if (
            $stockTransfer->getStatus() !== StatusTypes::DRAFT->value
            && $stockTransfer->getStatus() !== StatusTypes::SYSTEM_GENERATED->value
        ) {
            throw new RedirectBackWithErrorException(
                'The stock transfer status can be changed only from draft to open.'
            );
        }

        $productIds = $stockTransfer->items->pluck('product_id')->unique()->filter()->toArray();
        $products = $this->fetchProducts($productIds, $companyId);

        $inventoryQueries = resolve(InventoryQueries::class);
        $sourceInventories = $inventoryQueries->getByProductIdsAndLocationWithInventoryUnits(
            $stockTransfer->source_location_id,
            $productIds
        );

        if ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value) {
            $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);
            $stockTransferCheckRequestService->validateItemBatchesQuantityWithInventoryUnit(
                $stockTransfer,
                $products,
                $sourceInventories
            );
        }

        DB::beginTransaction();

        try {
            $notificationSendTo = 'destination';
            if ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value) {
                $this->moveSourceLocationStockInTransit($products, $sourceInventories, $stockTransfer, $user);
                $notificationSendTo = 'destination';
            }

            if ($stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value) {
                $notificationSendTo = 'source';
            }

            $this->addNotification($companyId, $stockTransfer, $user, $notificationSendTo, 'opened');

            $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);

            $stockTransferTransactionQueries->addNew(
                $stockTransfer->id,
                $stockTransfer->getStatus(),
                $statusId,
                $user
            );

            $stockTransferQueries->updateStatus($stockTransfer, $statusId);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Transfer-Order-open', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function removeAdditionalItem(int $stockTransferItemId): void
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);

        $stockTransferItemQueries->removeAdditionalItemAndRelations($stockTransferItemId);
    }

    public function markAsCancelled(
        int $stockTransferId,
        int $companyId,
        int $statusId,
        User $user,
        ?string $remarks = null,
    ): void {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
        $stockTransfer = $stockTransferQueries->getByIdWithItemsBatchesAndUnits($stockTransferId, $companyId);
        $openStatus = StatusTypes::OPEN->value;
        $shippedStatus = StatusTypes::SHIPPED->value;

        if ($stockTransfer->getStatus() === StatusTypes::DRAFT->value || $stockTransfer->getStatus() === StatusTypes::SYSTEM_GENERATED->value) {
            DB::beginTransaction();

            try {
                $this->revertReservedStocks($stockTransfer);
                $stockTransferTransactionQueries->addNew(
                    $stockTransfer->id,
                    $stockTransfer->getStatus(),
                    $statusId,
                    $user,
                    $remarks
                );
                $stockTransferQueries->updateStatus($stockTransfer, $statusId);

                DB::commit();
            } catch (Throwable $throwable) {
                Log::error('Transfer-Order-Cancelled', [
                    'error_message' => $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);
                DB::rollBack();

                abort(417, 'An error occurred. Please try again.');
            }

            return;
        }

        if (
            $stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value &&
            $stockTransfer->getStatus() === $openStatus
        ) {
            DB::beginTransaction();

            try {
                $stockTransferQueries->updateStatus($stockTransfer, $statusId);
                $stockTransferTransactionQueries->addNew($stockTransfer->id, $openStatus, $statusId, $user, $remarks);

                $this->revertReservedStocks($stockTransfer);

                $this->addNotification($companyId, $stockTransfer, $user, 'source', 'cancelled');

                DB::commit();
            } catch (Throwable $throwable) {
                Log::error('Request-Order-Cancelled', [
                    'error_message' => $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);
                DB::rollBack();

                abort(417, 'An error occurred. Please try again.');
            }

            return;
        }

        if (
            $stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value &&
            $stockTransfer->getStatus() === $openStatus
        ) {
            $this->revertBackInventory(
                $stockTransfer,
                $user,
                $companyId,
                $openStatus,
                $statusId,
                'cancelled',
                'destination',
                $remarks
            );
        }

        if ($stockTransfer->getStatus() !== $shippedStatus) {
            return;
        }

        $this->revertBackInventory(
            $stockTransfer,
            $user,
            $companyId,
            $shippedStatus,
            $statusId,
            'cancelled',
            'source',
            $remarks
        );
    }

    public function revertBackInventory(
        StockTransfer $stockTransfer,
        User $user,
        int $companyId,
        int $oldStatus,
        int $newStatusId,
        string $message,
        string $notificationSendTo,
        ?string $remarks = null,
    ): void {
        DB::beginTransaction();

        try {
            $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
            $stockTransferQueries = resolve(StockTransferQueries::class);
            $transitStockQueries = resolve(TransitStockQueries::class);

            foreach ($stockTransfer->getItems() as $stockTransferItem) {
                $transitStockQueries->deleteAffectedBy($stockTransferItem->id, ModelMapping::STOCK_TRANSFER_ITEM->name);
                $this->revertSourceInventoryFor($stockTransfer, $stockTransferItem, $user);
            }

            $stockTransferQueries->updateStatus($stockTransfer, $newStatusId);
            $stockTransferTransactionQueries->addNew($stockTransfer->id, $oldStatus, $newStatusId, $user, $remarks);

            $this->addNotification($companyId, $stockTransfer, $user, $notificationSendTo, $message);

            DB::commit();
        } catch (Throwable $throwable) {
            $text = StatusTypes::getCaseName($newStatusId);
            Log::error('Stock Transfer-Mark-as-' . $text, [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsDiscrepancy(int $stockTransferId, int $companyId, int $statusId, User $user): void
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfer = $stockTransferQueries->getLocationAndStatusById($stockTransferId, $companyId);
        $receivedStatus = StatusTypes::RECEIVED->value;

        if ($stockTransfer->getStatus() !== $receivedStatus) {
            throw new RedirectBackWithErrorException(
                "The stock transfer status must be 'Received' in order to change it to 'Discrepancy'."
            );
        }

        DB::beginTransaction();

        try {
            $this->addNotification($companyId, $stockTransfer, $user, 'source', 'discrepancy');

            $stockTransferQueries->updateStatus($stockTransfer, $statusId);

            $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
            $stockTransferTransactionQueries->addNew($stockTransfer->id, $receivedStatus, $statusId, $user);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Transfer-Order-Mark-As-Discrepancy', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsShippedOrTransit(
        StockTransferShippedData $stockTransferShippedData,
        int $stockTransferId,
        int $companyId,
        User $user,
    ): void {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfer = $stockTransferQueries->getByIdWithItemsAndBatches($stockTransferId, $companyId);
        $productIds = $stockTransfer->items->pluck('product_id')->unique()->filter()->toArray();
        $products = $this->fetchProducts($productIds, $companyId);

        $inventoryQueries = resolve(InventoryQueries::class);
        $sourceInventories = $inventoryQueries->getByProductIdsAndLocationWithInventoryUnits(
            $stockTransfer->source_location_id,
            $productIds
        );

        if ($stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value) {
            $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);
            $stockTransferCheckRequestService->validateItemBatchesQuantityWithInventoryUnit(
                $stockTransfer,
                $products,
                $sourceInventories
            );
        }

        if ($stockTransfer->getStatus() !== StatusTypes::TRANSIT->value) {
            if (
                $stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value &&
                $stockTransfer->getStatus() !== StatusTypes::OPEN->value
            ) {
                throw new RedirectBackWithErrorException(
                    'The Stock Transfer status must be open in order to change it to shipped.'
                );
            }

            if (
                $stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value &&
                $stockTransfer->getStatus() !== StatusTypes::APPROVED->value
            ) {
                throw new RedirectBackWithErrorException(
                    'The Stock Transfer status must be approved in order to change it to shipped.'
                );
            }
        }

        DB::beginTransaction();

        try {
            $oldStatus = $stockTransfer->getStatus();
            $sequenceQueries = resolve(SequenceQueries::class);
            $sequenceIn = $sequenceQueries->addNew(
                $stockTransfer->destination_location_id,
                SequenceTypes::TIN->value,
            );

            $sequenceOut = $sequenceQueries->addNew(
                $stockTransfer->source_location_id,
                SequenceTypes::TOUT->value,
            );

            if ($stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value) {
                $this->moveSourceLocationStockInTransit($products, $sourceInventories, $stockTransfer, $user);
            }

            $stockTransferQueries->updateShippedAndTransferNumber(
                $stockTransferId,
                $companyId,
                $sequenceIn->getCompleteNumber(),
                $sequenceOut->getCompleteNumber(),
                $stockTransferShippedData
            );

            $newStatus = StatusTypes::SHIPPED->value;
            if ($stockTransferShippedData->shipped_type === ShippedTypes::TRANSIT->value) {
                $newStatus = StatusTypes::TRANSIT->value;
            }

            $this->addNotification(
                $companyId,
                $stockTransfer,
                $user,
                'destination',
                StatusTypes::getCaseName($newStatus)
            );

            $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);

            $stockTransferTransactionQueries->addNew($stockTransfer->id, $oldStatus, $newStatus, $user);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Transfer-Order-Ship', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsReceived(int $companyId, int $stockTransferId, string $receivedDate, User $user): void
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfer = $stockTransferQueries->getLocationAndStatusById($stockTransferId, $companyId);
        $shippedStatus = StatusTypes::SHIPPED->value;
        $transitOutStatus = StatusTypes::TRANSIT_OUT->value;
        $previousStatus = null;

        if ($stockTransfer->getStatus() === $shippedStatus) {
            $previousStatus = $shippedStatus;
        } elseif ($stockTransfer->getStatus() === $transitOutStatus) {
            $previousStatus = $transitOutStatus;
        } else {
            throw new RedirectBackWithErrorException(
                "The stock transfer status must be 'Shipped' in order to change it to 'Received'."
            );
        }

        DB::beginTransaction();

        try {
            $this->addNotification($companyId, $stockTransfer, $user, 'source', 'received');

            $stockTransferQueries->updateReceivedDateAndStatus($stockTransfer, $receivedDate);
            $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
            $stockTransferTransactionQueries->addNew(
                $stockTransfer->id,
                $previousStatus,
                StatusTypes::RECEIVED->value,
                $user
            );

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('stock-transfer-mark-as-received', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function updateShippingDetailsAndMarkAsApproved(
        StockTransfer $stockTransfer,
        Collection $validatedData,
        User $user,
        int $companyId,
        int $openStatus,
        Collection $products,
        Collection $batches,
    ): void {
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);

        foreach ($stockTransfer->getItems() as $item) {
            $product = $products->firstWhere('id', $item->product_id);
            $requestStockTransferItem = $validatedData->firstWhere('id', $item->id);

            $stockTransferItemQueries->updateShippingDetailsRecordsById(
                $requestStockTransferItem,
                $stockTransfer->id,
                $companyId
            );

            if (config('app.product_variant') && ! $product->masterProduct->has_batch) {
                continue;
            }

            if (! $product->has_batch) {
                continue;
            }

            /** @var array<array> $batchDetails */
            $batchDetails = $requestStockTransferItem['batch_details'];
            $batchDetails = collect($batchDetails)
                ->whereNotNull('batch_number')
                ->whereNotNull('quantity');

            /** @var ?UnitOfMeasureDerivative $derivative */
            $derivative = $item->unitOfMeasureDerivative;

            foreach ($batchDetails as $batchDetail) {
                /** @var Batch $batch */
                $batch = $batches->where('product_id', $product->id)
                    ->firstWhere('number', $batchDetail['batch_number']);

                $stockTransferItemBatchQueries->addNew([
                    'stock_transfer_item_id' => $item['id'],
                    'batch_id' => $batch->id,
                    'quantity' => null !== $derivative ? CommonFunctions::numberFormat(
                        (float) $batchDetail['quantity'] / (float) $derivative->ratio
                    ) : $batchDetail['quantity'],
                ]);
            }
        }

        $this->addNotification($companyId, $stockTransfer, $user, 'destination', 'approved');

        $approvedStatus = StatusTypes::APPROVED->value;

        $stockTransferQueries->updateStatus($stockTransfer, $approvedStatus);
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
        $stockTransferTransactionQueries->addNew($stockTransfer->id, $openStatus, $approvedStatus, $user);
    }

    public function updateRequestOrder(
        StockTransferRequestOrderData $stockTransferRequestOrderData,
        StockTransfer $stockTransfer,
        int $companyId,
        User $user,
        int $status,
        Collection $products,
        Collection $inventories,
        Collection $derivatives,
    ): void {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferItemQueries->deleteItemAndBatches($stockTransfer);

        $stockTransferData = [
            'attention' => $stockTransferRequestOrderData->attention,
            'reference_number' => $stockTransferRequestOrderData->reference_number,
            'remarks' => $stockTransferRequestOrderData->remarks,
        ];

        $stockTransferQueries->update($stockTransferData, $stockTransfer->id, $companyId);

        $this->saveStockTransferItems($stockTransferRequestOrderData, $stockTransfer->id, $user, $status, $derivatives);

        $stockTransfer = $stockTransferQueries->getWithItemsAndBatchDetailsById($stockTransfer->id);

        $this->reserveStockTransferItemStocks($products, $inventories, $stockTransfer);
    }

    public function closeDiscrepancy(
        StockTransfer $stockTransfer,
        array $validatedData,
        User $user,
        Collection $products,
        int $companyId,
        int $discrepancyStatus,
        Collection $batches,
    ): void {
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $this->updateDiscrepancySourceInventory($stockTransfer, $validatedData, $user, $companyId, $products, $batches);

        $stockTransfer = $stockTransferQueries->loadItemsUnitsAndBatches($stockTransfer);

        $this->updateDestinationInventory($stockTransfer, $user);

        $this->addNotification($companyId, $stockTransfer, $user, 'destination', 'closed');

        $closedStatus = StatusTypes::CLOSED->value;
        $stockTransferQueries->updateStatus($stockTransfer, $closedStatus);
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
        $stockTransferTransactionQueries->addNew($stockTransfer->id, $discrepancyStatus, $closedStatus, $user);
    }

    public function updateAdditionalItems(array $additionalItems, User $user, Collection $derivatives): void
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $additionalItems = collect($additionalItems);

        $stockTransferId = null;

        foreach ($additionalItems as $additionalItem) {
            $stockTransferId = $additionalItem['stock_transfer_id'];
            $records = [
                'stock_transfer_id' => $additionalItem['stock_transfer_id'],
                'product_id' => $additionalItem['product_id'],
                'package_type_id' => $additionalItem['package_type_id'] ?? null,
                'package_quantity' => $additionalItem['package_quantity'] ?? null,
                'package_total_quantity' => $additionalItem['package_total_quantity'] ?? null,
                'quantity' => 0,
                'received_quantity' => $additionalItem['received_quantity'],
                'is_extra_item' => true,
            ];

            $derivative = null;

            if ($this->derivativeExist($additionalItem)) {
                $derivative = $derivatives->firstWhere('id', $additionalItem['unit_of_measure_derivative_id']);
            }

            if ($derivative) {
                $records['unit_of_measure_derivative_id'] = $additionalItem['unit_of_measure_derivative_id'];
                $records['derivative_ratio'] = $derivative->ratio;
            }

            $stockTransferItem = $stockTransferItemQueries->addNew($records);
            if (! array_key_exists('remarks', $additionalItem)) {
                continue;
            }

            if (! $additionalItem['remarks']) {
                continue;
            }

            $stockTransferItemTransactionQueries->addNew(
                $stockTransferItem->id,
                $additionalItem['remarks'],
                StatusTypes::RECEIVED->value,
                $user
            );
        }

        if ($stockTransferId) {
            $stockTransferQueries->setUpdatedAtById($stockTransferId);
        }
    }

    public function requestOrderMarkAsRejected(
        StockTransfer $stockTransfer,
        int $statusId,
        int $openStatus,
        int $companyId,
        User $user,
        ?string $remarks = null,
    ): void {
        DB::beginTransaction();

        try {
            $stockTransferQueries = resolve(StockTransferQueries::class);
            $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);

            $stockTransferQueries->updateStatus($stockTransfer, $statusId);
            $stockTransferTransactionQueries->addNew($stockTransfer->id, $openStatus, $statusId, $user, $remarks);
            $this->revertReservedStocks($stockTransfer);

            $this->addNotification($companyId, $stockTransfer, $user, 'destination', 'rejected');

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Request-Order-Mark-As-rejected', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function deliveryNoteItemRemarks(User $user, ?string $remarks, int $stockTransferItemId): void
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $status = $stockTransferItemQueries->getStatusById($stockTransferItemId);

        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $stockTransferItemTransactionQueries->addNew($stockTransferItemId, $remarks, $status, $user);
    }

    public function fetchBatches(Collection $products, int $companyId): Collection
    {
        $batches = collect([]);

        if (config('app.product_variant')) {
            $batchProductIds = $products->filter(
                fn ($product) => $product->masterProduct->where('is_non_inventory', false)
            )->pluck('id')->unique()->filter()->toArray();
        } else {
            $batchProductIds =
                $products->where('is_non_inventory', false)->pluck('id')->unique()->filter()->toArray();
        }

        if ([] !== $batchProductIds) {
            $batchQueries = resolve(BatchQueries::class);
            $batches = $batchQueries->getByProductIds($batchProductIds, $companyId);
        }

        return $batches;
    }

    public function fetchProducts(array $productIds, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return $productQueries->getActiveInventoryProductsByIds($productIds, $companyId);
    }

    public function fetchDerivatives(array $unitOfMeasureIds): Collection
    {
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return $unitOfMeasureDerivativeQueries->getByUnitOfMeasureIds($unitOfMeasureIds);
    }

    public function markAsTransitIn(int $stockTransferId, int $companyId, int $statusId, User $user): void
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
        $stockTransfer = $stockTransferQueries->getStatusById($stockTransferId, $companyId);

        if ($stockTransfer->getStatus() !== StatusTypes::TRANSIT->value) {
            throw new RedirectBackWithErrorException(
                'The stock transfer status can be changed only from transit to transit IN.'
            );
        }

        $stockTransferQueries->updateStatus($stockTransfer, $statusId);
        $stockTransferTransactionQueries->addNew($stockTransfer->id, StatusTypes::TRANSIT->value, $statusId, $user);
    }

    public function markAsTransitOut(int $stockTransferId, int $companyId, int $statusId, User $user): void
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferTransactionQueries = resolve(StockTransferTransactionQueries::class);
        $stockTransfer = $stockTransferQueries->getStatusById($stockTransferId, $companyId);

        if ($stockTransfer->getStatus() !== StatusTypes::TRANSIT_IN->value) {
            throw new RedirectBackWithErrorException(
                'The stock transfer status can be changed only from transit IN to transit OUT.'
            );
        }

        $stockTransferQueries->updateStatus($stockTransfer, $statusId);
        $stockTransferTransactionQueries->addNew($stockTransfer->id, StatusTypes::TRANSIT_IN->value, $statusId, $user);
    }

    public function getAverageAggregateDays(array $validatedData): array
    {
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);
        $stockTransferAverageLeadDays = $stockTransferAverageLeadDaysQueries->getAverageAggregateDays($validatedData);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferSuccessRatio = $stockTransferQueries->getSuccessRatio($validatedData);

        return [
            'success_ratio' => $stockTransferSuccessRatio,
            'aggregate_average_days' => $stockTransferAverageLeadDays,
        ];
    }

    public function statusIsShippedOrTransit(int $status): bool
    {
        return StatusTypes::SHIPPED->value === $status ||
            StatusTypes::TRANSIT->value === $status ||
            StatusTypes::TRANSIT_IN->value === $status ||
            StatusTypes::TRANSIT_OUT->value === $status;
    }

    public function preparedShipmentProgressbar(
        int $totalTravelingDays,
        string $dateTimeParseFormat,
        string $shippedAt,
    ): array {
        $travelingAverageLeadDays = [];
        /** @var Carbon $shippedAtFormat */
        $shippedAtFormat = Carbon::createFromFormat($dateTimeParseFormat, $shippedAt);
        $actualArrivalDate = $shippedAtFormat->copy()->addDays($totalTravelingDays);

        $currentDate = Carbon::now();

        if ($currentDate > $actualArrivalDate) {
            $travelingAverageLeadDays['progress_percentage'] = 101;
            $travelingAverageLeadDays['message'] = 'Shipment running late.';
        }

        if ($currentDate < $actualArrivalDate) {
            $remainingTravelingDays = $actualArrivalDate->diffInDays($currentDate);
            $completedDays = $totalTravelingDays - $remainingTravelingDays;
            $progressPercentage = ($completedDays / $totalTravelingDays) * 100;

            $dayText = 100 === $progressPercentage ? 'today' : 'in : ' . $remainingTravelingDays . ' days.';

            $travelingAverageLeadDays['progress_percentage'] = $progressPercentage;
            $travelingAverageLeadDays['message'] = 'Shipment may reach ' . $dayText;
        }

        return $travelingAverageLeadDays;
    }

    private function revertReservedStocks(StockTransfer $stockTransfer): void
    {
        $stockTransferItems = $stockTransfer->getItems();

        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        foreach ($stockTransferItems as $stockTransferItem) {
            /** @var StockTransferItem $stockTransferItem */
            $stockTransferInventoryService->revertReservedStock($stockTransferItem);
        }
    }

    private function hasNegativeDiscrepancy(StockTransfer $stockTransfer): bool
    {
        foreach ($stockTransfer->getItems() as $stockTransferItem) {
            if ($stockTransferItem->quantity > $stockTransferItem->received_quantity) {
                return true;
            }
        }

        return false;
    }

    private function revertSourceInventory(
        StockTransfer $stockTransfer,
        array $validatedData,
        User $user,
        Collection $products,
    ): void {
        /** @var array $requestStockTransferItems */
        $requestStockTransferItems = $validatedData['stock_transfer_items'];
        $requestStockTransferItems = collect($requestStockTransferItems);

        foreach ($stockTransfer->getItems() as $stockTransferItem) {
            if ($stockTransferItem->quantity <= $stockTransferItem->received_quantity) {
                continue;
            }

            $product = $products->firstWhere('id', $stockTransferItem->product_id);

            if (! $product->has_batch) {
                $this->revertSourceInventoryFor($stockTransfer, $stockTransferItem, $user);

                continue;
            }

            $requestStockTransferItem = $requestStockTransferItems->firstWhere('id', $stockTransferItem->id);

            /** @var array $requestStockTransferItemBatchDetails */
            $requestStockTransferItemBatchDetails = $requestStockTransferItem['batch_details'];
            $batchDetails = collect($requestStockTransferItemBatchDetails);

            $this->revertBatchProductInventories($stockTransfer, $stockTransferItem, $batchDetails, $user);

            $this->decreaseQuantityOfStockTransferItemBatch($stockTransferItem, $batchDetails);
        }
    }

    private function hasPositiveDiscrepancy(StockTransfer $stockTransfer): bool
    {
        foreach ($stockTransfer->getItems() as $stockTransferItem) {
            if ($stockTransferItem->quantity >= $stockTransferItem->received_quantity) {
                continue;
            }

            if ($stockTransferItem->discrepancy_type !== StockTransferDiscrepancyTypes::POSITIVE->value) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function hasAdditionalItems(StockTransfer $stockTransfer): bool
    {
        return $stockTransfer->getItems()->containsStrict('is_extra_item', true);
    }

    private function updateAdditionalItemsSourceInventory(
        StockTransfer $stockTransfer,
        User $user,
        Collection $products,
        Collection $batches,
        array $validatedData,
    ): void {
        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);

        $stockTransferItems = $stockTransfer->getItems()->where('is_extra_item', true);

        $sourceInventories = $inventoryQueries->getInventoriesByProductIds(
            $stockTransfer->source_location_id,
            $stockTransferItems->pluck('product_id')
                ->unique()
                ->filter()
                ->toArray()
        );

        /** @var array $validatedDataItems */
        $validatedDataItems = $validatedData['stock_transfer_items'];
        $requestStockTransferItem = collect($validatedDataItems);

        foreach ($stockTransferItems as $stockTransferItem) {
            $product = $products->firstWhere('id', $stockTransferItem->product_id);

            $sourceInventory = $sourceInventories->firstWhere('product_id', $product->id);

            $derivativeRatio = null;
            if ($derivative = $stockTransferItem->unitOfMeasureDerivative) {
                $derivativeRatio = (float) $derivative->ratio;
            }

            $hasBatch = config('app.product_variant') ? $product->masterProduct->has_batch : $product->has_batch;

            if (! $hasBatch) {
                $receivedQuantity = $derivativeRatio ?
                    $stockTransferItem->received_quantity / $derivativeRatio :
                    (float) $stockTransferItem->received_quantity;

                $stockTransferInventoryService->updateInventoryUnits(
                    $sourceInventory,
                    $product,
                    $stockTransfer->source_location_id,
                    $stockTransferItem,
                    $user,
                    $receivedQuantity,
                );

                continue;
            }

            $requestBatchDetails = $requestStockTransferItem->firstWhere('id', $stockTransferItem->id);

            foreach ($requestBatchDetails['batch_details'] as $requestBatchDetail) {
                /** @var Batch $batch */
                $batch = $batches->where('product_id', $product->id)
                    ->firstWhere('number', $requestBatchDetail['batch_number']);

                $quantity = $derivativeRatio ? (float) $requestBatchDetail['quantity'] / $derivativeRatio : (float) $requestBatchDetail['quantity'];

                $stockTransferInventoryService->updateInventoryUnits(
                    $sourceInventory,
                    $product,
                    $stockTransfer->source_location_id,
                    $stockTransferItem,
                    $user,
                    $quantity,
                    $batch->id,
                );

                $stockTransferItemBatchQueries->addNew([
                    'stock_transfer_item_id' => $stockTransferItem->getKey(),
                    'batch_id' => $batch->id,
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    private function updateExtraDiscrepancyDetails(
        StockTransfer $stockTransfer,
        array $validatedData,
        User $user,
        int $companyId,
        Collection $products,
    ): void {
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        $inventoryQueries = resolve(InventoryQueries::class);

        $stockTransferItems = $stockTransfer->getItems()
            ->where('discrepancy_type', StockTransferDiscrepancyTypes::POSITIVE->value);

        $sourceInventories = $inventoryQueries->getInventoriesByProductIds(
            $stockTransfer->source_location_id,
            $stockTransferItems->pluck('product_id')->toArray()
        );

        /** @var array $requestStockTransferItems */
        $requestStockTransferItems = $validatedData['stock_transfer_items'];
        $requestStockTransferItems = collect($requestStockTransferItems);

        $batchNumbers = $requestStockTransferItems
            ->pluck('batch_details')
            ->collapse()
            ->pluck('batch_number')
            ->unique()
            ->filter()
            ->toArray();

        $batchQueries = resolve(BatchQueries::class);
        $batches = $batchQueries->getByNumbers($batchNumbers, $companyId);

        foreach ($stockTransferItems as $stockTransferItem) {
            if ($stockTransferItem->quantity >= $stockTransferItem->received_quantity) {
                continue;
            }

            $product = $products->firstWhere('id', $stockTransferItem->product_id);

            $sourceInventory = $sourceInventories->firstWhere('product_id', $product->id);

            $exceedQuantity = $stockTransferItem->received_quantity - $stockTransferItem->quantity;

            $derivativeRatio = null;
            if ($derivative = $stockTransferItem->unitOfMeasureDerivative) {
                $derivativeRatio = (float) $derivative->ratio;
            }

            if (! $product->has_batch) {
                $exceedQuantity = $derivativeRatio ?
                    $exceedQuantity / $derivativeRatio :
                    (float) $exceedQuantity;

                $stockTransferInventoryService->updateInventoryUnits(
                    $sourceInventory,
                    $product,
                    $stockTransfer->source_location_id,
                    $stockTransferItem,
                    $user,
                    $exceedQuantity,
                );

                continue;
            }

            $requestStockTransferItem = $requestStockTransferItems->firstWhere('id', $stockTransferItem->id);

            /** @var array $requestStockTransferItemBatchDetails */
            $requestStockTransferItemBatchDetails = $requestStockTransferItem['batch_details'];
            $requestBatchDetails = collect($requestStockTransferItemBatchDetails);

            foreach ($requestBatchDetails as $requestBatchDetail) {
                /** @var Batch $batch */
                $batch = $batches->firstWhere('number', $requestBatchDetail['batch_number']);
                $stockTransferItemBatch = $stockTransferItem->batches->firstWhere('batch_id', $batch->id);

                $quantity = $derivativeRatio ? (float) $requestBatchDetail['quantity'] / $derivativeRatio : (float) $requestBatchDetail['quantity'];

                $stockTransferInventoryService->updateInventoryUnits(
                    $sourceInventory,
                    $product,
                    $stockTransfer->source_location_id,
                    $stockTransferItem,
                    $user,
                    $quantity,
                    $batch->id,
                );

                if ($stockTransferItemBatch) {
                    $stockTransferItemBatchQueries->increaseQuantity($stockTransferItemBatch, $quantity);

                    continue;
                }

                $stockTransferItemBatchQueries->addNew([
                    'stock_transfer_item_id' => $stockTransferItem->id,
                    'batch_id' => $batch->id,
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    private function revertSourceInventoryFor(
        StockTransfer $stockTransfer,
        StockTransferItem $stockTransferItem,
        User $user,
    ): void {
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        $quantity = $stockTransferItem->quantity - $stockTransferItem->received_quantity;

        foreach ($stockTransferItem->units as $stockTransferItemUnit) {
            if (0.00 === $quantity) {
                return;
            }

            if ($stockTransferItemUnit->quantity > $quantity) {
                $stockTransferInventoryService->revertInventoryAsPerStockTransfer(
                    $stockTransferItem,
                    $stockTransfer,
                    $user,
                    $stockTransferItemUnit,
                    (float) $quantity
                );

                $stockTransferItemUnitQueries->decreaseQuantity($stockTransferItemUnit, $quantity);

                return;
            }

            $quantity -= $stockTransferItemUnit->quantity;

            $stockTransferInventoryService->revertInventoryAsPerStockTransfer(
                $stockTransferItem,
                $stockTransfer,
                $user,
                $stockTransferItemUnit,
                (float) $stockTransferItemUnit->quantity
            );

            $stockTransferItemUnitQueries->decreaseQuantity(
                $stockTransferItemUnit,
                (float) $stockTransferItemUnit->quantity
            );
        }
    }

    private function revertBatchProductInventories(
        StockTransfer $stockTransfer,
        StockTransferItem $stockTransferItem,
        Collection $batchDetails,
        User $user,
    ): void {
        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        foreach ($stockTransferItem->units as $stockTransferItemUnit) {
            /** @var Batch $batch */
            $batch = $stockTransferItemUnit->batch;

            $requestBatches = $batchDetails->where('batch_number', $batch->number);

            foreach ($requestBatches as $requestBatch) {
                $stockTransferInventoryService->revertInventoryAsPerStockTransfer(
                    $stockTransferItem,
                    $stockTransfer,
                    $user,
                    $stockTransferItemUnit,
                    (float) ($stockTransferItemUnit->quantity - $requestBatch['quantity'])
                );

                $stockTransferItemUnitQueries->decreaseQuantity(
                    $stockTransferItemUnit,
                    (float) ($stockTransferItemUnit->quantity - $requestBatch['quantity'])
                );
            }
        }
    }

    private function decreaseQuantityOfStockTransferItemBatch(
        StockTransferItem $stockTransferItem,
        Collection $batchDetails,
    ): void {
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        foreach ($stockTransferItem->batches as $stockTransferItemBatch) {
            /** @var Batch $batch */
            $batch = $stockTransferItemBatch->batch;

            $requestBatches = $batchDetails->where('batch_number', $batch->number);

            foreach ($requestBatches as $requestBatch) {
                $stockTransferItemBatchQueries->decreaseQuantity(
                    $stockTransferItemBatch,
                    (float) ($stockTransferItemBatch->quantity - $requestBatch['quantity'])
                );
            }
        }
    }

    private function isStatusNotApproved(StockTransfer $stockTransfer): bool
    {
        $notApprovedStatuses = [
            StatusTypes::DRAFT->value,
            StatusTypes::SYSTEM_GENERATED->value,
            StatusTypes::OPEN->value,
            StatusTypes::CANCELLED->value,
            StatusTypes::REJECTED->value,
        ];

        return in_array($stockTransfer->status, $notApprovedStatuses, true);
    }

    private function sourceIsSelectedStore(StockTransfer $stockTransfer, array $filterData): bool
    {
        $locationId = (int) $filterData['location_id'];

        return $locationId === $stockTransfer->source_location_id;
    }

    private function destinationIsSelectedStore(StockTransfer $stockTransfer, array $filterData): bool
    {
        $locationId = (int) $filterData['location_id'];

        return $locationId === $stockTransfer->destination_location_id;
    }

    private function sourceForSelectedLocation(StockTransfer $stockTransfer, int $locationId): bool
    {
        return $locationId === $stockTransfer->source_location_id;
    }

    private function destinationForSelectedLocation(StockTransfer $stockTransfer, int $locationId): bool
    {
        return $locationId === $stockTransfer->destination_location_id;
    }

    private function fetchProductsWithArchived(array $productIds, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return $productQueries->getProductsWithArchivedByIds($productIds, $companyId);
    }

    private function updateDestinationInventory(StockTransfer $stockTransfer, User $user): void
    {
        $stockTransferItems = $stockTransfer->getItems();

        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        $transitStockQueries = resolve(TransitStockQueries::class);

        foreach ($stockTransferItems as $stockTransferItem) {
            foreach ($stockTransferItem->units as $stockTransferItemUnit) {
                if ($stockTransferItemUnit->quantity > 0) {
                    $stockTransferInventoryService->updateInventoryAsPerStockTransfer(
                        $stockTransferItem,
                        $stockTransfer,
                        $user,
                        $stockTransferItemUnit,
                    );
                }
            }

            $transitStockQueries->deleteAffectedBy($stockTransferItem->id, ModelMapping::STOCK_TRANSFER_ITEM->name);

            $stockTransferItemUnitQueries->delete($stockTransferItem->units);
        }
    }

    private function derivativeExist(array $item): bool
    {
        return array_key_exists(
            'unit_of_measure_derivative_id',
            $item
        ) && null !== $item['unit_of_measure_derivative_id'];
    }
}
