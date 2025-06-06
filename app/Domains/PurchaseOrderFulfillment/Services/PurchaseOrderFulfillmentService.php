<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Services;

use App\Domains\Batch\BatchQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\PurchaseOrderInventoryService;
use App\Domains\Inventory\Services\PurchaseOrderTransitStockService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\PartiallyReceiveFulfillment\Enums\PartiallyReceiveFulfillmentStatuses;
use App\Domains\PartiallyReceiveFulfillment\Services\PartiallyReceiveFulfillmentService;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentData;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Resource\PurchaseOrderFulfillmentListResource;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderFulfillmentItemBatch\PurchaseOrderFulfillmentItemBatchQueries;
use App\Domains\PurchaseOrderFulfillmentTransaction\PurchaseOrderFulfillmentTransactionQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderFulfillmentTransaction;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

class PurchaseOrderFulfillmentService
{
    public function prepareActiveBatches(array $productIds, int $companyId): Collection
    {
        $products = $this->fetchProducts($productIds, $companyId);

        return $this->fetchBatches($products, $companyId);
    }

    public function prepareActiveBatchesProductsAndInventories(
        array $productIds,
        int $companyId,
        int $locationId
    ): array {
        $products = $this->fetchProducts($productIds, $companyId);

        $batches = $this->fetchBatches($products, $companyId);

        $derivatives = $this->fetchDerivatives($this->fetchUniqueDerivatives($products));

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventories = $inventoryQueries->getByProductIdsAndLocationWithInventoryUnits($locationId, $productIds);

        return [$products, $batches, $inventories, $derivatives];
    }

    public function fetchProducts(array $productIds, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return $productQueries->getActiveInventoryProductsByIds($productIds, $companyId);
    }

    public function fetchBatches(Collection $products, int $companyId): Collection
    {
        $batches = collect([]);

        if (config('app.product_variant')) {
            $batchProductIds = $products->filter(
                fn ($product): bool => $product->masterProduct && $product->masterProduct->has_batch
            )->pluck('id')->unique()->toArray();
        } else {
            $batchProductIds = $products->where('has_batch', true)->pluck('id')->unique()->filter()->toArray();
        }

        if ([] !== $batchProductIds) {
            $batchQueries = resolve(BatchQueries::class);
            $batches = $batchQueries->getByProductIds($batchProductIds, $companyId);
        }

        return $batches;
    }

    public function fetchDerivatives(array $unitOfMeasureIds): Collection
    {
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return $unitOfMeasureDerivativeQueries->getByUnitOfMeasureIds($unitOfMeasureIds);
    }

    public function updateBatches(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        Collection $batches,
        array $batchDetails,
        bool $isDiscrepancy = false
    ): void {
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);

        $purchaseOrderFulfillmentItemBatchQueries->deleteByPurchaseOrderFulfillmentItem(
            $purchaseOrderFulfillmentItem->id
        );

