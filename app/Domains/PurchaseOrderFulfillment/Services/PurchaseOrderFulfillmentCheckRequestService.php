<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Services;

use App\CommonFunctions;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentData;
use App\Domains\PurchaseOrderFulfillmentItem\Enums\DiscrepancyTypes;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Batch;
use App\Models\Product;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Support\Collection;

class PurchaseOrderFulfillmentCheckRequestService
{
    public function checkRequestDetails(
        PurchaseOrderFulfillmentData $purchaseOrderFulfillmentData,
        Collection $products,
        Collection $inventories,
        Collection $batches,
        Collection $derivatives,
        Collection $purchaseOrderItems,
    ): void {
        $transferItems = $purchaseOrderFulfillmentData->transfer_items;
        if (collect($transferItems)->sum('transfer_quantity') <= 0) {
            throw new RedirectBackWithErrorException(
                'Please ensure at least one transfer quantity is requested for adding to the Delivery Order.'
            );
        }

        foreach ($transferItems as $transferItem) {
            $transferQuantity = $transferItem['transfer_quantity'];

            if ($transferQuantity <= 0) {
                continue;
            }

            $product = $products->firstWhere('id', $transferItem['product_id']);

            $purchaseOrderItem = $purchaseOrderItems->firstWhere('id', $transferItem['purchase_order_item_id']);

            if (! $product) {
                throw new RedirectBackWithErrorException(
                    'product ID: ' . $transferItem['product_id'] . ' is not available.'
                );
            }

            $derivative = null;
            if ($purchaseOrderItem->unit_of_measure_derivative_id) {
                $derivative = $derivatives->firstWhere('id', $purchaseOrderItem->unit_of_measure_derivative_id);

                if (! $derivative) {
                    throw new RedirectBackWithErrorException(
                        'derivative ID: ' . $purchaseOrderItem->unit_of_measure_derivative_id . ' is not available.'
                    );
                }

                $unitOfMeasureId = config(
                    'app.product_variant'
                ) ? $product->masterProduct->unit_of_measure_id : $product->unit_of_measure_id;

                if ($derivative->unit_of_measure_id !== $unitOfMeasureId) {
                    throw new RedirectBackWithErrorException(
                        'product (UPC - ' . $product->upc . ') UOM does not match with selected derivate.'
                    );
                }
            }

            $inventory = $inventories->firstWhere('product_id', $transferItem['product_id']);

            if (! $inventory) {
                throw new RedirectBackWithErrorException(
                    'product (UPC - ' . $product->upc . ') source stock is not available.'
                );
            }

            if (
                array_key_exists('package_total_quantity', $transferItem)
                && (null !== $transferItem['package_total_quantity'] && null !== $transferItem['package_quantity'])
                && ! CommonFunctions::compareFloatNumbers(
                    CommonFunctions::numberFormat((float) ($transferQuantity / $transferItem['package_quantity'])),
                    (float) $transferItem['package_total_quantity']
                )
            ) {
                throw new RedirectBackWithErrorException(
                    'The quantity of stock being transferred does not match the total quantity indicated in the package. Please verify and ensure that the correct quantity is being transferred to avoid errors and discrepancies.'
                );
            }

            $hasBatch = config('app.product_variant') ? $product->masterProduct->has_batch : $product->has_batch;

            if (! $hasBatch) {
                continue;
            }

            /** @var array<array> $batchDetails */
            $batchDetails = $transferItem['batch_details'];
            $batchDetails = collect($batchDetails);

            if ((float) $batchDetails->sum('quantity') !== (float) $transferQuantity) {
                throw new RedirectBackWithErrorException(
                    'Stock transfer quantity does not match with the batch details quantity.'
                );
            }

            $this->validateBatchNumber($batchDetails, $batches, $product->id);
            $this->validateBatchInventory($product, $inventory->inventoryUnits, $batches, $batchDetails, $derivative);
        }
    }

    public function getReservedQuantity(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        int $productId
    ): float {
        $purchaseOrderFulfillmentItem = $purchaseOrderFulfillment->items->firstWhere('product_id', $productId);
        if ($purchaseOrderFulfillmentItem) {
            $reservedStockQueries = resolve(ReservedStockQueries::class);

            return $reservedStockQueries->getByAffectedBy($purchaseOrderFulfillmentItem)
                ->sum('quantity');
        }

        return 0.00;
    }

    public function validateBatchNumber(Collection $batchDetails, Collection $batches, int $productId): void
    {
        $batchNumbers = $batchDetails->pluck('batch_number')->unique()->filter();

        $matchedBatches = $batches->where('product_id', $productId)
            ->whereIn('number', $batchNumbers->toArray());

        if ($matchedBatches->count() !== $batchNumbers->count()) {
            throw new RedirectBackWithErrorException('Some of the batch numbers do not match with our records.');
        }
    }

