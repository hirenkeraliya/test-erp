<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderPartialReceiveItem\Services;

use App\Domains\ExternalPurchaseOrderReceive\DataObjects\ExternalPurchaseOrderReceiveData;
use App\Exceptions\RedirectBackWithErrorException;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderPartialReceiveItemCheckRequestService
{
    public function checkRequestDetails(
        ExternalPurchaseOrderReceiveData $externalPurchaseOrderReceiveData,
        Collection $products,
        Collection $batches,
    ): void {
        $receiveItems = $externalPurchaseOrderReceiveData->receive_items;
        if (collect($receiveItems)->sum('quantity_received') <= 0) {
            throw new RedirectBackWithErrorException(
                'Please ensure at least one received quantity is requested for adding to the External Purchase Order Receive.'
            );
        }

        foreach ($receiveItems as $receiveItem) {
            $receivedQuantity = $receiveItem['quantity_received'];

            if ($receivedQuantity <= 0) {
                continue;
            }

            $product = $products->firstWhere('id', $receiveItem['product_id']);

            if (! $product) {
                throw new RedirectBackWithErrorException(
                    'product ID: ' . $receiveItem['product_id'] . ' is not available.'
                );
            }

            /** @var array<array> $batchDetails */
            $batchDetails = $receiveItem['batch_details'];
            $batchDetails = collect($batchDetails);

            if ($receiveItem['product_has_batch']) {
                $totalBatchQuantity = $batchDetails->sum(fn ($item): int => (int) $item['quantity']);

                if ((int) $receiveItem['quantity_received'] !== $totalBatchQuantity) {
                    throw new RedirectBackWithErrorException(
                        'The total batch quantity does not match the received quantity.'
                    );
                }
            }

            $this->validateBatchNumber($batchDetails, $batches, $product->id);
        }
    }

    public function validateBatchNumber(Collection $batchDetails, Collection $batches, int $productId): void
    {
        $batchNumbers = $batchDetails->pluck('batch_number')->unique()->filter();

        $batchExpiryDetails = $batchDetails->mapWithKeys(fn ($batch) => [
            $batch['batch_number'] => $batch['expiry_date'],
        ]);

        $matchedBatches = $batches->where('product_id', $productId)
            ->whereIn('number', $batchNumbers->toArray());

        foreach ($matchedBatches as $matchedBatch) {
            $batchNumber = $matchedBatch->number;
            $expectedExpiryDate = $batchExpiryDetails[$batchNumber] ?? null;
            if (! $expectedExpiryDate || $matchedBatch->expiry_date !== $expectedExpiryDate) {
                throw new RedirectBackWithErrorException(sprintf(
                    'Batch number %s has an expiry date mismatch.',
                    $batchNumber
                ));
            }
        }
    }
}
