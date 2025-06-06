<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\DataPreparer;

use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Models\ExternalLocation;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\PartiallyReceiveFulfillmentItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Vendor;

class StockMovementDataPreparer
{
    public function getFromLocation(InventoryUpdate $inventoryUpdate): string
    {
        $affectedBy = $inventoryUpdate->affectedBy;
        if ($affectedBy instanceof StockTransferItem) {
            /** @var StockTransfer $stockTransfer */
            $stockTransfer = $affectedBy->stockTransfer;

            return $stockTransfer->getSourceLocation();
        }

        if ($affectedBy instanceof PurchaseOrderFulfillmentItem) {
            /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
            $purchaseOrderFulfillment = $affectedBy->purchaseOrderFulfillment;

            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

            return $this->getPurchaseFromLocation($purchaseOrder);
        }

        if ($affectedBy instanceof PartiallyReceiveFulfillmentItem) {
            /** @var PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem */
            $purchaseOrderFulfillmentItem = $affectedBy->purchaseOrderFulfillmentItem;

            /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
            $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;

            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

            return $this->getPurchaseFromLocation($purchaseOrder);
        }

        if ($affectedBy instanceof GoodsReceivedNoteProduct) {
            /** @var GoodsReceivedNote $goodsReceivedNote */
            $goodsReceivedNote = $affectedBy->goodsReceivedNote;

            return $this->getGoodsReceivedNoteVendor($goodsReceivedNote);
        }

        return 'N/A';
    }

    public function getGoodsReceivedNoteVendor(GoodsReceivedNote $goodsReceivedNote): string
    {
        /** @var ?Vendor $vendor */
        $vendor = $goodsReceivedNote->vendor;

        return $vendor ? $vendor->name.'(vendor)' : 'N/A';
    }

    public function getPurchaseFromLocation(PurchaseOrder $purchaseOrder): string
    {
        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        /** @var Location $location */
        $location = $purchaseOrder->location;

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $location->name;
        }

        return $externalLocation->name;
    }

    public function getToLocation(InventoryUpdate $inventoryUpdate): string
    {
        $affectedBy = $inventoryUpdate->affectedBy;
        if ($affectedBy instanceof StockTransferItem) {
            /** @var StockTransfer $stockTransfer */
            $stockTransfer = $affectedBy->stockTransfer;

            return $stockTransfer->getDestinationLocation();
        }

        if ($affectedBy instanceof PurchaseOrderFulfillmentItem) {
            /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
            $purchaseOrderFulfillment = $affectedBy->purchaseOrderFulfillment;

            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

            return $this->getPurchaseToLocation($purchaseOrder);
        }

        if ($affectedBy instanceof PartiallyReceiveFulfillmentItem) {
            /** @var PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem */
            $purchaseOrderFulfillmentItem = $affectedBy->purchaseOrderFulfillmentItem;

            /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
            $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;

            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

            return $this->getPurchaseToLocation($purchaseOrder);
        }

        if ($affectedBy instanceof GoodsReceivedNoteProduct) {
            /** @var GoodsReceivedNote $goodsReceivedNote */
            $goodsReceivedNote = $affectedBy->goodsReceivedNote;

            return $this->getGoodsReceivedNoteToLocation($goodsReceivedNote);
        }

        return 'N/A';
    }

    public function getGoodsReceivedNoteToLocation(GoodsReceivedNote $goodsReceivedNote): string
    {
        /** @var Location $location */
        $location = $goodsReceivedNote->location;

        return $location->name;
    }

    public function getPurchaseToLocation(PurchaseOrder $purchaseOrder): string
    {
        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        /** @var Location $location */
        $location = $purchaseOrder->location;

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $externalLocation->name;
        }

        return $location->name;
    }
}
