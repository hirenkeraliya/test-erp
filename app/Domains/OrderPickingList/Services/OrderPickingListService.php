<?php

declare(strict_types=1);

namespace App\Domains\OrderPickingList\Services;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderPickingList\OrderPickingListQueries;
use App\Services\EcommerceIntegrationService;

class OrderPickingListService
{
    public function checkOrderPickingList(array $orderIds): void
    {
        $orderQueries = resolve(OrderQueries::class);
        $orderCount = $orderQueries->getCountByIdsAndStatus($orderIds, OrderStatus::PACKING);
        if ($orderCount > 0) {
            abort(412, 'Some of the orders status are not Accepted.');
        }
    }

    public function addOrderPickingList(array $orderIds, int $companyId): void
    {
        $orderPickingListQueries = resolve(OrderPickingListQueries::class);
        $ecommerceIntegrationService = resolve(EcommerceIntegrationService::class);

        $orderPickingListQueries->addNew($orderIds, $companyId);

        $orderQueries = resolve(OrderQueries::class);
        $orders = $orderQueries->getByIdsWithLoadRelationsForShipment($orderIds);
        foreach ($orders as $order) {
            $orderQueries->statusUpdate($order, OrderStatus::PACKING);
            $ecommerceIntegrationService->createShipment($order);
        }
    }
}