    public function validateBatchInventory(
        Product $product,
        Collection $inventoryUnits,
        Collection $batches,
        Collection $batchDetails,
        ?UnitOfMeasureDerivative $derivative,
    ): void {
        foreach ($batchDetails as $batchDetail) {
            /** @var Batch $batch */
            $batch = $batches->firstWhere('number', $batchDetail['batch_number']);
            $batchInventoryUnits = $inventoryUnits->where('batch_id', $batch->id);
            $currentBatchStock = $batchInventoryUnits->sum('quantity');

            $batchQuantity = (float) $batchDetail['quantity'];

            if ($derivative instanceof UnitOfMeasureDerivative && $derivative->ratio > 0) {
                $batchQuantity /= (float) $derivative->ratio;
            }

            if ($currentBatchStock < $batchQuantity) {
                throw new RedirectBackWithErrorException(
                    'You cannot do stock transfer for the specified product (name: ' . $product->compound_product_name . ') because the current batch units at the source location are ' . $currentBatchStock . ' only but you requested to transfer extra received ' . $batchDetail['quantity'] . ' units.'
                );
            }
        }
    }

    public function checkAdditionalItemsRequest(
        array $requestData,
        Collection $products,
        Collection $batches,
        PurchaseOrderFulfillment $purchaseOrderFulfillment
    ): void {
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;
        foreach ($requestData['additional_items'] as $transferItem) {
            $requestProductId = $transferItem['product_id'];
            $product = $products->firstWhere('id', $requestProductId);

            if (! $product) {
                throw new RedirectBackWithErrorException('product ID: ' . $requestProductId . ' is not available.');
            }

            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItems->firstWhere('product_id', $requestProductId);
            if ($purchaseOrderFulfillmentItem) {
                throw new RedirectBackWithErrorException(
                    'The product with UPC:' . $product->upc .' is all ready in delivery notes. Please increase received quantity'
                );
            }

            if (
                array_key_exists('package_total_quantity', $transferItem)
                && (null !== $transferItem['package_total_quantity'] && null !== $transferItem['package_quantity'])
                && ! CommonFunctions::compareFloatNumbers(
                    CommonFunctions::numberFormat(
                        (float) $transferItem['received_quantity'] / $transferItem['package_quantity']
                    ),
                    (float) $transferItem['package_total_quantity']
                )
            ) {
                throw new RedirectBackWithErrorException(
                    'The quantity of stock being transferred does not match the total quantity indicated in the package. Please verify and ensure that the correct quantity is being transferred to avoid errors and discrepancies.'
                );
            }

            $hasBatch = config('app.product_variant') ? $product->masterProduct->has_batch : $product->has_batch;

            if (! $hasBatch) {
                continue;
            }

            /** @var array<array> $batchDetails */
            $batchDetails = $transferItem['batch_details'];
            $batchDetails = collect($batchDetails);

            if ((float) $batchDetails->sum('quantity') !== (float) $transferItem['received_quantity']) {
                throw new RedirectBackWithErrorException(
                    'Stock transfer quantity does not match with the batch details quantity.'
                );
            }

            $this->validateBatchNumber($batchDetails, $batches, $product->id);
        }
    }

    public function checkClosingDiscrepancyRequestBatchDetails(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        array $validatedData,
        Collection $products,
        Collection $inventories,
        Collection $batches,
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->getItems();
        foreach ($validatedData['transfer_items'] as $transferItem) {
            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItems->firstWhere('id', $transferItem['id']);

            $product = $products->firstWhere('id', $purchaseOrderFulfillmentItem->product_id);

            $exceedQuantity = $purchaseOrderFulfillmentItem->received_quantity - $purchaseOrderFulfillmentItem->transfer_quantity;
            $inventory = $inventories->firstWhere('product_id', $product->id);

            if ($exceedQuantity <= 0) {
                continue;
            }

            $hasBatch = config('app.product_variant') ? $product->masterProduct->has_batch : $product->has_batch;

            if (! $hasBatch) {
                $currentStock = $inventory->stock;

                if ($currentStock < $exceedQuantity) {
                    throw new RedirectBackWithErrorException(
                        'You cannot close this discrepancy stock transfer because the current stock of the product (name: ' . $product->compound_product_name . ') at the source location is ' . $currentStock . ' only but you have decided to keep the extra quantity received which is ' . $exceedQuantity . ' units.'
                    );
                }

                continue;
            }

            /** @var array<array> $batchDetails */
            $batchDetails = $transferItem['batch_details'];
            $batchDetails = collect($batchDetails)
                ->whereNotNull('batch_number')
                ->whereNotNull('quantity');

            $this->validateBatchNumber($batchDetails, $batches, $product->id);

            if ($purchaseOrderFulfillmentItem->received_quantity > $purchaseOrderFulfillmentItem->transfer_quantity &&
                $purchaseOrderFulfillmentItem->discrepancy_type === DiscrepancyTypes::POSITIVE->value
            ) {
                if ((float) $batchDetails->sum('quantity') !== (float) $exceedQuantity) {
                    throw new RedirectBackWithErrorException(
                        'You must provide batch details for all the extra received quantity.'
                    );
                }

                foreach ($batchDetails as $batchDetail) {
                    /** @var Batch $batch */
                    $batch = $batches->firstWhere('number', $batchDetail['batch_number']);
                    $batchInventoryUnits = $inventoryUnitQueries->getByInventoryBatchId($inventory->id, $batch->id);
                    $currentBatchStock = $batchInventoryUnits->sum('quantity');

                    if ($currentBatchStock < $exceedQuantity) {
                        throw new RedirectBackWithErrorException(
                            'You cannot do stock transfer for the specified product (name: ' . $product->compound_product_name . '). Because the current batch units at the source location are ' . $currentBatchStock . ' only, you requested to transfer exceed received ' . $exceedQuantity . ' units.'
                        );
                    }
                }
            }
        }
    }

