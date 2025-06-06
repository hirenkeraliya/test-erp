<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderReceive\Services;

use App\Domains\ExternalPurchaseOrderReceive\DataObjects\ExternalPurchaseOrderReceiveData;
use App\Domains\ExternalPurchaseOrderReceive\Enums\Statuses;
use App\Domains\ExternalPurchaseOrderReceive\ExternalPurchaseOrderReceiveQueries;
use App\Models\ExternalPurchaseOrder;
use App\Models\ExternalPurchaseOrderPartialReceive;

class ExternalPurchaseOrderReceiveService
{
    public function saveExternalPurchaseOrderReceive(
        ExternalPurchaseOrderReceiveData $externalPurchaseOrderReceiveData,
        ExternalPurchaseOrder $externalPurchaseOrder
    ): ExternalPurchaseOrderPartialReceive {
        $data = $externalPurchaseOrderReceiveData->all();

        unset($data['receive_items']);
        $data['status'] = Statuses::PENDING->value;
        $data['external_purchase_order_id'] = $externalPurchaseOrder->id;
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);

        return $externalPurchaseOrderReceiveQueries->addNew($data);
    }

    public function updateExternalPurchaseOrderReceive(
        ExternalPurchaseOrderReceiveData $externalPurchaseOrderReceiveData,
        int $externalPurchaseOrderPartialReceiveId
    ): void {
        $data = $externalPurchaseOrderReceiveData->all();

        unset($data['receive_items']);
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);
        $externalPurchaseOrderReceiveQueries->update($data, $externalPurchaseOrderPartialReceiveId);
    }

    public function purchasePlanMarkAsCompleted(
        ExternalPurchaseOrderPartialReceive $externalPurchaseOrderPartialReceive,
    ): void {
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);
        $externalPurchaseOrderReceiveQueries->updateStatus(
            $externalPurchaseOrderPartialReceive,
            Statuses::COMPLETED->value
        );
    }

    public function hasPartialReceiveItems(ExternalPurchaseOrder $externalPurchaseOrder): bool
    {
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);
        $externalPurchaseOrderReceives = $externalPurchaseOrderReceiveQueries->getByExternalPurchaseOrderId(
            $externalPurchaseOrder->id
        );

        if ($externalPurchaseOrderReceives->isNotEmpty()) {
            $items = $externalPurchaseOrder->items;
            $externalItemTotalQuantity = $items->sum('quantity');
            $partialItemTotalQuantity = 0;

            foreach ($externalPurchaseOrderReceives as $externalPurchaseOrderReceive) {
                $receiveItems = $externalPurchaseOrderReceive->items;
                $partialItemTotalQuantity += $receiveItems->sum('quantity_received');
            }

            return $externalItemTotalQuantity === $partialItemTotalQuantity;
        }

        return false;
    }
}
