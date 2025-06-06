<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\InventoryUpdate\Resources\AdminStockMovementLedgerReportListResource;
use App\Domains\InventoryUpdate\Resources\StoreManagerStockMovementLedgerReportListResource;
use App\Domains\InventoryUpdate\Resources\WarehouseManagerStockMovementLedgerReportListResource;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Models\InventoryUpdate;
use App\Models\StockAdjustment;
use Carbon\Carbon;

class StockMovementLedgerReportService
{
    /**
     * Transform the resource into an array.
     */
    public function getStockMovementLedgerReportReferenceNumber(
        AdminStockMovementLedgerReportListResource|StoreManagerStockMovementLedgerReportListResource|WarehouseManagerStockMovementLedgerReportListResource|InventoryUpdate $inventoryResource,
        ?string $authPanel = null
    ): array {
        /** @var InventoryUpdate $inventoryUpdate */
        $inventoryUpdate = $inventoryResource;

        $type = $inventoryUpdate->affected_by_type;
        $affectedBy = $inventoryUpdate->affectedBy;

        if (ModelMapping::getCaseName(ModelMapping::STOCK_TRANSFER_ITEM->value) === $type) {
            $stockTransferNumber = $inventoryUpdate->quantity > 0 ? $affectedBy->stockTransfer->transfer_in_number : $affectedBy->stockTransfer->transfer_out_number;

            return [
                'message' => 'Stock Transfer: ' . $stockTransferNumber,
                'url' => null !== $stockTransferNumber && null !== $authPanel ? route(
                    $authPanel . '.stock_transfers.index',
                    [
                        'stock_transfer_number' => $stockTransferNumber,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::ORDER_ITEM->value) === $type) {
            $happenedAt = $affectedBy->order->happened_at
                ? Carbon::createFromFormat('Y-m-d H:i:s', $affectedBy->order->happened_at) ?: Carbon::parse(
                    $affectedBy->order->happened_at
                )
                : Carbon::now();

            $happenedAt = $happenedAt->format('Y-m-d');

            return [
                'message' => 'Marketplace Order: ' . $affectedBy->order->receipt_number,
                'url' => route(
                    $authPanel . '.orders.marketplaces_orders',
                    [
                        'receipt_number' => $affectedBy->order->receipt_number,
                        'date_range' => [
                            CommonFunctions::addStartTime($happenedAt),
                            CommonFunctions::addEndTime($happenedAt),
                        ],
                    ]
                ),
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::MERGE_PRODUCT_TRANSACTION->value) === $type) {
            $mergeTransaction = $affectedBy->id;

            return [
                'message' => 'Merge Transaction: ' . $mergeTransaction,
                'url' => null,
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

        if (ModelMapping::getCaseName(
            ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->value
        ) === $type && null !== $affectedBy) {
            $grnNumber = ($affectedBy->goodsReceivedNote?->grn_reference ?: 'N/A');

            return [
                'message' => 'GRN: ' . $grnNumber,
                'url' => 'N/A' !== $grnNumber && null !== $authPanel ? route(
                    $authPanel . '.goods_received_notes.index',
                    [
                        'grn_number' => $grnNumber,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::SALE_RETURN_ITEM->value) === $type) {
            $saleReturnOfflineId = ($affectedBy->saleReturn->offline_sale_return_id ?: 'N/A');

            return [
                'message' => 'Sale Return: ' . $saleReturnOfflineId,
                'url' => 'N/A' !== $saleReturnOfflineId && null !== $authPanel ? route(
                    $authPanel . '.sale_returns.index',
                    [
                        'offline_sale_return_id' => $saleReturnOfflineId,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::STOCK_ADJUSTMENT_ITEM->value) === $type) {
            /** @var StockAdjustment $stockAdjustment */
            $stockAdjustment = $affectedBy->stockAdjustment;
            $reason = $stockAdjustment->reason;
            $type = StockAdjustmentTypes::STI->value === $stockAdjustment->type_id ? 'STI: ' : 'STO: ';

            return [
                'message' => 'Stock Adjustment: ' . $type . $reason . '(' . $stockAdjustment->id . ')',
                'url' => null !== $stockAdjustment->id && null !== $authPanel ? route(
                    $authPanel . '.stock_adjustments.index',
                    [
                        'stock_adjustment_id' => $stockAdjustment->id,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::VOID_SALE->value) === $type) {
            $voidSaleOfflineId = ($affectedBy->void_sale_number ?: 'N/A');

            return [
                'message' => 'Void Sale: ' . $voidSaleOfflineId,
                'url' => 'N/A' !== $voidSaleOfflineId && null !== $authPanel ? route($authPanel . '.void_sales.index', [
                    'void_sale_number' => $voidSaleOfflineId,
                ]) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->value) === $type) {
            $purchaseOrderFulfillment = $affectedBy->purchaseOrderFulfillment;
            $deliveryOrderNumber = $purchaseOrderFulfillment->delivery_order_number;

            $discrepancy = '';
            if ($affectedBy->discrepancy_type) {
                $discrepancy = ' (Discrepancy)';
            }

            return [
                'message' => 'Delivery Order:' . $deliveryOrderNumber . $discrepancy,
                'url' => null !== $deliveryOrderNumber && null !== $authPanel ? route(
                    $authPanel . '.purchase_order_fulfillments.delivery_orders',
                    [
                        'order_number' => $deliveryOrderNumber,
                    ]
                ) : null,
            ];
        }

        if (ModelMapping::getCaseName(ModelMapping::PARTIALLY_RECEIVE_FULFILLMENT_ITEM->value) === $type) {
            $purchaseOrderFulfillmentItem = $affectedBy->purchaseOrderFulfillmentItem;
            $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;
            $deliveryOrderNumber = $purchaseOrderFulfillment->delivery_order_number;
            $partiallyReceiveNumber = $affectedBy->partiallyReceiveFulfillment->partially_receive_number;

            $discrepancy = '';
            if ($purchaseOrderFulfillmentItem->discrepancy_type) {
                $discrepancy = ' (Discrepancy)';
            }

            $message = 'Delivery Order:' . $deliveryOrderNumber . $discrepancy;
            if ($partiallyReceiveNumber) {
                $message .= ', Partially Receive Number:.' . $partiallyReceiveNumber;
            }

            return [
                'message' => $message,
                'url' => null !== $deliveryOrderNumber && null !== $authPanel ? route(
                    $authPanel . '.purchase_order_fulfillments.delivery_orders',
                    [
                        'order_number' => $deliveryOrderNumber,
                    ]
                ) : null,
            ];
        }

        return [
            'message' => 'Id: ' . $inventoryUpdate->getAffectedById(),
            'url' => null,
        ];
    }
}