    public function checkStock(
        Collection $purchaseOrderFulfillmentItems,
        Collection $products,
        Collection $inventories,
        Collection $batches,
    ): void {
        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            $product = $products->firstWhere('id', $purchaseOrderFulfillmentItem->product_id);

            $inventory = $inventories->firstWhere('product_id', $product->id);

            if (! $inventory) {
                throw new RedirectBackWithErrorException(
                    'Transfer stock (' . $purchaseOrderFulfillmentItem->transfer_quantity . ') of the product (UPC - ' . $product->upc . ') cannot be more than the current source stock (0).'
                );
            }

            $hasBatch = config('app.product_variant') ? $product->masterProduct->has_batch : $product->has_batch;

            if ($hasBatch) {
                continue;
            }

            $currentStock = $inventory->stock;

            if ($currentStock < $purchaseOrderFulfillmentItem->transfer_quantity) {
                throw new RedirectBackWithErrorException(
                    'Transfer stock (' . $purchaseOrderFulfillmentItem->transfer_quantity . ') of the product (UPC - ' . $product->upc . ') cannot be more than the current source stock (' . $currentStock . ').'
                );
            }
        }
    }

    public function checkBatchDetailsRequest(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        Product $product,
        Collection $batchDetails,
        Collection $externalBatches,
        Collection $externalBatchInventoryUnits,
        array $externalProduct
    ): void {
        if ((float) $purchaseOrderFulfillmentItem->received_quantity !== (float) $batchDetails->sum(
            'received_quantity'
        )) {
            abort(412, 'Transferred quantity does not match with the batch details quantity.');
        }

        if ($externalBatches->count() !== $batchDetails->count()) {
            abort(412, 'Some of the batch numbers do not match with our records.');
        }

        if (config('app.product_variant')) {
            $unitOfMeasure = $externalProduct['master_product']['unit_of_measure'];
        } else {
            $unitOfMeasure = $externalProduct['unit_of_measure'];
        }

        $derivatives = null;
        if ($unitOfMeasure && $unitOfMeasure['derivatives']) {
            /** @var Collection $derivatives */
            /* @phpstan-ignore-next-line */
            $derivatives = collect($unitOfMeasure['derivatives']);
        }

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        $unitOfMeasureDerivative = null;
        if ($derivatives) {
            $unitOfMeasureDerivative = $derivatives->firstWhere(
                'id',
                $purchaseOrderItem->unit_of_measure_derivative_id
            );
        }

        foreach ($batchDetails as $batchDetail) {
            /** @var Batch $batch */
            $batch = $externalBatches->firstWhere('number', $batchDetail['batch_number']);
            $batchInventoryUnits = $externalBatchInventoryUnits->where('batch_id', $batch['id']);
            $currentBatchStock = $batchInventoryUnits->sum('quantity');

            $batchQuantity = (float) $batchDetail['quantity'];

            if ($unitOfMeasureDerivative && $unitOfMeasureDerivative['ratio'] > 0) {
                $batchQuantity /= (float) $unitOfMeasureDerivative['ratio'];
            }

            if ($currentBatchStock < $batchQuantity) {
                abort(
                    412,
                    'You cannot do stock transfer for the specified product (upc: ' . $product->upc . ') because the current batch units at the source location are ' . $currentBatchStock . ' only but you requested to transfer extra received ' . $batchDetail['quantity'] . ' units.'
                );
            }
        }
    }
}
