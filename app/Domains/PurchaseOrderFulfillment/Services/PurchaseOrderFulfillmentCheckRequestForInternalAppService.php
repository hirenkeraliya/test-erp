<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Services;

use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentStoreForStoreManagerData;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentStoreForWarehouseManagerData;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Collection;

class PurchaseOrderFulfillmentCheckRequestForInternalAppService
{
    public function checkRequestDetails(
        PurchaseOrderFulfillmentStoreForStoreManagerData|PurchaseOrderFulfillmentStoreForWarehouseManagerData $purchaseOrderFulfillmentStoreData,
        Collection $products,
        Collection $inventories,
        Collection $batches,
    ): void {
        $transferItems = $purchaseOrderFulfillmentStoreData->transfer_items;
        if (collect($transferItems)->sum('transfer_quantity') <= 0) {
            abort(412, 'Please ensure at least one transfer quantity is requested for adding to the Delivery Order.'
            );
        }

        foreach ($transferItems as $transferItem) {
            if ($transferItem['transfer_quantity'] <= 0) {
                continue;
            }

            $product = $products->firstWhere('id', $transferItem['product_id']);

            if (! $product instanceof Product) {
                abort(412, 'product ID: ' . $transferItem['product_id'] . ' is not available in our records.');
            }

            $inventory = $inventories->firstWhere('product_id', $transferItem['product_id']);

            if (! $inventory instanceof Inventory) {
                abort(412, 'product (UPC - ' . $product->upc . ') source stock is not available.');
            }

            $finalStock = (float) $inventory->stock;

            if ($transferItem['transfer_quantity'] > $finalStock) {
                abort(
                    412,
                    'Transfer stock (' . $transferItem['transfer_quantity'] . ') of the product (UPC - ' . $product->upc . ') cannot be more than the current source stock (' . $finalStock . ').'
                );
            }

            if (array_key_exists('package_total_quantity', $transferItem) &&
                (float) ($transferItem['package_total_quantity'] * $transferItem['package_quantity']) !== (float) $transferItem['transfer_quantity']
            ) {
                abort(
                    412,
                    'The quantity of stock being transferred does not match the total quantity indicated in the package. Please verify and ensure that the correct quantity is being transferred to avoid errors and discrepancies.'
                );
            }

            if (! $product->has_batch) {
                continue;
            }

            /** @var Collection $batchDetails */
            $batchDetails = collect([$transferItem['batch_details']]);

            if ((float) $batchDetails->sum('quantity') !== (float) $transferItem['transfer_quantity']) {
                abort(412, 'Stock transfer quantity does not match with the batch details quantity.');
            }

            $this->validateBatchNumber($batchDetails, $batches, $product->id);
        }
    }

    private function validateBatchNumber(Collection $batchDetails, Collection $batches, int $productId): void
    {
        $batchNumbers = $batchDetails->pluck('batch_number')->unique()->filter();

        $matchedBatches = $batches->where('product_id', $productId)
            ->whereIn('number', $batchNumbers->toArray());

        if ($matchedBatches->count() !== $batchNumbers->count()) {
            abort(412, 'Some of the batch numbers do not match with our records.');
        }
    }
}
