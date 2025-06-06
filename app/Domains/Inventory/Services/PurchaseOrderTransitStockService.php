<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\PartiallyReceiveFulfillmentItem\PartiallyReceiveFulfillmentItemQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderItem;

class PurchaseOrderTransitStockService
{
    public function addTransitStock(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
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
            $purchaseOrderItem = $purchaseOrderItems->firstWhere('id',
                $purchaseOrderFulfillmentItem->purchase_order_item_id
            );

            $derivative = $purchaseOrderItem->derivative;

            $inventory = $inventoryQueries->fetchOrCreate($purchaseOrder->location_id, $product->id);

            if ($product->has_batch) {
                $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;
                foreach ($itemBatches as $itemBatch) {
                    $quantity = (float) $itemBatch['quantity'];
                    if ($derivative) {
                        $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
                    }

                    $this->addUnitTransitStock(
                        $inventory->id,
                        $purchaseOrderFulfillmentItem,
                        $quantity,
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

            $this->addUnitTransitStock(
                $inventory->id,
                $purchaseOrderFulfillmentItem,
                $quantity,
                $purchaseAmountId,
                null
            );
        }
    }

    public function addUnitTransitStock(
        int $inventoryId,
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        float $quantity,
        int $purchaseAmountId,
        ?int $batchId,
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->addNewAndGetId($inventoryId, $purchaseAmountId, $batchId);

        $transitStockQueries = resolve(TransitStockQueries::class);
        $transitStockQueries->addNew([
            'inventory_id' => $inventoryId,
            'inventory_unit_id' => $inventoryUnit->id,
            'affected_by_id' => $purchaseOrderFulfillmentItem->id,
            'affected_by_type' => ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name,
            'quantity' => $quantity,
            'notes' => null,
        ]);
    }

    public function removeTransitStock(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        $transitStockQueries = resolve(TransitStockQueries::class);
        foreach ($purchaseOrderFulfillment->items as $purchaseOrderFulfillmentItem) {
            $transitStockQueries->deleteAffectedBy(
                $purchaseOrderFulfillmentItem->id,
                ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
            );
        }
    }

    public function removePartialCompletedTransitStock(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        int $partialReceiveId
    ): void {
        $transitStockQueries = resolve(TransitStockQueries::class);
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        foreach ($purchaseOrderFulfillment->items as $purchaseOrderFulfillmentItem) {
            $partialReceiveFulfillmentItem = $partiallyReceiveFulfillmentItemQueries->getPartiallyReceiveFulfillmentItemByPartialReceiveId(
                $partialReceiveId,
                $purchaseOrderFulfillmentItem->id
            );

            if (! $partialReceiveFulfillmentItem) {
                continue;
            }

            $receivedQuantity = (float) $partialReceiveFulfillmentItem->received_quantity;

            /** @var PurchaseOrderItem $purchaseOrderItem */
            $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;
            $derivative = $purchaseOrderItem->derivative;

            if ($derivative) {
                $receivedQuantity = CommonFunctions::numberFormat($receivedQuantity / $derivative->ratio);
            }

            $transitStockQueries->partialDeleteAffectedBy(
                $purchaseOrderFulfillmentItem,
                ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name,
                $receivedQuantity
            );
        }
    }
}
