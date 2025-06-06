<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\CommonFunctions;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\PartiallyReceiveFulfillmentItem\PartiallyReceiveFulfillmentItemQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillmentItemBatch\PurchaseOrderFulfillmentItemBatchQueries;
use App\Domains\PurchaseOrderFulfillmentItemUnit\PurchaseOrderFulfillmentItemUnitQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Models\Batch;
use App\Models\InventoryUnit;
use App\Models\Model;
use App\Models\PartiallyReceiveFulfillment;
use App\Models\PartiallyReceiveFulfillmentItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderFulfillmentItemUnit;
use App\Models\PurchaseOrderItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

class PurchaseOrderInventoryService
{
    public function addInventoryReservedStock(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->loadRelations($purchaseOrderFulfillment);

        $this->revertReservedStockForPurchaseOrderFulfillment($purchaseOrderFulfillment);

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderItems = $purchaseOrder->items;

        $inventoryQueries = resolve(InventoryQueries::class);

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;
        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            /** @var Product $product */
            $product = $purchaseOrderFulfillmentItem->product;

            /** @var PurchaseOrderItem $purchaseOrderItem */
            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItem->purchase_order_item_id
            );

            $derivative = $purchaseOrderItem->derivative;

            $inventory = $inventoryQueries->fetchOrCreate($purchaseOrder->location_id, $product->id);

            $transferQuantity = (float) $purchaseOrderFulfillmentItem->transfer_quantity;

            if ($derivative) {
                $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
            }

            $inventoryQueries->increaseReservedStock($inventory, $transferQuantity);

            if ($product->has_batch) {
                $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;
                foreach ($itemBatches as $itemBatch) {
                    $quantity = (float) $itemBatch->quantity;
                    if ($derivative) {
                        $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
                    }

                    $this->updateInventoryUnitsForReserved(
                        $inventory->id,
                        $product->has_batch,
                        $purchaseOrderFulfillmentItem,
                        $quantity,
                        $itemBatch->batch_id,
                    );
                }

                return;
            }

