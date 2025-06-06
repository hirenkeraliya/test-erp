<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Domains\Inventory\Services\CancelOrderInventoryService;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\ReservedStock\Services\OrderReservedStockService;
use App\Models\Order;
use App\Models\StoreManager;

class CancelOrderService
{
    public function updateInventory(Order $order, StoreManager $storeManager, int $locationId): void
    {
        $cancelOrderInventoryService = resolve(CancelOrderInventoryService::class);

        foreach ($order->getOrderItems() as $orderItem) {
            if ($order->getTypeId() === OrderTypes::PENDING_LAYAWAY_ORDER) {
                $orderReservedStockService = resolve(OrderReservedStockService::class);
                $orderReservedStockService->revertReservedStock($orderItem);

                return;
            }

            foreach ($orderItem->getOrderItemUnits() as $orderItemUnit) {
                $cancelOrderInventoryService->addInventory(
                    $order,
                    $storeManager,
                    (float) $orderItemUnit->quantity,
                    $locationId,
                    $orderItem->product_id,
                    $orderItemUnit->purchase_amount_id,
                    $orderItemUnit->batch_id,
                );
            }
        }
    }
}
