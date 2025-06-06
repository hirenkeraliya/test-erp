<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\Domains\StockTransfer\DataObjects\StockTransferData;
use App\Domains\StockTransfer\DataObjects\StockTransferRequestOrderData;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\Enums\StockTransferDiscrepancyTypes;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StockTransferCheckRequestService
{
    public function checkRequestDetails(
        StockTransferData|StockTransferRequestOrderData $stockTransferData,
        Collection $products,
        Collection $inventories,
        Collection $batches,
        Collection $derivatives,
    ): void {
        $transferItems = $stockTransferData->transfer_items;

        if (
            $stockTransferData->source_location_id === $stockTransferData->destination_location_id
        ) {
            throw new RedirectBackWithErrorException('Source & Destination cannot be same.');
        }

        foreach ($transferItems as $transferItem) {
            $product = $products->firstWhere('id', $transferItem['product_id']);

            if (! $product) {
                throw new RedirectBackWithErrorException(
                    'product ID: ' . $transferItem['product_id'] . ' is not available.'
                );
            }

            $derivative = null;

            if (array_key_exists('unit_of_measure_derivative_id', $transferItem) &&
                $transferItem['unit_of_measure_derivative_id']
            ) {
                $derivative = $derivatives->firstWhere('id', $transferItem['unit_of_measure_derivative_id']);

                if (! $derivative) {
                    throw new RedirectBackWithErrorException(
                        'derivative ID: ' . $transferItem['unit_of_measure_derivative_id'] . ' is not available.'
                    );
                }

                if (config('app.product_variant')) {
                    if ($derivative->unit_of_measure_id !== $product->masterProduct->unit_of_measure_id) {
                        throw new RedirectBackWithErrorException(
                            'product (UPC - ' . $product->upc . ') UOM does not match with selected derivate.'
                        );
                    }
                } elseif ($derivative->unit_of_measure_id !== $product->unit_of_measure_id) {
                    throw new RedirectBackWithErrorException(
                        'product (UPC - ' . $product->upc . ') UOM does not match with selected derivate.'
                    );
                }

                $transferItem['transfer_stock'] = (float) $transferItem['transfer_stock'] / (float) $derivative->ratio;
                $transferItem['initial_transfer_quantity'] = (float) $transferItem['initial_transfer_quantity'] / (float) $derivative->ratio;
            }

            $inventory = $inventories->firstWhere('product_id', $transferItem['product_id']);

            if (! $inventory) {
                throw new RedirectBackWithErrorException(
                    'product (UPC - ' . $product->upc . ') source stock is not available.'
                );
            }

            $finalStock = (float) ($inventory->stock + $transferItem['initial_transfer_quantity']);

            if ($transferItem['transfer_stock'] > $finalStock) {
                throw new RedirectBackWithErrorException(
                    'Transfer stock (' . $transferItem['transfer_stock'] . ') of the product (UPC - ' . $product->upc . ') cannot be more than the current source stock (' . $finalStock . ').'
                );
            }

            $transferStock = $derivative ?
                (float) $transferItem['transfer_stock'] * (float) $derivative->ratio :
                $transferItem['transfer_stock'];

            if (array_key_exists('package_total_quantity', $transferItem) &&
                (float) $transferItem['package_total_quantity'] !== (float) $transferStock
            ) {
                throw new RedirectBackWithErrorException(
                    'The quantity of stock being transferred does not match the total quantity indicated in the package. Please verify and ensure that the correct quantity is being transferred to avoid errors and discrepancies.'
                );
            }

            if (config('app.product_variant')) {
                if (! $product->masterProduct->has_batch) {
                    continue;
                }
            } elseif (! $product->has_batch) {
                continue;
            }

            if ($stockTransferData instanceof StockTransferData &&
                StockTransferTypes::getCaseName(
                    StockTransferTypes::TRANSFER_ORDER->value
                ) === $stockTransferData->transfer_type
            ) {
                if (array_key_exists('batch_details', $transferItem) && [] !== $transferItem['batch_details']) {
                    /** @var array<array> $batchDetails */
                    $batchDetails = $transferItem['batch_details'];

                    $batchDetails = collect($batchDetails);

                    if ((float) $batchDetails->sum('quantity') !== (float) $transferStock) {
                        throw new RedirectBackWithErrorException(
                            'Stock transfer quantity does not match with the batch details quantity.'
                        );
                    }

                    $this->validateBatchNumber($batchDetails, $batches, $product->id);

                    foreach ($transferItem['batch_details'] as $batch) {
                        $this->validateBatchDetailsInventory(
                            $product,
                            $inventories,
                            $batches,
                            $batchDetails,
                            (string) $batch['quantity']
                        );
                    }

                    continue;
                }

                throw new RedirectBackWithErrorException(
                    'product (UPC - ' . $product->upc . ') is a batch product so the batch details are required.'
                );
            }
        }
    }

    public function checkRequestOrderEditor(StockTransfer $stockTransfer, int $locationId): void
    {
        if ($stockTransfer->getStatus() === StatusTypes::OPEN->value &&
            $stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value &&
            $stockTransfer->source_location_id === $locationId
        ) {
            return;
        }

        throw new RedirectBackWithErrorException('You are not eligible to update request order details.');
    }

    public function checkClosingDiscrepancyRequestBatchDetails(
        StockTransfer $stockTransfer,
        array $validatedData,
        Collection $products,
        Collection $sourceInventories,
        Collection $batches
    ): void {
        /** @var array $stockTransferItems */
        $stockTransferItems = $validatedData['stock_transfer_items'];
        $requestStockTransferItems = collect($stockTransferItems);

        foreach ($stockTransfer->getItems() as $item) {
            $product = $products->firstWhere('id', $item->product_id);

            $exceedQuantity = $item->received_quantity - $item->quantity;

            if (true === $item->is_extra_item) {
                $exceedQuantity = $item->received_quantity;
            }

            $derivative = null;

            if ($derivative = $item->unitOfMeasureDerivative) {
                $exceedQuantity = (float) $exceedQuantity / (float) $derivative->ratio;
            }

            $hasBatch = config('app.product_variant') ? $product->masterProduct->has_batch : $product->has_batch;

            if (! $hasBatch) {
                $inventory = $sourceInventories->firstWhere('product_id', $product->id);

                if ((float) $inventory->stock < (float) $exceedQuantity) {
                    throw new RedirectBackWithErrorException(
                        'You cannot close this discrepancy stock transfer because the current stock of the product (name: ' . $product->compound_product_name . ') at the source location is ' . $inventory->stock . ' only but the extra quantity received is ' . $exceedQuantity . ' units.'
                    );
                }
            }

            if ($hasBatch && (float) $item->quantity !== (float) $item->received_quantity) {
                $requestStockTransferItem = $requestStockTransferItems->firstWhere('id', $item->id);

                /** @var array<array> $batchDetails */
                $batchDetails = $requestStockTransferItem['batch_details'];
                $batchDetails = collect($batchDetails)
                    ->whereNotNull('batch_number')
                    ->whereNotNull('quantity');

                $this->validateBatchNumber($batchDetails, $batches, $product->id);

                if ($this->isPositiveDiscrepancy($item)) {
                    if ((float) $batchDetails->sum('quantity') !== (float) $exceedQuantity) {
                        throw new RedirectBackWithErrorException(
                            'Positive discrepancy item quantity does not match with batch(es) quantity.'
                        );
                    }

                    $this->validateBatchDetailsInventory(
                        $product,
                        $sourceInventories,
                        $batches,
                        $batchDetails,
                        (string) $exceedQuantity
                    );
                }

                if ($this->isNegativeDiscrepancy($item)) {
                    $quantity = $derivative ? (float) $item->received_quantity / (float) $derivative->ratio : (float) $item->received_quantity;

                    if ((float) $batchDetails->sum('quantity') !== $quantity) {
                        throw new RedirectBackWithErrorException(
                            'Negative discrepancy item quantity does not match with batch(es) quantity.'
                        );
                    }

                    $this->validateBatchDetailsInventory(
                        $product,
                        $sourceInventories,
                        $batches,
                        $batchDetails,
                        (string) $quantity
                    );
                }

                if (true === $item->is_extra_item) {
                    if ((float) $batchDetails->sum('quantity') !== (float) $exceedQuantity) {
                        throw new RedirectBackWithErrorException(
                            'Additional item quantity does not match with batch(es) quantity.'
                        );
                    }

                    $exceedQuantity = $derivative ?
                        (float) $derivative->ratio / (float) $exceedQuantity :
                        $exceedQuantity;

                    $this->validateBatchDetailsInventory(
                        $product,
                        $sourceInventories,
                        $batches,
                        $batchDetails,
                        (string) $exceedQuantity
                    );
                }
            }
        }
    }

    public function checkTransferType(StockTransferData $stockTransferData, int $locationId): void
    {
        if (Str::lower(StockTransferTypes::TRANSFER_ORDER->name) === $stockTransferData->transfer_type &&
            $stockTransferData->source_location_id !== $locationId
        ) {
            throw new RedirectBackWithErrorException(
                'The source location has to be the current location when the transfer type is "' . Str::lower(
                    StockTransferTypes::TRANSFER_ORDER->name
                ) . '"'
            );
        }

        if (Str::lower(StockTransferTypes::REQUEST_ORDER->name) !== $stockTransferData->transfer_type) {
            return;
        }

        if ($stockTransferData->destination_location_id === $locationId) {
            return;
        }

        throw new RedirectBackWithErrorException(
            'The destination location has to be the current location when the transfer type is "' . Str::lower(
                StockTransferTypes::REQUEST_ORDER->name
            ) . '"'
        );
    }

    public function checkPrintTransferType(StockTransfer $stockTransfer, string $transferType, int $locationId): void
    {
        if ($stockTransfer->transit_location_id === $locationId
        ) {
            return;
        }

        if ('OUT' === $transferType && $stockTransfer->getSourceLocationId() !== $locationId) {
            throw new RedirectBackWithErrorException(
                'Transfer source location does not match currently selected store.'
            );
        }

        if ('IN' !== $transferType) {
            return;
        }

        if (
            $stockTransfer->getDestinationLocationId() === $locationId) {
            return;
        }

        throw new RedirectBackWithErrorException(
            'Transfer destination location does not match currently selected store.'
        );
    }

    public function checkWarehouseManagerPrintTransferType(
        StockTransfer $stockTransfer,
        string $transferType,
        int $locationId
    ): void {
        if (
            $stockTransfer->transit_location_id === $locationId
        ) {
            return;
        }

        if ('OUT' === $transferType && $stockTransfer->getSourceLocationId() !== $locationId) {
            throw new RedirectBackWithErrorException(
                'Transfer source location does not match currently selected warehouse.'
            );
        }

        if ('IN' !== $transferType) {
            return;
        }

        if ($stockTransfer->getDestinationLocationId() === $locationId) {
            return;
        }

        throw new RedirectBackWithErrorException(
            'Transfer destination location does not match currently selected warehouse.'
        );
    }

    public function checkAdditionalItemsRequest(
        array $requestData,
        Collection $products,
        int $stockTransferId,
        Collection $derivatives
    ): void {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);

        $stockTransferItems = $stockTransferItemQueries->getProductIdsBy($stockTransferId);

        foreach ($requestData['additional_items'] as $transferItem) {
            $requestProductId = $transferItem['product_id'];
            $product = $products->firstWhere('id', $requestProductId);
            if (! $product) {
                abort(412, 'product ID: ' . $requestProductId . ' is not available.');
            }

            $derivative = null;

            if (array_key_exists('unit_of_measure_derivative_id', $transferItem) &&
                $transferItem['unit_of_measure_derivative_id']
            ) {
                $derivative = $derivatives->firstWhere('id', $transferItem['unit_of_measure_derivative_id']);

                if (! $derivative) {
                    throw new RedirectBackWithErrorException(
                        'derivative ID: ' . $transferItem['unit_of_measure_derivative_id'] . ' is not available.'
                    );
                }

                if (config('app.product_variant')) {
                    if ($derivative->unit_of_measure_id !== $product->masterProduct->unit_of_measure_id) {
                        throw new RedirectBackWithErrorException(
                            'product (UPC - ' . $product->upc . ') UOM does not match with selected derivate.'
                        );
                    }
                } elseif ($derivative->unit_of_measure_id !== $product->unit_of_measure_id) {
                    throw new RedirectBackWithErrorException(
                        'product (UPC - ' . $product->upc . ') UOM does not match with selected derivate.'
                    );
                }
            }

            /** @var ?StockTransferItem $stockTransferItem */
            $stockTransferItem = $stockTransferItems->firstWhere('product_id', $requestProductId);
            if ($stockTransferItem && $requestProductId === $stockTransferItem->product_id) {
                abort(412, 'You can not add duplicate product UPC:' . $product->upc);
            }

            if (! array_key_exists('package_total_quantity', $transferItem)) {
                continue;
            }

            if ((float) $transferItem['package_total_quantity'] === (float) $transferItem['received_quantity']) {
                continue;
            }

            abort(
                412,
                'The received quantity of stock being transferred does not match the total quantity indicated in the package. Please verify and ensure that the correct received quantity is being transferred to avoid errors and discrepancies.'
            );
        }
    }

    public function checkShippingDetails(
        Collection $validatedData,
        StockTransfer $stockTransfer,
        Collection $products,
        Collection $batches,
        Collection $derivatives,
    ): void {
        foreach ($stockTransfer->getItems() as $item) {
            $requestStockTransferItem = $validatedData->firstWhere('id', $item->id);
            $product = $products->firstWhere('id', $item->product_id);

            $derivative = null;

            if ($item->unit_of_measure_derivative_id) {
                $derivative = $derivatives->firstWhere('id', $item->unit_of_measure_derivative_id);

                if (! $derivative) {
                    throw new RedirectBackWithErrorException(
                        'derivative ID: ' . $item->unit_of_measure_derivative_id . ' is not available.'
                    );
                }

                if (config('app.product_variant')) {
                    if ($derivative->unit_of_measure_id !== $product->masterProduct->unit_of_measure_id) {
                        throw new RedirectBackWithErrorException(
                            'product (UPC - ' . $product->upc . ') UOM does not match with selected derivate.'
                        );
                    }
                } elseif ($derivative->unit_of_measure_id !== $product->unit_of_measure_id) {
                    throw new RedirectBackWithErrorException(
                        'product (UPC - ' . $product->upc . ') UOM does not match with selected derivate.'
                    );
                }
            }

            if (config('app.product_variant')) {
                if (! $product->masterProduct->has_batch) {
                    continue;
                }
            } elseif (! $product->has_batch) {
                continue;
            }

            $transferStock = $derivative ?
                (float) $item->quantity / (float) $derivative->ratio :
                $item->quantity;

            /** @var Collection $batchDetails */
            $batchDetails = collect((array) $requestStockTransferItem['batch_details']);

            if ((float) $batchDetails->sum('quantity') !== (float) $transferStock) {
                throw new RedirectBackWithErrorException(
                    'Stock transfer quantity does not match with the batch details quantity.'
                );
            }

            $this->validateBatchNumber($batchDetails, $batches, $product->id);
        }
    }

    public function locationChanged(
        StockTransfer $stockTransfer,
        StockTransferData|StockTransferRequestOrderData $stockTransferData
    ): void {
        if (
            $stockTransfer->source_location_id !== $stockTransferData->source_location_id
        ) {
            throw new RedirectBackWithErrorException('Stock Transfer source location cannot change.');
        }

        if (
            $stockTransfer->destination_location_id !== $stockTransferData->destination_location_id
        ) {
            throw new RedirectBackWithErrorException('Stock Transfer destination location cannot change.');
        }
    }

    public function validateTransitLocation(
        StockTransferShippedData $stockTransferShippedData,
        int $stockTransferId,
        int $companyId
    ): void {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfer = $stockTransferQueries->getLocationById($stockTransferId, $companyId);
        if ((int) $stockTransferShippedData->location_id !== (int) $stockTransfer->source_location_id) {
            return;
        }

        if ((int) $stockTransferShippedData->location_id !== (int) $stockTransfer->destination_location_id) {
            return;
        }

        abort(412, 'Transit location must be different than source & destination location.');
    }

    public function validateItemBatchesQuantityWithInventoryUnit(
        StockTransfer $stockTransfer,
        Collection $products,
        Collection $sourceInventories
    ): void {
        foreach ($stockTransfer->items as $stockTransferItem) {
            $product = $products->firstWhere('id', $stockTransferItem->product_id);

            if (config('app.product_variant')) {
                if ($product instanceof Product && $product->masterProduct && ! $product->masterProduct->has_batch) {
                    continue;
                }
            } elseif ($product instanceof Product && ! $product->has_batch) {
                continue;
            }

            $inventory = $sourceInventories->firstWhere('product_id', $stockTransferItem->product_id);

            foreach ($stockTransferItem->batches as $batch) {
                $batchInventoryUnits = $inventory->inventoryUnits->where('batch_id', $batch->batch_id);
                $currentBatchStock = $batchInventoryUnits->sum('quantity');
                $batchDetailsQuantity = $stockTransferItem->batches->where(
                    'stock_transfer_item_id',
                    $stockTransferItem->id
                )->sum('quantity');
                // same batch number(batch_id) with different quantity entry in the db.

                if ($currentBatchStock < $batchDetailsQuantity) {
                    throw new RedirectBackWithErrorException(
                        'You cannot do stock transfer for the specified product (name: ' . $product->compound_product_name . '). Because the current batch units at the source location are ' . $currentBatchStock . ' only, you have requested to transfer ' . $batchDetailsQuantity . ' units.'
                    );
                }
            }
        }
    }

    private function validateBatchNumber(Collection $batchDetails, Collection $batches, int $productId): void
    {
        $batchNumbers = $batchDetails->pluck('batch_number')->unique()->filter();

        if ($batchNumbers->isEmpty()) {
            throw new RedirectBackWithErrorException('Batch numbers must be provided.');
        }

        $matchedBatches = $batches->where('product_id', $productId)
            ->whereIn('number', $batchNumbers->toArray());

        if ($matchedBatches->count() !== $batchNumbers->count()) {
            throw new RedirectBackWithErrorException('Some of the batch numbers do not match with our records.');
        }
    }

    private function isNegativeDiscrepancy(StockTransferItem $item): bool
    {
        return $item->received_quantity < $item->quantity;
    }

    private function isPositiveDiscrepancy(StockTransferItem $item): bool
    {
        return $item->received_quantity > $item->quantity &&
            $item->discrepancy_type === StockTransferDiscrepancyTypes::POSITIVE->value;
    }

    private function validateBatchDetailsInventory(
        Product $product,
        Collection $sourceInventories,
        Collection $batches,
        Collection $batchDetails,
        string $transferBatchQuantity
    ): void {
        $inventory = $sourceInventories->firstWhere('product_id', $product->id);
        foreach ($batchDetails as $batchDetail) {
            /** @var Batch $batch */
            $batch = $batches->firstWhere('number', $batchDetail['batch_number']);
            $batchInventoryUnits = $inventory->inventoryUnits->where('batch_id', $batch->id);
            $currentBatchStock = $batchInventoryUnits->sum('quantity');

            if ($currentBatchStock < (float) $transferBatchQuantity) {
                throw new RedirectBackWithErrorException(
                    'You cannot do stock transfer for the specified product (name: ' . $product->compound_product_name . ') because the current batch units at the source location are ' . $currentBatchStock . ' only but you requested to transfer extra received ' . $transferBatchQuantity . ' units.'
                );
            }
        }
    }
}
