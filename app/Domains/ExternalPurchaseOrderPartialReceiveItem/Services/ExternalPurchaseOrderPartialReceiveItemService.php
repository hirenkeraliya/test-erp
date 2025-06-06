<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderPartialReceiveItem\Services;

use App\Domains\ExternalPurchaseOrderItem\ExternalPurchaseOrderItemQueries;
use App\Domains\ExternalPurchaseOrderPartialReceiveItem\ExternalPurchaseOrderPartialReceiveItemQueries;
use App\Domains\ExternalPurchaseOrderPartialReceiveItemBatch\ExternalPurchaseOrderPartialReceiveItemBatchQueries;
use App\Domains\ExternalPurchaseOrderReceive\DataObjects\ExternalPurchaseOrderReceiveData;
use App\Domains\ExternalPurchaseOrderReceive\ExternalPurchaseOrderReceiveQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderPartialReceive;
use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderPartialReceiveItemService
{
    public function addReceiveDetails(
        ExternalPurchaseOrderReceiveData $externalPurchaseOrderReceiveData,
        ExternalPurchaseOrderPartialReceive $externalPurchaseOrderReceive,
        Collection $batches
    ): void {
        $externalPurchaseOrderItemIds = collect($externalPurchaseOrderReceiveData->receive_items)->pluck(
            'external_purchase_order_item_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);

        $externalPurchaseOrderItems = $externalPurchaseOrderItemQueries->getByIds($externalPurchaseOrderItemIds);

        foreach ($externalPurchaseOrderReceiveData->receive_items as $externalPurchaseOrderReceiveItemData) {
            if (! array_key_exists('quantity_received', $externalPurchaseOrderReceiveItemData)) {
                continue;
            }

            if ($externalPurchaseOrderReceiveItemData['quantity_received'] <= 0) {
                continue;
            }

            $externalPurchaseOrderPartialReceiveItem = $externalPurchaseOrderPartialReceiveItemQueries->addNew([
                'external_purchase_order_partial_receive_id' => $externalPurchaseOrderReceive->id,
                'external_purchase_order_item_id' => $externalPurchaseOrderReceiveItemData['external_purchase_order_item_id'],
                'quantity_received' => $externalPurchaseOrderReceiveItemData['quantity_received'],
                'notes' => $externalPurchaseOrderReceiveItemData['notes'] ?? null,
                'unit_of_measure_derivative_id' => $externalPurchaseOrderReceiveItemData['unit_of_measure_derivative_id'],
            ]);

            $externalPurchaseOrderItem = $externalPurchaseOrderItems->firstWhere(
                'id',
                $externalPurchaseOrderReceiveItemData['external_purchase_order_item_id']
            );

            $externalPurchaseOrderItemQueries->updateReceivedQuantity(
                $externalPurchaseOrderItem,
                (float) $externalPurchaseOrderReceiveItemData['quantity_received']
            );

            if (array_key_exists('batch_details', $externalPurchaseOrderReceiveItemData)) {
                $this->updateBatches(
                    $externalPurchaseOrderPartialReceiveItem,
                    $batches,
                    $externalPurchaseOrderReceiveItemData['batch_details']
                );
            }
        }
    }

    public function updateReceiveDetails(
        ExternalPurchaseOrderReceiveData $externalPurchaseOrderReceiveData,
        ExternalPurchaseOrderPartialReceive $externalPurchaseOrderPartialReceive,
        Collection $batches,
    ): void {
        $externalPurchaseOrderPartialReceiveItems = $externalPurchaseOrderPartialReceive->items;

        $externalPurchaseOrderItemIds = collect($externalPurchaseOrderReceiveData->receive_items)->pluck(
            'external_purchase_order_item_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);

        $externalPurchaseOrderItems = $externalPurchaseOrderItemQueries->getByIds($externalPurchaseOrderItemIds);

        foreach ($externalPurchaseOrderReceiveData->receive_items as $externalPurchaseOrderReceiveItemData) {
            if (! array_key_exists('quantity_received', $externalPurchaseOrderReceiveItemData)) {
                continue;
            }

            if ($externalPurchaseOrderReceiveItemData['quantity_received'] <= 0) {
                continue;
            }

            $externalPurchaseOrderItem = $externalPurchaseOrderItems->firstWhere(
                'id',
                $externalPurchaseOrderReceiveItemData['external_purchase_order_item_id']
            );

            /** @var ExternalPurchaseOrderPartialReceiveItem $externalPurchaseOrderPartialReceiveItem */
            $externalPurchaseOrderPartialReceiveItem = $externalPurchaseOrderPartialReceiveItems->firstWhere(
                'id',
                $externalPurchaseOrderReceiveItemData['id']
            );

            if (array_key_exists(
                'id',
                $externalPurchaseOrderReceiveItemData
            ) && $externalPurchaseOrderReceiveItemData['id'] > 0) {
                $externalPurchaseOrderItemQueries->updateReceivedQuantity(
                    $externalPurchaseOrderItem,
                    (float) $externalPurchaseOrderReceiveItemData['quantity_received'] - $externalPurchaseOrderItem->received_quantity
                );

                $externalPurchaseOrderPartialReceiveItemQueries->update(
                    $externalPurchaseOrderPartialReceiveItem,
                    [
                        'external_purchase_order_partial_receive_id' => $externalPurchaseOrderPartialReceive->id,
                        'external_purchase_order_item_id' => $externalPurchaseOrderReceiveItemData['external_purchase_order_item_id'],
                        'quantity_received' => $externalPurchaseOrderReceiveItemData['quantity_received'],
                        'unit_of_measure_derivative_id' => $externalPurchaseOrderReceiveItemData['unit_of_measure_derivative_id'],
                        'notes' => $externalPurchaseOrderReceiveItemData['notes'] ?? null,
                    ]
                );

                if ((float) $externalPurchaseOrderReceiveItemData['quantity_received'] === 0.0) {
                    $this->removeExternalPurchaseOrderPartialReceiveItem($externalPurchaseOrderPartialReceiveItem);
                    continue;
                }

                if (array_key_exists('batch_details', $externalPurchaseOrderReceiveItemData)) {
                    $this->updateBatches(
                        $externalPurchaseOrderPartialReceiveItem,
                        $batches,
                        $externalPurchaseOrderReceiveItemData['batch_details']
                    );
                }

                continue;
            }

            $externalPurchaseOrderPartialReceiveItem = $externalPurchaseOrderPartialReceiveItemQueries->addNew([
                'external_purchase_order_partial_receive_id' => $externalPurchaseOrderPartialReceive->id,
                'external_purchase_order_item_id' => $externalPurchaseOrderReceiveItemData['external_purchase_order_item_id'],
                'quantity_received' => $externalPurchaseOrderReceiveItemData['quantity_received'],
                'notes' => $externalPurchaseOrderReceiveItemData['notes'] ?? null,
            ]);

            $externalPurchaseOrderItem = $externalPurchaseOrderItems->firstWhere(
                'id',
                $externalPurchaseOrderReceiveItemData['external_purchase_order_item_id']
            );

            $externalPurchaseOrderItemQueries->updateReceivedQuantity(
                $externalPurchaseOrderItem,
                (float) $externalPurchaseOrderReceiveItemData['quantity_received']
            );

            if (array_key_exists('batch_details', $externalPurchaseOrderReceiveItemData)) {
                $this->updateBatches(
                    $externalPurchaseOrderPartialReceiveItem,
                    $batches,
                    $externalPurchaseOrderReceiveItemData['batch_details']
                );
            }
        }
    }

    public function removeExternalPurchaseOrderPartialReceiveItem(
        ExternalPurchaseOrderPartialReceiveItem $externalPurchaseOrderPartialReceiveItem
    ): void {
        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );
        $externalPurchaseOrderPartialReceiveItemQueries->removeItemAndRelations(
            $externalPurchaseOrderPartialReceiveItem
        );
    }

    public function updateBatches(
        ExternalPurchaseOrderPartialReceiveItem $externalPurchaseOrderPartialReceiveItem,
        Collection $batches,
        array $batchDetails,
    ): void {
        $externalPurchaseOrderPartialReceiveItemBatchQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemBatchQueries::class
        );

        $externalPurchaseOrderPartialReceiveItemBatchQueries->deleteByExternalPurchaseOrderPartialReceiveItem(
            $externalPurchaseOrderPartialReceiveItem->id
        );

        foreach ($batchDetails as $batchDetail) {
            $externalPurchaseOrderPartialReceiveItemBatchQueries->addNew([
                'external_purchase_order_partial_receive_item_id' => $externalPurchaseOrderPartialReceiveItem->id,
                'batch_number' => $batchDetail['batch_number'],
                'expiry_date' => $batchDetail['expiry_date'],
                'quantity' => $batchDetail['quantity'],
                'notes' => $batchDetail['notes'],
            ]);
        }
    }

    public function markAsCancel(ExternalPurchaseOrderPartialReceive $purchaseOrderPartialReceive, int $status): void
    {
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $partialReceiveItems = $purchaseOrderPartialReceive->items;
        foreach ($partialReceiveItems as $partialReceiveItem) {
            /** @var ExternalPurchaseOrderItem $externalPurchaseOrderItem */
            $externalPurchaseOrderItem = $partialReceiveItem->externalPurchaseOrderItem;

            $externalPurchaseOrderItemQueries->decreaseItemQuantity(
                $externalPurchaseOrderItem,
                (float) $partialReceiveItem['quantity_received']
            );
        }

        $externalPurchaseOrderReceiveQueries->updateStatus($purchaseOrderPartialReceive, $status);
    }

    public function checkAllItemsReceived(Collection $purchaseOrderItems, string $routeUrl): void
    {
        $purchaseOrderItems = $purchaseOrderItems->filter(
            fn ($purchaseOrderItem): bool => ($purchaseOrderItem->quantity - $purchaseOrderItem->received_quantity) > 0
        );

        if ($purchaseOrderItems->isNotEmpty()) {
            return;
        }

        throw new RedirectWithErrorException(
            $routeUrl,
            'All items that were to be added to the External Purchase Order partial Receive have already been included.'
        );
    }
}
