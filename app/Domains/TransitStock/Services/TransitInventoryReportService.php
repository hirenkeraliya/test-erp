<?php

declare(strict_types=1);

namespace App\Domains\TransitStock\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\TransitStock\Resources\TransitInventoryReportListResource;
use App\Models\InventoryUpdate;
use App\Models\TransitStock;

class TransitInventoryReportService
{
    /**
     * Transform the resource into an array.
     */
    public function getTransitInventoryReportReferenceNumber(
        TransitInventoryReportListResource|TransitStock $transitInventoryResource,
        ?string $authPanel = null
    ): array {
        /** @var InventoryUpdate $transitStock */
        $transitStock = $transitInventoryResource;
        $type = $transitStock->affected_by_type;
        $affectedBy = $transitStock->affectedBy;

        if (ModelMapping::getCaseName(ModelMapping::STOCK_TRANSFER_ITEM->value) === $type) {
            $stockTransferNumber = $transitStock->quantity > 0 ? $affectedBy->stockTransfer->transfer_in_number : $affectedBy->stockTransfer->transfer_out_number;
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
            $purchaseOrderFulfillment = $affectedBy->purchaseOrderFulfillment;
            $deliveryOrderNumber = $purchaseOrderFulfillment->delivery_order_number;

            return [
                'message' => 'Delivery Order:' . $deliveryOrderNumber,
                'url' => null !== $deliveryOrderNumber && null !== $authPanel ? route(
                    $authPanel . '.purchase_order_fulfillments.delivery_orders',
                    [
                        'order_number' => $deliveryOrderNumber,
                    ]
                ) : null,
            ];
        }

        return [
            'message' => 'Id: ' . $transitStock->getAffectedById(),
            'url' => null,
        ];
    }

    public function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        return [$stores, $warehouses];
    }
}