        foreach ($batchDetails as $batchDetail) {
            /** @var Batch $batch */
            $batch = $batches->firstWhere('number', $batchDetail['batch_number']);

            $purchaseOrderFulfillmentItemBatchQueries->addNew([
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
                'batch_id' => $batch->id,
                'quantity' => $batchDetail['quantity'],
                'received_quantity' => $batchDetail['quantity'],
                'is_discrepancy' => $isDiscrepancy,
            ]);
        }
    }

    public function addExtraBatches(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        Collection $batches,
        array $batchDetails,
    ): void {
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);

        foreach ($batchDetails as $batchDetail) {
            /** @var Batch $batch */
            $batch = $batches->firstWhere('number', $batchDetail['batch_number']);

            $purchaseOrderFulfillmentItemBatchQueries->addNew([
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
                'batch_id' => $batch->id,
                'quantity' => $batchDetail['quantity'],
            ]);
        }
    }

    public function updatePurchaseOrderFulfillmentData(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        PurchaseOrderFulfillmentData $purchaseOrderFulfillmentData,
        Collection $purchaseOrderItems,
        Collection $batches,
    ): void {
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $purchaseOrderFulfillmentQueries->update(
            $purchaseOrderFulfillment,
            [
                'happened_at' => $purchaseOrderFulfillmentData->happened_at,
                'notes' => $purchaseOrderFulfillmentData->notes,
            ]
        );

        foreach ($purchaseOrderFulfillmentData->transfer_items as $purchaseOrderFulfillmentItemData) {
            if (! array_key_exists('transfer_quantity', $purchaseOrderFulfillmentItemData)) {
                continue;
            }

            if ($purchaseOrderFulfillmentItemData['transfer_quantity'] < 0) {
                continue;
            }

            if (! array_key_exists('package_quantity', $purchaseOrderFulfillmentItemData)) {
                $purchaseOrderFulfillmentItemData['package_quantity'] = null;
            }

            if (! array_key_exists('package_total_quantity', $purchaseOrderFulfillmentItemData)) {
                $purchaseOrderFulfillmentItemData['package_total_quantity'] = null;
            }

            if (! array_key_exists('package_type_id', $purchaseOrderFulfillmentItemData)) {
                $purchaseOrderFulfillmentItemData['package_type_id'] = null;
            }

            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItemData['purchase_order_item_id']
            );

            if (array_key_exists(
                'id',
                $purchaseOrderFulfillmentItemData
            ) && $purchaseOrderFulfillmentItemData['id'] > 0) {
                /** @var PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem */
                $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItems->firstWhere(
                    'id',
                    $purchaseOrderFulfillmentItemData['id']
                );

                $purchaseOrderItemQueries->updateTransferredQuantity(
                    $purchaseOrderItem,
                    (float) ($purchaseOrderFulfillmentItemData['transfer_quantity'] - $purchaseOrderFulfillmentItem->transfer_quantity)
                );

                $purchaseOrderFulfillmentItemQueries->update(
                    $purchaseOrderFulfillmentItem,
                    [
                        'transfer_quantity' => $purchaseOrderFulfillmentItemData['transfer_quantity'],
                        'package_quantity' => $purchaseOrderFulfillmentItemData['package_quantity'],
                        'package_total_quantity' => $purchaseOrderFulfillmentItemData['package_total_quantity'],
                        'package_type_id' => $purchaseOrderFulfillmentItemData['package_type_id'],
                        'remarks' => $purchaseOrderFulfillmentItemData['remarks'],
                    ]
                );

                if ((float) $purchaseOrderFulfillmentItemData['transfer_quantity'] === 0.0) {
                    $this->removePurchaseOrderFulfillmentItem($purchaseOrderFulfillmentItem);
                    continue;
                }

                if (array_key_exists('batch_details', $purchaseOrderFulfillmentItemData)) {
                    $this->updateBatches(
                        $purchaseOrderFulfillmentItem,
                        $batches,
                        $purchaseOrderFulfillmentItemData['batch_details']
                    );
                }

                continue;
            }

            if ((float) $purchaseOrderFulfillmentItemData['transfer_quantity'] === 0.0) {
                continue;
            }

            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->addNew([
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
                'purchase_order_item_id' => $purchaseOrderFulfillmentItemData['purchase_order_item_id'],
                'product_id' => $purchaseOrderFulfillmentItemData['product_id'],
                'transfer_quantity' => $purchaseOrderFulfillmentItemData['transfer_quantity'],
                'package_quantity' => $purchaseOrderFulfillmentItemData['package_quantity'],
                'package_total_quantity' => $purchaseOrderFulfillmentItemData['package_total_quantity'],
                'package_type_id' => $purchaseOrderFulfillmentItemData['package_type_id'],
                'remarks' => $purchaseOrderFulfillmentItemData['remarks'],
            ]);

            $purchaseOrderItemQueries->updateTransferredQuantity(
                $purchaseOrderItem,
                (float) $purchaseOrderFulfillmentItemData['transfer_quantity']
            );

            if (array_key_exists('batch_details', $purchaseOrderFulfillmentItemData)) {
                $this->updateBatches(
                    $purchaseOrderFulfillmentItem,
                    $batches,
                    $purchaseOrderFulfillmentItemData['batch_details']
                );
            }
        }
    }

    public function removePurchaseOrderFulfillmentItem(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem
    ): void {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentItemQueries->removeItemAndRelations($purchaseOrderFulfillmentItem);

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->revertReservedStock($purchaseOrderFulfillmentItem);
    }

    public function updateAdditionalItems(
        array $additionalItems,
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Collection $batches,
        Collection $products,
    ): void {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        foreach ($additionalItems as $additionalItem) {
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;
            $purchaseOrderItem = $purchaseOrder->items->firstWhere('product_id', $additionalItem['product_id']);

            $product = $products->firstWhere('id', $additionalItem['product_id']);

            if (! $purchaseOrderItem) {
                $purchaseOrderItem = $purchaseOrderItemQueries->addNew([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $additionalItem['product_id'],
                    'quantity' => 0,
                    'purchase_cost' => (float) $product->purchase_cost,
                    'transferred_quantity' => 0,
                    'unit_of_measure_derivative_id' => $additionalItem['unit_of_measure_derivative_id'] ?? null,
                    'remarks' => 'Extra item receive',
                ]);
            }

            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->addNew([
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'product_id' => $additionalItem['product_id'],
                'transfer_quantity' => 0,
                'received_quantity' => $additionalItem['quantity'],
                'package_type_id' => $additionalItem['package_type_id'] ?? null,
                'package_quantity' => $additionalItem['package_quantity'] ?? null,
                'package_total_quantity' => $additionalItem['package_total_quantity'] ?? null,
                'is_extra_item' => true,
                'discrepancy_type' => true,
                'remarks' => $additionalItem['remarks'] ?? null,
            ]);

            if (array_key_exists('batch_details', $additionalItem)) {
                $this->updateBatches($purchaseOrderFulfillmentItem, $batches, $additionalItem['batch_details'], true);
            }
        }
    }

    public function closeDiscrepancy(PurchaseOrderFulfillment $purchaseOrderFulfillment, User $user): void
    {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->getItems();

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;
        $purchaseOrderItems = $purchaseOrder->getItems();

        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            if (! $purchaseOrderFulfillmentItem->discrepancy_type) {
                continue;
            }

            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItem->purchase_order_item_id
            );

            $exceedQuantity = (float) ($purchaseOrderFulfillmentItem->received_quantity - $purchaseOrderFulfillmentItem->transfer_quantity);

            $purchaseOrderItemQueries->updateTransferredQuantity($purchaseOrderItem, $exceedQuantity);
            if ($exceedQuantity < 0) {
                $purchaseOrderInventoryService->updateSingleItemInventoryToSenderForDiscrepancy(
                    $purchaseOrderFulfillmentItem,
                    $user,
                    $purchaseOrderItems,
                    $exceedQuantity,
                    $purchaseOrder->location_id,
                );

                continue;
            }

            $purchaseOrderInventoryService->updateSingleItemInventoryToSenderForDiscrepancy(
                $purchaseOrderFulfillmentItem,
                $user,
                $purchaseOrderItems,
                $exceedQuantity,
                $purchaseOrder->location_id,
            );
        }
    }

    public function closeExternalDiscrepancy(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        array $validatedData,
        PurchaseOrderFulfillmentTransaction $purchaseOrderFulfillmentTransaction,
    ): void {
        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->getItems();

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;
        $purchaseOrderItems = $purchaseOrder->getItems();

        foreach ($validatedData['items'] as $transferItem) {
            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItems->firstWhere('id', $transferItem['id']);

            if (! $purchaseOrderFulfillmentItem->discrepancy_type) {
                continue;
            }

            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItem->purchase_order_item_id
            );

            $receivedQuantity = (float) ($transferItem['received_quantity'] - $purchaseOrderFulfillmentItem->transfer_quantity);

            $purchaseOrderItemQueries->updateTransferredQuantity($purchaseOrderItem, $receivedQuantity);

            $purchaseOrderFulfillmentItemQueries->addReceivedQuantity(
                $purchaseOrderFulfillmentItem,
                (float) $transferItem['received_quantity']
            );

            $purchaseOrderFulfillmentItemQueries->updateRemarks(
                $purchaseOrderFulfillmentItem,
                (string) $transferItem['remarks']
            );

            if (array_key_exists('batch_details', $transferItem)) {
                $purchaseOrderService->updateBatches(
                    $purchaseOrderFulfillmentItem,
                    $transferItem['batch_details'],
                    (int) $purchaseOrder->company_id
                );
            }

            $exceedQuantity = (float) ($transferItem['received_quantity'] - $purchaseOrderFulfillmentItem->transfer_quantity);
        }

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdWithRelationForEdit(
            $purchaseOrderFulfillment->id,
            $purchaseOrder->company_id
        );

        $partiallyReceiveFulfillmentService = resolve(PartiallyReceiveFulfillmentService::class);
        $partiallyReceiveFulfillment = $partiallyReceiveFulfillmentService->addPartialByDO(
            $purchaseOrderFulfillmentTransaction->user,
            $purchaseOrderFulfillment,
            $purchaseOrder->location_id
        );

        if ($partiallyReceiveFulfillment) {
            $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
            $purchaseOrderInventoryService->updateInventoryToPartialReceiver(
                $purchaseOrderFulfillment,
                $purchaseOrderFulfillmentTransaction->user,
                $partiallyReceiveFulfillment
            );

            $purchaseOrderTransitStockService = resolve(PurchaseOrderTransitStockService::class);
            $purchaseOrderTransitStockService->removePartialCompletedTransitStock(
                $purchaseOrderFulfillment,
                $partiallyReceiveFulfillment->id
            );
        }
    }

    public function prepareTransferTypeForDeliveryNote(int $orderType): int
    {
        if (OrderTypes::SALES_ORDER->value === $orderType) {
            return SequenceTypes::SODO->value;
        }

        return SequenceTypes::PODO->value;
    }

    public function getDeliveryOrdersStatusCount(array $filterData, int $companyId): array
    {
        $deliveryOrdersStatusCounts = [];
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        $fulfillmentStatusCounts = $purchaseOrderFulfillmentQueries->allDeliveryOrdersStatusCount(
            $filterData,
            $companyId
        );

        foreach (FulfillmentStatuses::getList() as $fulfillmentStatus) {
            $fulfillmentStatusCount = $fulfillmentStatusCounts->firstWhere('status', $fulfillmentStatus['id']);

            $statusName = FulfillmentStatuses::getFormattedCaseName($fulfillmentStatus['id']);
            $deliveryOrdersStatusCounts[$statusName] = [
                'count' => (int) $fulfillmentStatusCount?->count,
                'id' => $fulfillmentStatus['id'],
            ];
        }

        return $deliveryOrdersStatusCounts;
    }

    public static function getOrderNumbers(PurchaseOrder $purchaseOrder, string $deliveryOrderNumber): array
    {
        $orderNumber = [];

        $orderNumber[] = 'Delivery Order : ' . $deliveryOrderNumber;

        $orderNumber[] = OrderTypes::getFormattedCaseName(
            $purchaseOrder->order_type
        ) . ' : ' . $purchaseOrder->order_number;

        $orderNumber[] = self::getExternalOrderNumber($purchaseOrder);

        if ($purchaseOrder->parentPurchaseOrder) {
            $orderNumber[] = OrderTypes::getFormattedCaseName(
                $purchaseOrder->parentPurchaseOrder->order_type
            ) . ' : ' . $purchaseOrder->parentPurchaseOrder->order_number;

            $orderNumber[] = self::getExternalOrderNumber($purchaseOrder->parentPurchaseOrder);
        }

        return array_filter($orderNumber);
    }

    private static function getExternalOrderNumber(PurchaseOrder $purchaseOrder): string
    {
        if (! $purchaseOrder->external_order_number) {
            return '';
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return 'External ' . OrderTypes::getFormattedCaseName(
                OrderTypes::PURCHASE_ORDER->value
            ) . ' : ' . $purchaseOrder->external_order_number;
        }

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) {
            return 'External ' . OrderTypes::getFormattedCaseName(
                OrderTypes::SALES_ORDER->value
            ) . ' : ' . $purchaseOrder->external_order_number;
        }

        return 'External ' . OrderTypes::getFormattedCaseName(
            $purchaseOrder->order_type
        ) . ' : ' . $purchaseOrder->external_order_number;
    }

    public function fetchPurchaseOrderFulfillments(
        int $purchaseOrderId,
        int $companyId,
        array $filterData,
    ): array {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $lengthAwarePaginator = $purchaseOrderFulfillmentQueries->listQuery(
            $filterData,
            $purchaseOrderId,
            $companyId
        );

        $statusCounts = [];

        $fulfillmentStatusCounts = $purchaseOrderFulfillmentQueries->allFulfillmentStatusCount(
            $filterData,
            $purchaseOrderId,
            $companyId
        );

        foreach ($fulfillmentStatusCounts as $fulfillmentStatusCount) {
            $statusName = FulfillmentStatuses::getFormattedCaseName($fulfillmentStatusCount->status);
            $statusCounts[$statusName] = [
                'count' => $fulfillmentStatusCount->count,
                'id' => $fulfillmentStatusCount->status,
            ];
        }

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => PurchaseOrderFulfillmentListResource::collection($lengthAwarePaginator->getCollection()),
            'statusCounts' => $statusCounts,
        ];
    }

    public function checkShippedDeliveryOrder(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        if ($purchaseOrderFulfillment->status === FulfillmentStatuses::OPEN->value) {
            return;
        }

        if ($purchaseOrderFulfillment->created_by_company_id) {
            return;
        }

        abort(417, 'The delivery order is locked for shipped as it is currently not in draft status.');
    }

    public function shippedDeliveryOrder(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->purchaseOrderMarkAsPartialFulfillment($purchaseOrder, $user);

        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::SHIPPED->value,
            $user
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus(
            $purchaseOrderFulfillment,
            FulfillmentStatuses::SHIPPED->value
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->shiftExternalFulfillment($purchaseOrderFulfillment, $user);

        $purchaseOrderService->markAsFulfillmentCompletedPurchaseOrder($purchaseOrder, $user, null);

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->removeReservationStockForSalesOrderOnFulfillmentItemsShipped(
            $purchaseOrderFulfillment,
            $user
        );
    }

    public function checkMarkAsReceivedDeliveryOrder(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        if ($purchaseOrderFulfillment->status === FulfillmentStatuses::SHIPPED->value) {
            return;
        }

        if ($purchaseOrderFulfillment->status === FulfillmentStatuses::CANCELLED->value) {
            abort(417, 'The Delivery order is cancelled.');
        }

        if (! $purchaseOrderFulfillment->created_by_company_id) {
            return;
        }

        abort(417, 'The delivery order is locked for received  as it is currently not in shipped status.');
    }

    public function markAsReceivedDeliveryOrder(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::RECEIVED->value,
            $user
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus(
            $purchaseOrderFulfillment,
            FulfillmentStatuses::RECEIVED->value
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->receivedExternalFulfillment($purchaseOrderFulfillment, $user);
    }

    public function checkMarkAsCancelDeliveryOrder(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        if ($purchaseOrderFulfillment->status === FulfillmentStatuses::SHIPPED->value) {
            return;
        }

        if ($purchaseOrderFulfillment->status === FulfillmentStatuses::RECEIVED->value) {
            abort(417, 'The Delivery order is already received.');
        }

        if (! $purchaseOrderFulfillment->created_by_company_id) {
            return;
        }

        abort(
            417,
            'At this moment, cancel the delivery order is not possible as it currently does not have a shipped status.'
        );
    }

    public function markAsCancelDeliveryOrder(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::CANCELLED->value,
            $user
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus(
            $purchaseOrderFulfillment,
            FulfillmentStatuses::CANCELLED->value
        );

        $this->decreaseTransferQuantityFromPurchaseOrderItems($purchaseOrderFulfillment);

        $purchaseOrderTransitStockService = resolve(PurchaseOrderTransitStockService::class);
        $purchaseOrderTransitStockService->removeTransitStock($purchaseOrderFulfillment);

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->cancelExternalFulfillment($purchaseOrderFulfillment, $user);
    }

    public function decreaseTransferQuantityFromPurchaseOrderItems(
        PurchaseOrderFulfillment $purchaseOrderFulfillment
    ): void {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;
        $purchaseOrderItems = $purchaseOrder->getItems();

        foreach ($purchaseOrderFulfillment->items as $item) {
            $purchaseOrderItem = $purchaseOrderItems->firstWhere('id', $item->purchase_order_item_id);

            $purchaseOrderItemQueries->decreaseTransferredQuantity(
                $purchaseOrderItem,
                (float) $item->transfer_quantity
            );
        }
    }

    public function checkDeliveryNote(PurchaseOrderFulfillment $purchaseOrderFulfillment, string $redirectUrl): void
    {
        if ($purchaseOrderFulfillment->status === FulfillmentStatuses::RECEIVED->value && null === $purchaseOrderFulfillment->created_by_company_id) {
            return;
        }

        throw new RedirectWithErrorException(
            $redirectUrl,
            'The delivery order cannot be used for generating a delivery note because it is currently not in the received status.'
        );
    }

    public function checkClosedDeliveryOrder(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        if ($this->isPartiallyReceivePending($purchaseOrderFulfillment)) {
            abort(417, 'The delivery order cannot be closed when partially receive is not completed.');
        }
    }

    public function checkDeliveryOrderDiscrepancy(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        if ($this->isPartiallyReceivePending($purchaseOrderFulfillment)) {
            abort(417, 'The delivery order cannot closed discrepancy when partially receive is not completed.');
        }
    }

    public function isPartiallyReceivePending(PurchaseOrderFulfillment $purchaseOrderFulfillment): bool
    {
        return $purchaseOrderFulfillment->partiallyReceives
            ->filter(
                fn ($item): bool => $item->status === PartiallyReceiveFulfillmentStatuses::DRAFT->value || $item->status === PartiallyReceiveFulfillmentStatuses::APPROVED->value
            )
            ->isNotEmpty();
    }

    public function closedDeliveryOrder(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        PurchaseOrder $purchaseOrder,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $partiallyReceiveFulfillmentService = resolve(PartiallyReceiveFulfillmentService::class);
        $partiallyReceiveFulfillment = $partiallyReceiveFulfillmentService->addPartialByDO(
            $user,
            $purchaseOrderFulfillment,
            $purchaseOrder->location_id
        );

        if ($partiallyReceiveFulfillment) {
            $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
            $purchaseOrderInventoryService->updateInventoryToPartialReceiver(
                $purchaseOrderFulfillment,
                $user,
                $partiallyReceiveFulfillment
            );

            $purchaseOrderTransitStockService = resolve(PurchaseOrderTransitStockService::class);
            $purchaseOrderTransitStockService->removePartialCompletedTransitStock(
                $purchaseOrderFulfillment,
                $partiallyReceiveFulfillment->id
            );
        }

        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::CLOSED->value,
            $user
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus(
            $purchaseOrderFulfillment,
            FulfillmentStatuses::CLOSED->value
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->closedExternalFulfillment($purchaseOrderFulfillment, $user);

        $purchaseOrderService->closePurchaseOrder($purchaseOrder);
    }

    public function deliveryOrderDiscrepancy(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|StoreManager|WarehouseManager|null $user
    ): void {
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::DISCREPANCY->value,
            $user
        );
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus(
            $purchaseOrderFulfillment,
            FulfillmentStatuses::DISCREPANCY->value
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->postExternalFulfillmentDiscrepancy($purchaseOrderFulfillment, $user);
    }

    public function checkEditDeliveryOrder(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        if ($purchaseOrderFulfillment->status === FulfillmentStatuses::DRAFT->value) {
            return;
        }

        if (! $purchaseOrderFulfillment->created_by_company_id) {
            return;
        }

        abort(417, 'The delivery order is locked for editing as it is currently not in draft status.');
    }

    public function addShippingDetails(
        PurchaseOrderFulfillmentData $purchaseOrderFulfillmentData,
        PurchaseOrder $purchaseOrder,
        Collection $batches
    ): void {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);

        $purchaseOrderItemIds = collect($purchaseOrderFulfillmentData->transfer_items)->pluck(
            'purchase_order_item_id'
        )->unique()->filter()->toArray();
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderItems = $purchaseOrderItemQueries->getByIds($purchaseOrderItemIds);

        $sequenceQueries = resolve(SequenceQueries::class);

        $transferType = $this->prepareTransferTypeForDeliveryNote(OrderTypes::SALES_ORDER->value);

        $sequence = $sequenceQueries->addNew($purchaseOrder->location_id, $transferType);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->addNew([
            'purchase_order_id' => $purchaseOrder->id,
            'created_by_company_id' => $purchaseOrder->company_id,
            'happened_at' => $purchaseOrderFulfillmentData->happened_at,
            'notes' => $purchaseOrderFulfillmentData->notes,
            'delivery_order_number' => $sequence->getCompleteNumber(),
            'status' => FulfillmentStatuses::DRAFT->value,
        ]);

        foreach ($purchaseOrderFulfillmentData->transfer_items as $purchaseOrderFulfillmentItemData) {
            if (! array_key_exists('transfer_quantity', $purchaseOrderFulfillmentItemData)) {
                continue;
            }

            if ($purchaseOrderFulfillmentItemData['transfer_quantity'] <= 0) {
                continue;
            }

            if (! array_key_exists('package_quantity', $purchaseOrderFulfillmentItemData)) {
                $purchaseOrderFulfillmentItemData['package_quantity'] = null;
            }

            if (! array_key_exists('package_total_quantity', $purchaseOrderFulfillmentItemData)) {
                $purchaseOrderFulfillmentItemData['package_total_quantity'] = null;
            }

            if (! array_key_exists('package_type_id', $purchaseOrderFulfillmentItemData)) {
                $purchaseOrderFulfillmentItemData['package_type_id'] = null;
            }

            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->addNew([
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
                'purchase_order_item_id' => $purchaseOrderFulfillmentItemData['purchase_order_item_id'],
                'product_id' => $purchaseOrderFulfillmentItemData['product_id'],
                'transfer_quantity' => $purchaseOrderFulfillmentItemData['transfer_quantity'],
                'package_quantity' => $purchaseOrderFulfillmentItemData['package_quantity'],
                'package_total_quantity' => $purchaseOrderFulfillmentItemData['package_total_quantity'],
                'package_type_id' => $purchaseOrderFulfillmentItemData['package_type_id'],
                'remarks' => $purchaseOrderFulfillmentItemData['remarks'],
            ]);

            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItemData['purchase_order_item_id']
            );

            $purchaseOrderItemQueries->updateTransferredQuantity(
                $purchaseOrderItem,
                (float) $purchaseOrderFulfillmentItemData['transfer_quantity']
            );

            if (array_key_exists('batch_details', $purchaseOrderFulfillmentItemData)) {
                $this->updateBatches(
                    $purchaseOrderFulfillmentItem,
                    $batches,
                    $purchaseOrderFulfillmentItemData['batch_details']
                );
            }
        }
    }

    public function closeDeliveryOrderDiscrepancy(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::CLOSED->value,
            $user
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus(
            $purchaseOrderFulfillment,
            FulfillmentStatuses::CLOSED->value
        );

        $this->closeDiscrepancy($purchaseOrderFulfillment, $user);

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->closedDiscrepancyExternalFulfillment($purchaseOrderFulfillment, $user);

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderService->closePurchaseOrder($purchaseOrder);
    }

    public function getDeliveryOrderStatusCounts(array $filterData, int $companyId): array
    {
        $statusCounts = [];
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $deliveryOrdersStatusCount = $purchaseOrderFulfillmentQueries->allDeliveryOrdersStatusCount(
            $filterData,
            $companyId
        );
        foreach (FulfillmentStatuses::getList() as $fulfillmentStatus) {
            $deliveryOrderStatusCount = $deliveryOrdersStatusCount->firstWhere('status', $fulfillmentStatus['id']);
            $statusName = FulfillmentStatuses::getFormattedCaseName($fulfillmentStatus['id']);
            $statusCounts[$statusName] = [
                'count' => (int) $deliveryOrderStatusCount?->count,
                'id' => $fulfillmentStatus['id'],
            ];
        }

        return $statusCounts;
    }

    public function canPurchaseOrderDeliveryOrder(PurchaseOrder $purchaseOrder): bool
    {
        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::APPROVED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::CLOSED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::PARTIAL_FULFILLMENT->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::FULFILLMENT_COMPLETED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::SALES_ORDER->value
            && $purchaseOrder->status === Statuses::APPROVED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::SALES_ORDER->value
            && $purchaseOrder->status === Statuses::CLOSED->value
        ) {
            return true;
        }

        if ($purchaseOrder->order_type !== OrderTypes::SALES_ORDER->value) {
            return false;
        }

        if ($purchaseOrder->status === Statuses::FULFILLMENT_COMPLETED->value) {
            return true;
        }

        return $purchaseOrder->status === Statuses::PARTIAL_FULFILLMENT->value;
    }

    public function checkDeliveryOrder(PurchaseOrder $purchaseOrder): void
    {
        if ($this->canPurchaseOrderDeliveryOrder($purchaseOrder)) {
            return;
        }

        abort(417, 'The Delivery Order cannot be accessed.');
    }

    public function hasPurchaseOrderItems(PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->items->filter(
            fn ($purchaseOrderItem): bool => ($purchaseOrderItem->quantity - $purchaseOrderItem->rejected_quantity - $purchaseOrderItem->transferred_quantity) > 0
        )->isEmpty();
    }

    public function checkAllItemsDelivered(Collection $purchaseOrderItems, string $routeUrl): void
    {
        $purchaseOrderItems = $purchaseOrderItems->filter(
            fn ($purchaseOrderItem): bool => ($purchaseOrderItem->quantity - $purchaseOrderItem->rejected_quantity - $purchaseOrderItem->transferred_quantity) > 0
        );

        if ($purchaseOrderItems->isNotEmpty()) {
            return;
        }

        throw new RedirectWithErrorException(
            $routeUrl,
            'All items that were to be added to the Delivery Order have already been included.'
        );
    }

    public function checkUrlRestrictionsForCreateDeliveryOrder(PurchaseOrder $purchaseOrder, string $routeUrl): void
    {
        if ($purchaseOrder->getOrderType() === OrderTypes::SALES_ORDER->value) {
            return;
        }

        throw new RedirectWithErrorException(
            $routeUrl,
            'You are not authorized to access this URL as it is restricted.'
        );
    }

    public function checkDeliveryOrderDetails(PurchaseOrder $purchaseOrder, string $routeUrl): void
    {
        $this->checkAllItemsDelivered($purchaseOrder->items, $routeUrl);
        $this->checkUrlRestrictionsForCreateDeliveryOrder($purchaseOrder, $routeUrl);
    }

    public function cancelExternalDeliveryOrder(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        PurchaseOrderFulfillmentTransaction $purchaseOrderFulfillmentTransaction,
        ?string $externalUsername
    ): void {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);

        /** @var User $user */
        $user = $purchaseOrderFulfillmentTransaction->user;

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->getItems();

        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

            $purchaseOrderItemQueries->decreaseTransferredQuantity(
                $purchaseOrderItem,
                (float) $purchaseOrderFulfillmentItem->transfer_quantity
            );

            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

            $purchaseOrderFulfillmentItemUnits = $purchaseOrderFulfillmentItem->units;
            if ($purchaseOrderFulfillment->status === FulfillmentStatuses::SHIPPED->value) {
                foreach ($purchaseOrderFulfillmentItemUnits as $purchaseOrderFulfillmentItemUnit) {
                    $purchaseOrderInventoryService->revertTransferQuantityForSender(
                        $purchaseOrderFulfillmentItem,
                        $user,
                        (float) $purchaseOrderFulfillmentItemUnit->quantity,
                        $purchaseOrder->location_id,
                        $purchaseOrderFulfillmentItem->product_id,
                        $purchaseOrderFulfillmentItemUnit->purchase_amount_id,
                        $purchaseOrderFulfillmentItemUnit->batch_id,
                    );
                }
            }
        }

        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::CANCELLED->value,
            null,
            $externalUsername
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus(
            $purchaseOrderFulfillment,
            FulfillmentStatuses::CANCELLED->value
        );
    }

    public function deleteBatchDetails(int $purchaseOrderFulfillmentItemId, string $batchNumber): void
    {
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries->deleteBatchItem($purchaseOrderFulfillmentItemId, $batchNumber);
    }

    public function updateTheBatchDetails(
        Collection $batchDetails,
        int $purchaseOrderFulfillmentItemId,
        int $discrepancyStatus,
        int $companyId
    ): void {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);

        /** @var PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem */
        $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->getByIdWithProductAndPurchaseOrderAndPurchaseOrderItem(
            $purchaseOrderFulfillmentItemId
        );

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $inventoryDetails = [
            'external_company_id' => $purchaseOrder->external_company_id,
            'external_location_id' => $purchaseOrder->external_location_id,
        ];

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        [$externalBatches, $externalProduct, $externalInventoryUnits] = $purchaseOrderService->getExternalProductBatchesAndInventoryUnits(
            $batchDetails->pluck('batch_number')->toArray(),
            $inventoryDetails,
            (string) $product->upc
        );

        $purchaseOrderFulfillmentCheckRequestService = resolve(PurchaseOrderFulfillmentCheckRequestService::class);
        $purchaseOrderFulfillmentCheckRequestService->checkBatchDetailsRequest(
            $purchaseOrderFulfillmentItem,
            $product,
            $batchDetails,
            $externalBatches,
            $externalInventoryUnits,
            $externalProduct
        );

        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);

        foreach ($batchDetails as $batchDetail) {
            $batch = $externalBatches->firstWhere('number', $batchDetail['batch_number']);

            $purchaseOrderFulfillmentItemBatchData = [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemId,
                'batch_id' => $batch['id'],
                'received_quantity' => $batchDetail['received_quantity'],
                'is_discrepancy' => array_key_exists(
                    'is_discrepancy',
                    $batchDetail
                ) ? $batchDetail['is_discrepancy'] : array_key_exists('is_extra', $batchDetail),
            ];

            $purchaseOrderFulfillmentItemBatchQueries->addOrUpdate($purchaseOrderFulfillmentItemBatchData);
        }

        $purchaseOrderFulfillmentItemQueries->updateDiscrepancyStatusById(
            $purchaseOrderFulfillmentItemId,
            $companyId,
            $discrepancyStatus
        );
    }

    public function updateTheDiscrepancyBatchDetails(
        Collection $batchDetails,
        int $purchaseOrderFulfillmentItemId,
        int $discrepancyStatus,
        int $companyId
    ): void {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->getByIdWithProductAndPurchaseOrderAndPurchaseOrderItem(
            $purchaseOrderFulfillmentItemId
        );

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $batchQueries = resolve(BatchQueries::class);
        $batches = $batchQueries->getByNumbersWithProductUpc(
            $batchDetails->pluck('batch_number')->toArray(),
            $product->upc
        );

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $batchInventoryUnits = $inventoryUnitQueries->getInventoryUnitsByBatchAndUpc(
            $batchDetails->pluck('batch_number')->toArray(),
            $purchaseOrder->location_id,
            $product->upc
        );

        $purchaseOrderFulfillmentCheckRequestService = resolve(PurchaseOrderFulfillmentCheckRequestService::class);
        $purchaseOrderFulfillmentCheckRequestService->checkBatchDetailsRequest(
            $purchaseOrderFulfillmentItem,
            $product,
            $batchDetails,
            $batches,
            $batchInventoryUnits,
            $product->toArray()
        );

        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);

        foreach ($batchDetails as $batchDetail) {
            /** @var Batch $batch */
            $batch = $batches->firstWhere('number', $batchDetail['batch_number']);

            $purchaseOrderFulfillmentItemBatchData = [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemId,
                'batch_id' => $batch->id,
                'received_quantity' => $batchDetail['received_quantity'],
                'is_discrepancy' => array_key_exists(
                    'is_discrepancy',
                    $batchDetail
                ) ? $batchDetail['is_discrepancy'] : array_key_exists('is_extra', $batchDetail),
            ];

            $purchaseOrderFulfillmentItemBatchQueries->addOrUpdate($purchaseOrderFulfillmentItemBatchData);
        }

        $purchaseOrderFulfillmentItemQueries->updateDiscrepancyStatusById(
            $purchaseOrderFulfillmentItemId,
            $companyId,
            $discrepancyStatus
        );
    }

    public function markAsOpen(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            $purchaseOrderFulfillment->status,
            FulfillmentStatuses::OPEN->value,
            $user
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentQueries->updateStatus($purchaseOrderFulfillment, FulfillmentStatuses::OPEN->value);

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->postExternalFulfillment($purchaseOrderFulfillment, $user);
    }

    private function fetchUniqueDerivatives(Collection $products): array
    {
        if (config('app.product_variant')) {
            return $products->map(
                fn ($product) => $product->masterProduct ? $product->masterProduct->unit_of_measure_id : null
            )->unique()->filter()->toArray();
        }

        return $products->pluck('unit_of_measure_id')->unique()->filter()->toArray();
    }
}