            $this->updateInventoryUnitsForReserved(
                $inventory->id,
                $product->has_batch,
                $purchaseOrderFulfillmentItem,
                $transferQuantity,
                null,
            );
        }
    }

    public function addInventoryReservedStockForPurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->loadRelationForPurchaseOrderInventory($purchaseOrder);

        $this->revertReservedStockForPurchaseOrderRecord($purchaseOrder);

        $purchaseOrderItems = $purchaseOrder->items;

        $inventoryQueries = resolve(InventoryQueries::class);

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            /** @var Product $product */
            $product = $purchaseOrderItem->product;

            $derivative = $purchaseOrderItem->derivative;

            $inventory = $inventoryQueries->fetchOrCreate($purchaseOrder->location_id, $product->id);

            $transferQuantity = (float) $purchaseOrderItem->quantity;

            if ($derivative) {
                $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
            }

            $inventoryQueries->increaseReservedStock($inventory, $transferQuantity);

            $this->updateInventoryUnitsForReservedStockForPurchaseOrder(
                $inventory->id,
                $product->has_batch,
                $purchaseOrderItem,
                $transferQuantity,
                null,
            );
        }
    }

    public function revertReservedStockForPurchaseOrderFulfillment(
        PurchaseOrderFulfillment $purchaseOrderFulfillment
    ): void {
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;
        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            $this->revertReservedStock($purchaseOrderFulfillmentItem);
        }
    }

    public function revertReservedStockForPurchaseOrderRecord(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrderItems = $purchaseOrder->items;
        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            $this->revertReservedStockForPurchaseOrderItem($purchaseOrderItem);
        }
    }

    public function revertReservedStock(PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($purchaseOrderFulfillmentItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventory = $inventoryQueries->getInventoryById($reservedStock->inventory_id);
            $inventoryUnit = $inventoryUnitQueries->getById($reservedStock->inventory_unit_id);
            $quantity = (float) $reservedStock->quantity;

            $inventoryUnitQueries->revertReservedStock($inventoryUnit, $quantity);
            $inventoryQueries->revertReservedStock($inventory, $quantity);
            $reservedStockQueries->delete($reservedStock);
        }
    }

    public function revertReservedStockForPurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($purchaseOrderItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventory = $inventoryQueries->getInventoryById($reservedStock->inventory_id);
            $inventoryUnit = $inventoryUnitQueries->getById($reservedStock->inventory_unit_id);
            $quantity = (float) $reservedStock->quantity;

            $inventoryUnitQueries->revertReservedStock($inventoryUnit, $quantity);
            $inventoryQueries->revertReservedStock($inventory, $quantity);
            $reservedStockQueries->delete($reservedStock);
        }
    }

    public function updateInventoryUnitsForReserved(
        int $inventoryId,
        bool $hasBatch,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        float $quantity,
        ?int $batchId,
    ): void {
        $inventoryService = resolve(InventoryService::class);

        $inventoryUnits = $inventoryService->getInventoryUnits($hasBatch, $inventoryId, $batchId);

        $totalQuantity = abs($quantity);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $this->increaseInventoryUnitsForReserved(
                    $inventoryId,
                    $inventoryUnit,
                    $purchaseOrderFulfillmentItem,
                    $totalQuantity
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;

            $this->increaseInventoryUnitsForReserved(
                $inventoryId,
                $inventoryUnit,
                $purchaseOrderFulfillmentItem,
                $inventoryUnit->quantity
            );
        }
    }

    public function updateInventoryUnitsForReservedStockForPurchaseOrder(
        int $inventoryId,
        bool $hasBatch,
        PurchaseOrderItem $purchaseOrderItem,
        float $quantity,
        ?int $batchId,
    ): void {
        $inventoryService = resolve(InventoryService::class);

        $inventoryUnits = $inventoryService->getInventoryUnits($hasBatch, $inventoryId, $batchId);

        $totalQuantity = abs($quantity);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $this->increaseInventoryUnitsForReservedStockForPurchaseOrder(
                    $inventoryId,
                    $inventoryUnit,
                    $purchaseOrderItem,
                    $totalQuantity
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;

            $this->increaseInventoryUnitsForReservedStockForPurchaseOrder(
                $inventoryId,
                $inventoryUnit,
                $purchaseOrderItem,
                (float) $inventoryUnit->quantity
            );
        }
    }

    public function increaseInventoryUnitsForReserved(
        int $inventoryId,
        InventoryUnit $inventoryUnit,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        float $quantity,
    ): void {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $reservedStockQueries->addNew($inventoryId, $inventoryUnit->id, $quantity, $purchaseOrderFulfillmentItem);

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->increaseReservedStock($inventoryUnit, $quantity);
    }

    public function increaseInventoryUnitsForReservedStockForPurchaseOrder(
        int $inventoryId,
        InventoryUnit $inventoryUnit,
        PurchaseOrderItem $purchaseOrderItem,
        float $quantity,
    ): void {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $reservedStockQueries->addNew($inventoryId, $inventoryUnit->id, $quantity, $purchaseOrderItem);

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->increaseReservedStock($inventoryUnit, $quantity);
    }

    public function removeReservationStockForPurchaseOrder(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        User $user
    ): void {
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;
        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            $this->removeReservationStock($purchaseOrderFulfillmentItem, $user);
        }
    }

    public function removeReservationStockForSalesOrderOnFulfillmentItemsShipped(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        User $user
    ): void {
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;

        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            $this->removeReservationStockForSalesOrderOnFulfillmentItemShipped($purchaseOrderFulfillmentItem, $user);
        }
    }

    public function removeReservationStockForSalesOrderOnFulfillmentItemShipped(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user
    ): void {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

        $derivative = $purchaseOrderItem->derivative;

        $reservedStocks = $reservedStockQueries->getByAffectedBy($purchaseOrderItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventoryUnit = $reservedStock->inventoryUnit;
            $quantity = (float) $purchaseOrderFulfillmentItem->transfer_quantity;

            if ($derivative) {
                $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
            }

            $inventoryUnitQueries->decreaseReservedStock($inventoryUnit, $quantity);
            $inventory = $inventoryQueries->decreaseReservedStock($reservedStock->inventory, $quantity);

            $inventoryStock = ($inventory->stock + $inventory->reserved_stock);

            $inventoryUpdateQueries->addNew(
                $inventory->product_id,
                (float) ('-' . $quantity),
                $inventory->location_id,
                $purchaseOrderFulfillmentItem,
                $user,
                $inventoryStock,
                $inventoryUnit->batch_id,
                $inventoryUnit->purchase_amount_id,
            );

            $reservedStockQueries->decrementQuantity($reservedStock, $quantity);

            $this->addPurchaseOrderFulfillmentItemUnit(
                $quantity,
                $inventory->id,
                $purchaseOrderFulfillmentItem->id,
                $inventoryUnit->purchase_amount_id,
                $inventoryUnit->batch_id,
            );
        }
    }

    public function removeReservationStock(PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem, User $user): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($purchaseOrderFulfillmentItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventoryUnit = $reservedStock->inventoryUnit;
            $quantity = (float) $reservedStock->quantity;

            $inventoryUnitQueries->decreaseReservedStock($inventoryUnit, $quantity);
            $inventory = $inventoryQueries->decreaseReservedStock($reservedStock->inventory, $quantity);

            $inventoryStock = ($inventory->stock + $inventory->reserved_stock);

            $inventoryUpdateQueries->addNew(
                $inventory->product_id,
                (float) ('-' . $quantity),
                $inventory->location_id,
                $purchaseOrderFulfillmentItem,
                $user,
                $inventoryStock,
                $inventoryUnit->batch_id,
                $inventoryUnit->purchase_amount_id,
            );

            $reservedStockQueries->delete($reservedStock);

            $this->addPurchaseOrderFulfillmentItemUnit(
                $quantity,
                $inventory->id,
                $purchaseOrderFulfillmentItem->id,
                $inventoryUnit->purchase_amount_id,
                $inventoryUnit->batch_id,
            );
        }
    }

    public function addPurchaseOrderFulfillmentItemUnit(
        float $quantity,
        int $inventoryId,
        int $purchaseOrderFulfillmentItemId,
        int $purchaseAmountId,
        ?int $batchId
    ): void {
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);
        $purchaseOrderFulfillmentItemUnitQueries->addNew(
            [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemId,
                'inventory_id' => $inventoryId,
                'purchase_amount_id' => $purchaseAmountId,
                'batch_id' => $batchId,
                'quantity' => $quantity,
            ]
        );
    }

    public function removeInventoryUnits(
        int $inventoryId,
        Product $product,
        int $locationId,
        Model $model,
        User $user,
        float $quantity,
        float $inventoryStock,
        ?int $batchId,
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryService = resolve(InventoryService::class);

        $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventoryId, $batchId);

        $totalQuantity = abs($quantity);
        $inventoryStock += $totalQuantity;

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $inventoryUnitQueries->decreaseStock($inventoryUnit, $totalQuantity);
                $inventoryStock -= $totalQuantity;

                $inventoryUpdateQueries->addNew(
                    $product->id,
                    (float) ('-' . $totalQuantity),
                    $locationId,
                    $model,
                    $user,
                    $inventoryStock,
                    $inventoryUnit->batch_id,
                    $inventoryUnit->purchase_amount_id,
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;
            $inventoryStock -= $inventoryUnit->quantity;
            $inventoryUpdateQueries->addNew(
                $product->id,
                (float) ('-' . $inventoryUnit->quantity),
                $locationId,
                $model,
                $user,
                $inventoryStock,
                $inventoryUnit->batch_id,
                $inventoryUnit->purchase_amount_id,
            );

            $inventoryUnitQueries->decreaseStock($inventoryUnit, (float) $inventoryUnit->quantity);
        }
    }

    public function updateInventoryToReceiver(PurchaseOrderFulfillment $purchaseOrderFulfillment, User $user): void
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderItems = $purchaseOrder->items;

        $inventoryQueries = resolve(InventoryQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;
        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            /** @var Product $product */
            $product = $purchaseOrderFulfillmentItem->product;

            /** @var PurchaseOrderItem $purchaseOrderItem */
            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItem->purchase_order_item_id
            );

            $derivative = $purchaseOrderItem->derivative;

            $inventory = $inventoryQueries->fetchOrCreate($purchaseOrder->location_id, $product->id);

            $transferQuantity = (float) $purchaseOrderFulfillmentItem->transfer_quantity;

            if ($derivative) {
                $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
            }

            $inventoryStock = $inventoryQueries->increaseStock($inventory, $transferQuantity);

            if ($product->has_batch) {
                $inventoryStock -= $transferQuantity;
                $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;
                foreach ($itemBatches as $itemBatch) {
                    $quantity = (float) $itemBatch['quantity'];
                    if ($derivative) {
                        $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
                    }

                    $inventoryStock += $quantity;

                    $this->updateInventoryUnits(
                        $inventory->id,
                        $product->id,
                        $purchaseOrder->location_id,
                        $purchaseOrderFulfillmentItem,
                        $user,
                        $quantity,
                        $inventoryStock,
                        $purchaseAmountId,
                        $itemBatch['batch_id']
                    );
                }

                continue;
            }

            $quantity = (float) $purchaseOrderFulfillmentItem->transfer_quantity;
            if ($derivative) {
                $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
            }

            $this->updateInventoryUnits(
                $inventory->id,
                $product->id,
                $purchaseOrder->location_id,
                $purchaseOrderFulfillmentItem,
                $user,
                $quantity,
                $inventoryStock,
                $purchaseAmountId,
                null
            );
        }
    }

    public function updateInventoryUnits(
        int $inventoryId,
        int $productId,
        int $locationId,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        float $quantity,
        float $inventoryStock,
        int $purchaseAmountId,
        ?int $batchId,
    ): void {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnitReservedStock($quantity, $inventoryId, $purchaseAmountId, $batchId);

        $inventoryUpdateQueries->addNew(
            $productId,
            $quantity,
            $locationId,
            $purchaseOrderFulfillmentItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );

        $this->updatePurchaseOrderFulfillmentItemUnit(
            $purchaseOrderFulfillmentItem->id,
            $inventoryId,
            $purchaseAmountId,
            $quantity,
            $batchId
        );
    }

    public function updatePurchaseOrderFulfillmentItemUnit(
        int $purchaseOrderFulfillmentItemId,
        int $inventoryId,
        int $purchaseAmountId,
        float $quantity,
        ?int $batchId = null,
    ): void {
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);

        $purchaseOrderFulfillmentItemUnit = $purchaseOrderFulfillmentItemUnitQueries->getByIdInventoryIdPurchaseAmountIdAndBatchId(
            $purchaseOrderFulfillmentItemId,
            $inventoryId,
            $purchaseAmountId,
            $batchId,
        );

        if ($purchaseOrderFulfillmentItemUnit instanceof PurchaseOrderFulfillmentItemUnit) {
            $purchaseOrderFulfillmentItemUnitQueries->increaseQuantity($purchaseOrderFulfillmentItemUnit, $quantity);

            return;
        }

        $purchaseOrderFulfillmentItemUnitQueries->addNew(
            [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemId,
                'inventory_id' => $inventoryId,
                'purchase_amount_id' => $purchaseAmountId,
                'batch_id' => $batchId,
                'quantity' => $quantity,
            ]
        );
    }

    public function updateSingleItemInventoryToSender(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        Collection $batches,
        float $quantity,
        int $locationId,
        array $itemBatches,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderItems->firstWhere(
            'id',
            $purchaseOrderFulfillmentItem->purchase_order_item_id
        );

        $derivative = $purchaseOrderItem->derivative;

        $transferQuantity = $quantity;

        if ($derivative) {
            $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
        }

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $product->id);

        $inventoryStock = $inventoryQueries->decreaseStock($inventory, $transferQuantity);

        if ($product->has_batch) {
            foreach ($itemBatches as $itemBatch) {
                /** @var Batch $batch */
                $batch = $batches->firstWhere('number', $itemBatch['batch_number']);

                $quantity = (float) $itemBatch['quantity'];
                if ($derivative) {
                    $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
                }

                $this->removeInventoryUnits(
                    $inventory->id,
                    $product,
                    $locationId,
                    $purchaseOrderFulfillmentItem,
                    $user,
                    $quantity,
                    $inventoryStock,
                    $batch->id,
                );
            }

            return;
        }

        if ($derivative) {
            $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
        }

        $this->removeInventoryUnits(
            $inventory->id,
            $product,
            $locationId,
            $purchaseOrderFulfillmentItem,
            $user,
            $quantity,
            $inventoryStock,
            null,
        );
    }

    public function updateSingleItemInventoryToReceiver(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        Collection $batches,
        float $quantity,
        int $locationId,
        array $itemBatches,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderItems->firstWhere(
            'id',
            $purchaseOrderFulfillmentItem->purchase_order_item_id
        );

        $derivative = $purchaseOrderItem->derivative;

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $product->id);

        $transferQuantity = $quantity;

        if ($derivative) {
            $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
        }

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $transferQuantity);

        if ($product->has_batch) {
            foreach ($itemBatches as $itemBatch) {
                /** @var Batch $batch */
                $batch = $batches->firstWhere('number', $itemBatch['batch_number']);

                $quantity = (float) $itemBatch['quantity'];
                if ($derivative) {
                    $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
                }

                $this->updateInventoryUnits(
                    $inventory->id,
                    $product->id,
                    $locationId,
                    $purchaseOrderFulfillmentItem,
                    $user,
                    $quantity,
                    $inventoryStock,
                    $purchaseAmountId,
                    $batch->id
                );
            }

            return;
        }

        if ($derivative) {
            $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
        }

        $this->updateInventoryUnits(
            $inventory->id,
            $product->id,
            $locationId,
            $purchaseOrderFulfillmentItem,
            $user,
            $quantity,
            $inventoryStock,
            $purchaseAmountId,
            null
        );
    }

    public function revertReservedStockForPurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrderFulfillments = $purchaseOrder->fulfillments;
        foreach ($purchaseOrderFulfillments as $purchaseOrderFulfillment) {
            $this->revertReservedStockForPurchaseOrderFulfillment($purchaseOrderFulfillment);
        }
    }

    public function revertTransferQuantityForSender(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        float $quantity,
        int $locationId,
        int $productId,
        int $purchaseAmountId,
        ?int $batchId,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate($locationId, $productId);
        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $reservedStocks = $reservedStockQueries->getByAffectedBy($purchaseOrderItem);

        foreach ($reservedStocks as $reservedStock) {
            $reservedStockQueries->incrementQuantity($reservedStock, $quantity);
        }

        $inventoryStock = $inventoryQueries->increaseOnlyReservedStockAndGetSumOfReservedAndStock(
            $inventory,
            $quantity
        );

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnitReservedStock($quantity, $inventory->id, $purchaseAmountId, $batchId);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            $quantity,
            $locationId,
            $purchaseOrderFulfillmentItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );
    }

    public function updateSingleItemInventoryToSenderForDiscrepancy(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        float $quantity,
        int $locationId,
    ): void {
        if (! $purchaseOrderFulfillmentItem->discrepancy_type) {
            return;
        }

        if ($quantity < 0) {
            $this->updateNegativeDiscrepancyForSender(
                $purchaseOrderFulfillmentItem,
                $user,
                $purchaseOrderItems,
                abs($quantity),
                $locationId,
            );

            return;
        }

        $this->updatePositiveDiscrepancyForSender(
            $purchaseOrderFulfillmentItem,
            $user,
            $purchaseOrderItems,
            $quantity,
            $locationId,
        );
    }

    public function updatePositiveDiscrepancyForSender(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        float $quantity,
        int $locationId,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $reservedStockQueries = resolve(ReservedStockQueries::class);

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderItems->firstWhere(
            'id',
            $purchaseOrderFulfillmentItem->purchase_order_item_id
        );

        $derivative = $purchaseOrderItem->derivative;

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $product->id);

        $transferQuantity = $quantity;

        if ($derivative) {
            $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
        }

        $transferDiscrepancyQuantity = $transferQuantity;
        $deductQuantityFromInventoryReservedStock = (float) min($inventory->reserved_stock, $transferQuantity);
        $transferDiscrepancyQuantity -= $deductQuantityFromInventoryReservedStock;

        $latestInventory = $inventoryQueries->decreaseReservedStock(
            $inventory,
            $deductQuantityFromInventoryReservedStock
        );
        $reservedStocks = $reservedStockQueries->getByAffectedBy($purchaseOrderItem);

        foreach ($reservedStocks as $reservedStock) {
            $reservedStock = $reservedStockQueries->decrementQuantityWithLatestReservedStock(
                $reservedStock,
                $deductQuantityFromInventoryReservedStock
            );

            if ((float) $reservedStock->quantity === 0.0) {
                $reservedStockQueries->delete($reservedStock);
            }
        }

        $inventoryStock = ($latestInventory->stock + $latestInventory->reserved_stock);

        if ($transferDiscrepancyQuantity > 0) {
            $inventoryStock = $inventoryQueries->decreaseStock($latestInventory, $transferDiscrepancyQuantity);
        }

        if ($product->has_batch) {
            $this->updateBatchInventoryForSender(
                $user,
                $purchaseOrderFulfillmentItem,
                $locationId,
                $inventory->id,
                $inventoryStock,
                $derivative,
            );

            return;
        }

        $this->removeInventoryUnitsPositiveDiscrepancyForSender(
            $purchaseOrderFulfillmentItem,
            $product,
            $user,
            $locationId,
            $inventory->id,
            $transferQuantity,
            $inventoryStock,
            null,
        );
    }

    public function removeInventoryUnitsPositiveDiscrepancyForSender(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        Product $product,
        User $user,
        int $locationId,
        int $inventoryId,
        float $quantity,
        float $inventoryStock,
        ?int $batchId,
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryService = resolve(InventoryService::class);

        $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventoryId, $batchId);

        $totalQuantity = abs($quantity);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $inventoryUnitQueries->decreaseReservedStock($inventoryUnit, $totalQuantity);

                $inventoryUpdateQueries->addNew(
                    $product->id,
                    (float) ('-' . $totalQuantity),
                    $locationId,
                    $purchaseOrderFulfillmentItem,
                    $user,
                    $inventoryStock,
                    $inventoryUnit->batch_id,
                    $inventoryUnit->purchase_amount_id,
                );

                $this->updatePurchaseOrderFulfillmentItemUnit(
                    $purchaseOrderFulfillmentItem->id,
                    $inventoryId,
                    $inventoryUnit->purchase_amount_id,
                    $totalQuantity,
                    $inventoryUnit->batch_id,
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;
            $inventoryStock -= $inventoryUnit->quantity;
            $inventoryUpdateQueries->addNew(
                $product->id,
                (float) ('-' . $inventoryUnit->quantity),
                $locationId,
                $purchaseOrderFulfillmentItem,
                $user,
                $inventoryStock,
                $inventoryUnit->batch_id,
                $inventoryUnit->purchase_amount_id,
            );

            $this->updatePurchaseOrderFulfillmentItemUnit(
                $purchaseOrderFulfillmentItem->id,
                $inventoryId,
                $inventoryUnit->purchase_amount_id,
                (float) $inventoryUnit->quantity,
                $inventoryUnit->batch_id,
            );

            $inventoryUnitQueries->decreaseStock($inventoryUnit, (float) $inventoryUnit->quantity);
        }
    }

    public function updateNegativeDiscrepancyForSender(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        float $quantity,
        int $locationId,
    ): void {
        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderItems->firstWhere(
            'id',
            $purchaseOrderFulfillmentItem->purchase_order_item_id
        );

        $derivative = $purchaseOrderItem->derivative;

        $transferQuantity = $quantity;

        if ($derivative) {
            $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
        }

        /** @var Collection $purchaseOrderFulfillmentItemUnits */
        $purchaseOrderFulfillmentItemUnits = $purchaseOrderFulfillmentItem->units;

        /** @var PurchaseOrderFulfillmentItemUnit $firstPurchaseOrderFulfillmentItemUnit */
        $firstPurchaseOrderFulfillmentItemUnit = $purchaseOrderFulfillmentItemUnits->first();

        $inventoryQueries = resolve(InventoryQueries::class);
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $reservedStocks = $reservedStockQueries->getByAffectedBy($purchaseOrderItem);

        foreach ($reservedStocks as $reservedStock) {
            $reservedStockQueries->incrementQuantity($reservedStock, $transferQuantity);
        }

        $inventory = $inventoryQueries->getInventoryById((int) $firstPurchaseOrderFulfillmentItemUnit->inventory_id);

        $inventoryStock = $inventoryQueries->increaseOnlyReservedStockAndGetSumOfReservedAndStock(
            $inventory,
            $transferQuantity
        );

        $inventoryStock -= $transferQuantity;

        if ($firstPurchaseOrderFulfillmentItemUnit->batch_id) {
            $this->updateBatchInventoryForSender(
                $user,
                $purchaseOrderFulfillmentItem,
                $locationId,
                $inventory->id,
                $inventoryStock,
                $derivative,
            );

            return;
        }

        $this->decreasePurchaseOrderFulfillmentItemUnits(
            $purchaseOrderFulfillmentItem,
            $user,
            $purchaseOrderFulfillmentItemUnits,
            $locationId,
            $transferQuantity,
            $inventoryStock,
        );
    }

    public function updateBatchInventoryForSender(
        User $user,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        int $locationId,
        int $inventoryId,
        float $inventoryStock,
        ?UnitOfMeasureDerivative $derivative,
    ): void {
        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $fulfillmentItemBatches = $purchaseOrderFulfillmentItemBatchQueries->getByFulfillmentItemId(
            $purchaseOrderFulfillmentItem->id
        );

        foreach ($fulfillmentItemBatches as $fulfillmentItemBatch) {
            $units = $purchaseOrderFulfillmentItem->units->where('batch_id', $fulfillmentItemBatch->batch_id);

            $batchQuantity = (float) $fulfillmentItemBatch->received_quantity;

            if ($derivative instanceof UnitOfMeasureDerivative) {
                $batchQuantity = CommonFunctions::numberFormat($batchQuantity / $derivative->ratio);
            }

            if ($units->sum('quantity') === $batchQuantity) {
                continue;
            }

            if ($units->sum('quantity') < $batchQuantity) {
                $totalQuantity = $batchQuantity - (float) $units->sum('quantity');

                $this->removeInventoryUnitsPositiveDiscrepancyForSender(
                    $purchaseOrderFulfillmentItem,
                    $product,
                    $user,
                    $locationId,
                    $inventoryId,
                    $totalQuantity,
                    $inventoryStock,
                    $fulfillmentItemBatch->batch_id,
                );

                $inventoryStock -= $totalQuantity;

                continue;
            }

            $totalQuantity = $units->sum('quantity') - $batchQuantity;
            $this->decreasePurchaseOrderFulfillmentItemUnits(
                $purchaseOrderFulfillmentItem,
                $user,
                $units,
                $locationId,
                $totalQuantity,
                $inventoryStock,
            );

            $inventoryStock += $totalQuantity;
        }
    }

    public function decreasePurchaseOrderFulfillmentItemUnits(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $units,
        int $locationId,
        float $quantity,
        float $inventoryStock,
    ): void {
        foreach ($units as $unit) {
            if ($quantity <= 0) {
                return;
            }

            if ($unit->quantity >= $quantity) {
                $inventoryStock += $quantity;

                $this->decreasePurchaseOrderFulfillmentItemUnitQuantity($unit, $quantity);

                $this->updateInventoryUnitsWithoutFulfillmentItemUnits(
                    $unit->inventory_id,
                    $purchaseOrderFulfillmentItem->product_id,
                    $locationId,
                    $purchaseOrderFulfillmentItem,
                    $user,
                    $quantity,
                    $inventoryStock,
                    $unit->purchase_amount_id,
                    $unit->batch_id,
                );

                $quantity = 0;

                return;
            }

            $quantity -= $unit->quantity;
            $inventoryStock += (float) $unit->quantity;

            $this->decreasePurchaseOrderFulfillmentItemUnitQuantity($unit, $unit->quantity);

            $this->updateInventoryUnitsWithoutFulfillmentItemUnits(
                $unit->inventory_id,
                $purchaseOrderFulfillmentItem->product_id,
                $locationId,
                $purchaseOrderFulfillmentItem,
                $user,
                $unit->quantity,
                $inventoryStock,
                $unit->purchase_amount_id,
                $unit->batch_id,
            );
        }
    }

    public function updateInventoryUnitsWithoutFulfillmentItemUnits(
        int $inventoryId,
        int $productId,
        int $locationId,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        float $quantity,
        float $inventoryStock,
        int $purchaseAmountId,
        ?int $batchId,
    ): void {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnitReservedStock($quantity, $inventoryId, $purchaseAmountId, $batchId);

        $inventoryUpdateQueries->addNew(
            $productId,
            $quantity,
            $locationId,
            $purchaseOrderFulfillmentItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );
    }

    public function decreasePurchaseOrderFulfillmentItemUnitQuantity(
        PurchaseOrderFulfillmentItemUnit $purchaseOrderFulfillmentItemUnit,
        float $quantity
    ): void {
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);
        $purchaseOrderFulfillmentItemUnitQueries->decreaseQuantity($purchaseOrderFulfillmentItemUnit, $quantity);
    }

    public function increasePurchaseOrderFulfillmentItemUnitQuantity(
        PurchaseOrderFulfillmentItemUnit $purchaseOrderFulfillmentItemUnit,
        float $quantity
    ): void {
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);
        $purchaseOrderFulfillmentItemUnitQueries->increaseQuantity($purchaseOrderFulfillmentItemUnit, $quantity);
    }

    public function updateSingleItemInventoryToReceiverForDiscrepancy(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        float $quantity,
        int $locationId,
    ): void {
        if (! $purchaseOrderFulfillmentItem->discrepancy_type) {
            return;
        }

        if ($quantity < 0) {
            $this->updateNegativeDiscrepancyForReceiver(
                $purchaseOrderFulfillmentItem,
                $user,
                $purchaseOrderItems,
                abs($quantity),
                $locationId,
            );

            return;
        }

        $this->updatePositiveDiscrepancyForReceiver(
            $purchaseOrderFulfillmentItem,
            $user,
            $purchaseOrderItems,
            $quantity,
            $locationId,
        );
    }

    public function updateNegativeDiscrepancyForReceiver(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        float $quantity,
        int $locationId,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderItems->firstWhere(
            'id',
            $purchaseOrderFulfillmentItem->purchase_order_item_id
        );

        $derivative = $purchaseOrderItem->derivative;

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $product->id);

        $transferQuantity = $quantity;

        if ($derivative) {
            $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
        }

        $inventoryStock = $inventoryQueries->decreaseStock($inventory, $transferQuantity);
        $inventoryStock += $transferQuantity;

        if ($product->has_batch) {
            $this->updateBatchInventoryForReceiver(
                $user,
                $purchaseOrderFulfillmentItem,
                $locationId,
                $inventory->id,
                $inventoryStock,
                $derivative,
            );

            return;
        }

        $this->decreaseInventoryUnitsForReceiver(
            $purchaseOrderFulfillmentItem,
            $user,
            $purchaseOrderFulfillmentItem->units,
            $locationId,
            $transferQuantity,
            $inventoryStock,
        );
    }

    public function updatePositiveDiscrepancyForReceiver(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $purchaseOrderItems,
        float $quantity,
        int $locationId,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderItems->firstWhere(
            'id',
            $purchaseOrderFulfillmentItem->purchase_order_item_id
        );

        $derivative = $purchaseOrderItem->derivative;

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $product->id);

        $transferQuantity = $quantity;

        if ($derivative) {
            $transferQuantity = CommonFunctions::numberFormat($transferQuantity / $derivative->ratio);
        }

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $transferQuantity);

        if ($product->has_batch) {
            $inventoryStock -= $transferQuantity;
            $this->updateBatchInventoryForReceiver(
                $user,
                $purchaseOrderFulfillmentItem,
                $locationId,
                $inventory->id,
                $inventoryStock,
                $derivative,
            );

            return;
        }

        $this->increaseInventoryUnitsPositiveDiscrepancyForReceiver(
            $purchaseOrderFulfillmentItem,
            $user,
            $locationId,
            $inventory->id,
            $transferQuantity,
            $inventoryStock,
            null,
        );
    }

    public function increaseInventoryUnitsPositiveDiscrepancyForReceiver(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        int $locationId,
        int $inventoryId,
        float $quantity,
        float $inventoryStock,
        ?int $batchId,
    ): void {
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        $this->updateInventoryUnitsWithoutFulfillmentItemUnits(
            $inventoryId,
            $purchaseOrderFulfillmentItem->product_id,
            $locationId,
            $purchaseOrderFulfillmentItem,
            $user,
            $quantity,
            $inventoryStock,
            $purchaseAmountId,
            $batchId,
        );

        $this->updatePurchaseOrderFulfillmentItemUnit(
            $purchaseOrderFulfillmentItem->id,
            $inventoryId,
            $purchaseAmountId,
            $quantity,
            $batchId,
        );
    }

    public function updateBatchInventoryForReceiver(
        User $user,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        int $locationId,
        int $inventoryId,
        float $inventoryStock,
        ?UnitOfMeasureDerivative $derivative,
    ): void {
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $fulfillmentItemBatches = $purchaseOrderFulfillmentItemBatchQueries->getByFulfillmentItemId(
            $purchaseOrderFulfillmentItem->id
        );
        foreach ($fulfillmentItemBatches as $fulfillmentItemBatch) {
            $units = $purchaseOrderFulfillmentItem->units->where('batch_id', $fulfillmentItemBatch->batch_id);

            $batchQuantity = (float) $fulfillmentItemBatch->received_quantity;

            if ($derivative instanceof UnitOfMeasureDerivative) {
                $batchQuantity = CommonFunctions::numberFormat($batchQuantity / $derivative->ratio);
            }

            if ($units->sum('quantity') === $batchQuantity) {
                continue;
            }

            if ($units->sum('quantity') < $batchQuantity) {
                $totalQuantity = $batchQuantity - (float) $units->sum('quantity');
                $inventoryStock += $totalQuantity;

                $this->increaseInventoryUnitsPositiveDiscrepancyForReceiver(
                    $purchaseOrderFulfillmentItem,
                    $user,
                    $locationId,
                    $inventoryId,
                    $totalQuantity,
                    $inventoryStock,
                    $fulfillmentItemBatch->batch_id,
                );

                continue;
            }

            $totalQuantity = $units->sum('quantity') - $batchQuantity;
            $this->decreaseInventoryUnitsForReceiver(
                $purchaseOrderFulfillmentItem,
                $user,
                $units,
                $locationId,
                $totalQuantity,
                $inventoryStock,
            );

            $inventoryStock -= $totalQuantity;
        }
    }

    public function decreaseInventoryUnitsForReceiver(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        Collection $units,
        int $locationId,
        float $quantity,
        float $inventoryStock,
    ): void {
        foreach ($units as $unit) {
            if ($quantity <= 0) {
                return;
            }

            if ($unit->quantity >= $quantity) {
                $inventoryStock -= $quantity;

                $this->decreasePurchaseOrderFulfillmentItemUnitQuantity($unit, $quantity);

                $this->decreaseInventoryUnitsWithoutFulfillmentItemUnits(
                    $unit->inventory_id,
                    $purchaseOrderFulfillmentItem->product_id,
                    $locationId,
                    $purchaseOrderFulfillmentItem,
                    $user,
                    $quantity,
                    $inventoryStock,
                    $unit->purchase_amount_id,
                    $unit->batch_id,
                );

                $quantity = 0;

                return;
            }

            $quantity -= $unit->quantity;
            $inventoryStock -= (float) $unit->quantity;

            $this->decreasePurchaseOrderFulfillmentItemUnitQuantity($unit, $unit->quantity);

            $this->decreaseInventoryUnitsWithoutFulfillmentItemUnits(
                $unit->inventory_id,
                $purchaseOrderFulfillmentItem->product_id,
                $locationId,
                $purchaseOrderFulfillmentItem,
                $user,
                $unit->quantity,
                $inventoryStock,
                $unit->purchase_amount_id,
                $unit->batch_id,
            );
        }
    }

    public function decreaseInventoryUnitsWithoutFulfillmentItemUnits(
        int $inventoryId,
        int $productId,
        int $locationId,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        User $user,
        float $quantity,
        float $inventoryStock,
        int $purchaseAmountId,
        ?int $batchId,
    ): void {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->decreaseInventoryUnit($quantity, $inventoryId, $purchaseAmountId, $batchId);

        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $purchaseOrderFulfillmentItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );
    }

    public function updateTheReservedStockFromPurchaseRequestToSalesOrder(PurchaseOrder $salesOrder): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $salesOrder = $purchaseOrderQueries->getByIdWithParentDetails($salesOrder->id, $salesOrder->company_id);

        if (! $salesOrder->parentPurchaseOrder instanceof PurchaseOrder) {
            return;
        }

        $transferRequest = $salesOrder->parentPurchaseOrder;
        $transferRequestItems = $transferRequest->items;

        if ($transferRequestItems->isEmpty() && $salesOrder->items->isEmpty()) {
            return;
        }

        /** @var PurchaseOrderItem $transferRequestItem */
        $transferRequestItem = $transferRequestItems->first();

        $reservedStocks = $reservedStockQueries->getByAffectedByIds(
            $transferRequestItems->pluck('id')->toArray(),
            $transferRequestItem::class
        );

        foreach ($reservedStocks as $reservedStock) {
            /** @var PurchaseOrderItem $salesOrderItem */
            $salesOrderItem = $salesOrder->items->firstWhere(
                'parent_purchase_order_item_id',
                $reservedStock->affected_by_id
            );

            $reservedStockQueries->updateAffectedByType($reservedStock, $salesOrderItem->getKey());
        }
    }

    public function updateInventoryToPartialReceiver(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        User $user,
        PartiallyReceiveFulfillment $partiallyReceiveFulfillment,
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderItems = $purchaseOrder->items;

        $inventoryQueries = resolve(InventoryQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;
        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            /** @var Product $product */
            $product = $purchaseOrderFulfillmentItem->product;

            /** @var PurchaseOrderItem $purchaseOrderItem */
            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItem->purchase_order_item_id
            );

            $derivative = $purchaseOrderItem->derivative;

            $inventory = $inventoryQueries->fetchOrCreate($purchaseOrder->location_id, $product->id);

            $partiallyReceiveFulfillmentItem = $partiallyReceiveFulfillmentItemQueries->getPartiallyReceiveFulfillmentItemByPartialReceiveId(
                $partiallyReceiveFulfillment->id,
                $purchaseOrderFulfillmentItem->id
            );

            if (! $partiallyReceiveFulfillmentItem) {
                continue;
            }

            $receivedQuantity = (float) $partiallyReceiveFulfillmentItem->received_quantity;

            if ($derivative) {
                $receivedQuantity = CommonFunctions::numberFormat($receivedQuantity / $derivative->ratio);
            }

            $inventoryStock = $inventoryQueries->increaseStock($inventory, $receivedQuantity);

            if ($product->has_batch) {
                $inventoryStock -= $receivedQuantity;
                $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;
                foreach ($itemBatches as $itemBatch) {
                    $quantity = (float) $itemBatch['quantity'];
                    if ($derivative) {
                        $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
                    }

                    $inventoryStock += $quantity;

                    $this->updateInventoryUnitsForPartialReceiver(
                        $inventory->id,
                        $product->id,
                        $purchaseOrder->location_id,
                        $purchaseOrderFulfillmentItem,
                        $partiallyReceiveFulfillmentItem,
                        $user,
                        $quantity,
                        $inventoryStock,
                        $purchaseAmountId,
                        $itemBatch['batch_id']
                    );
                }

                continue;
            }

            $quantity = (float) $partiallyReceiveFulfillmentItem->received_quantity;
            if ($derivative) {
                $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
            }

            $this->updateInventoryUnitsForPartialReceiver(
                $inventory->id,
                $product->id,
                $purchaseOrder->location_id,
                $purchaseOrderFulfillmentItem,
                $partiallyReceiveFulfillmentItem,
                $user,
                $quantity,
                $inventoryStock,
                $purchaseAmountId,
                null
            );
        }
    }

    public function updateInventoryUnitsForPartialReceiver(
        int $inventoryId,
        int $productId,
        int $locationId,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        PartiallyReceiveFulfillmentItem $partiallyReceiveFulfillmentItem,
        User $user,
        float $quantity,
        float $inventoryStock,
        int $purchaseAmountId,
        ?int $batchId,
    ): void {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit($quantity, $inventoryId, $purchaseAmountId, $batchId);

        $inventoryUpdateQueries->addNew(
            $productId,
            $quantity,
            $locationId,
            $partiallyReceiveFulfillmentItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );

        $this->updatePurchaseOrderFulfillmentItemUnit(
            $purchaseOrderFulfillmentItem->id,
            $inventoryId,
            $purchaseAmountId,
            $quantity,
            $batchId
        );
    }
}
