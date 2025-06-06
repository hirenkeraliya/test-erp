<?php

declare(strict_types=1);

namespace App\Domains\ReservedStock\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\ReservedStock\Resources\ReservedInventoryReportListResource;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\InventoryUpdate;
use App\Models\Order;
use App\Models\ReservedStock;

class ReservedInventoryReportService
{
    /**
     * Transform the resource into an array.
     */
    public function getReservedInventoryReportReferenceNumber(
        ReservedInventoryReportListResource|ReservedStock $reservedInventoryResource,
        ?string $authPanel = null
    ): array {
        /** @var InventoryUpdate $reservedStock */
        $reservedStock = $reservedInventoryResource;
        $type = $reservedStock->affected_by_type;
        $affectedBy = $reservedStock->affectedBy;

        if (ModelMapping::getCaseName(ModelMapping::STOCK_TRANSFER_ITEM->value) === $type) {
            $stockTransferNumber = $reservedStock->quantity > 0 ? $affectedBy->stockTransfer->transfer_in_number : $affectedBy->stockTransfer->transfer_out_number;
            if (null === $stockTransferNumber) {
                $stockTransferNumber = $affectedBy->stockTransfer->request_order_number ?? $affectedBy->stockTransfer->transfer_order_number;
            }

            $param = 'stock_transfer_number';
            if ('' === $stockTransferNumber || null === $stockTransferNumber) {
                $stockTransferNumber = $affectedBy->stockTransfer->id;
                $param = 'stock_transfer_id';
            }

            return [
                'message' => 'Stock Transfer: ' . $stockTransferNumber,
                'url' => null !== $stockTransferNumber && null !== $authPanel ? route(
                    $authPanel . '.stock_transfers.index',
                    [
                        $param => $stockTransferNumber,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->value) === $type) {
            $purchaseOrder = $affectedBy->purchaseOrderFulfillment->purchaseOrder;
            $purchaseOrderNumber = $purchaseOrder->order_number;
            $orderType = OrderTypes::getFormattedCaseName($purchaseOrder->order_type);

            return [
                'message' => $orderType . ':' . $purchaseOrderNumber,
                'url' => null !== $purchaseOrderNumber && null !== $authPanel ? route(
                    $authPanel . '.purchase_orders.index',
                    [
                        'order_number' => $purchaseOrderNumber,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::PURCHASE_ORDER_ITEM->value) === $type) {
            $purchaseOrder = $affectedBy->purchaseOrder;
            $purchaseOrderNumber = $purchaseOrder->order_number;
            $orderType = OrderTypes::getFormattedCaseName($purchaseOrder->order_type);

            return [
                'message' => $orderType . ':' . $purchaseOrderNumber,
                'url' => null !== $purchaseOrderNumber && null !== $authPanel ? route(
                    $authPanel . '.purchase_orders.index',
                    [
                        'order_number' => $purchaseOrderNumber,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::SALE_ITEM->value) === $type) {
            $offlineId = ($affectedBy->sale->offline_sale_id ?: 'N/A');

            $route = 'N/A' !== $offlineId && null !== $authPanel ? route($authPanel . '.sales.index', [
                'offline_sale_id' => $offlineId,
            ]) : null;

            if ($affectedBy->sale->status === SaleStatus::VOID_SALE->value && 'N/A' !== $offlineId) {
                $route = null !== $authPanel ? route($authPanel . '.void_sales.index', [
                    'offline_sale_id' => $offlineId,
                ]) : null;
            }

            return [
                'message' => 'Sale: ' . $offlineId,
                'url' => $route,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::ORDER_ITEM->value) === $type) {
            /** @var Order $order */
            $order = $affectedBy->order;

            return [
                'message' => 'Wholesale Order: ' . $order->getReceiptNumber(),
                'url' => null,
            ];
        }

        return [
            'message' => 'Id: ' . $reservedStock->getAffectedById(),
            'url' => null,
        ];
    }

    public function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $stores = $locationQueries->getStoreWithBasicColumns($companyId);
        $warehouses = $locationQueries->getWithBasicColumnsOfWarehouse($companyId);

        return [$stores, $warehouses];
    }
}
