<?php

declare(strict_types=1);

namespace App\Domains\PartiallyReceiveFulfillment\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\PartiallyReceiveFulfillment\Enums\PartiallyReceiveFulfillmentStatuses;
use App\Domains\PartiallyReceiveFulfillment\PartiallyReceiveFulfillmentQueries;
use App\Domains\PartiallyReceiveFulfillmentItem\PartiallyReceiveFulfillmentItemQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\PartiallyReceiveFulfillment;
use App\Models\PurchaseOrderFulfillment;
use Illuminate\Foundation\Auth\User;

class PartiallyReceiveFulfillmentService
{
    public function addPartialReceive(
        User $user,
        int $purchaseOrderFulfillmentId,
        int $locationId,
        array $partialReceiveItems
    ): void {
        $partialReceiveItems = collect($partialReceiveItems);
        if (($partialReceiveItems->sum('received_quantity') - $partialReceiveItems->sum('partial_received')) <= 0) {
            return;
        }

        $sequenceQueries = resolve(SequenceQueries::class);
        $sequence = $sequenceQueries->addNew($locationId, SequenceTypes::SODOPR->value);

        $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
        $partialReceiveFulfillment = $partiallyReceiveFulfillmentQueries->addNew([
            'purchase_order_fulfillment_id' => $purchaseOrderFulfillmentId,
            'received_by_user_id' => $user->id,
            'received_by_user_type' => ModelMapping::getCaseName($user::class),
            'status' => PartiallyReceiveFulfillmentStatuses::DRAFT->value,
            'partially_receive_number' => $sequence->getCompleteNumber(),
        ]);

        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        foreach ($partialReceiveItems as $partialReceiveItem) {
            $partialReceivedQuantity = ($partialReceiveItem['received_quantity'] - $partialReceiveItem['partial_received']);
            if ($partialReceivedQuantity > 0) {
                $partiallyReceiveFulfillmentItemQueries->addNew([
                    'partially_receive_fulfillment_id' => $partialReceiveFulfillment->id,
                    'purchase_order_fulfillment_item_id' => $partialReceiveItem['id'],
                    'received_quantity' => $partialReceivedQuantity,
                ]);
            }
        }
    }

    public function addPartialByDO(
        User $user,
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        int $locationId
    ): ?PartiallyReceiveFulfillment {
        $partialReceivedQuantity = $purchaseOrderFulfillment->items
            ->flatMap(fn ($item) => $item->partialReceivedItems)
            ->sum('received_quantity');

        if ($partialReceivedQuantity >= $purchaseOrderFulfillment->items->sum('received_quantity')) {
            return null;
        }

        $sequenceQueries = resolve(SequenceQueries::class);
        $sequence = $sequenceQueries->addNew($locationId, SequenceTypes::SODOPR->value);

        $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
        $partiallyReceiveFulfillment = $partiallyReceiveFulfillmentQueries->addNew([
            'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
            'received_by_user_id' => $user->id,
            'received_by_user_type' => ModelMapping::getCaseName($user::class),
            'status' => PartiallyReceiveFulfillmentStatuses::COMPLETED->value,
            'partially_receive_number' => $sequence->getCompleteNumber(),
        ]);

        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        foreach ($purchaseOrderFulfillment->items as $purchaseOrderFulfillmentItem) {
            $partialReceivedQuantity = ($purchaseOrderFulfillmentItem->received_quantity - $purchaseOrderFulfillmentItem->partialReceivedItems->sum(
                'received_quantity'
            ));
            if ($partialReceivedQuantity > 0) {
                $partiallyReceiveFulfillmentItemQueries->addNew([
                    'partially_receive_fulfillment_id' => $partiallyReceiveFulfillment->id,
                    'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
                    'received_quantity' => $partialReceivedQuantity,
                ]);
            }
        }

        return $partiallyReceiveFulfillment;
    }
}
