<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Resources;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Batch;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemTransaction;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StockTransferOrderEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StockTransfer $stockTransfer */
        $stockTransfer = $this;

        /** @var Collection $stockTransferItems */
        $stockTransferItems = $stockTransfer->getItems();

        /** @var Location $sourceLocation */
        $sourceLocation = $stockTransfer->sourceLocation;

        /** @var Location $destinationLocation */
        $destinationLocation = $stockTransfer->destinationLocation;

        $sourceLocationType = LocationTypes::getFormattedCaseName($sourceLocation->type_id);
        $destinationLocationType = LocationTypes::getFormattedCaseName($destinationLocation->type_id);

        return [
            'id' => $stockTransfer->id,
            'source_location_name' => $sourceLocation->name . ' (' . $sourceLocationType . ')',
            'destination_location_name' => $destinationLocation->name . ' (' . $destinationLocationType . ')',
            'source_location_id' => $stockTransfer->source_location_id,
            'destination_location_id' => $stockTransfer->destination_location_id,
            'transfer_date' => $stockTransfer->transfer_date,
            'require_date' => $stockTransfer->require_date,
            'attention' => $stockTransfer->attention,
            'reference_number' => $stockTransfer->reference_number,
            'remarks' => $stockTransfer->remarks,
            'stock_transfer_reason_id' => $stockTransfer->stock_transfer_reason_id,
            'transfer_items' => $stockTransferItems->map(function ($item) use ($stockTransfer): array {
                /** @var StockTransferItem $stockTransferItem */
                $stockTransferItem = $item;
                /** @var Product $product */
                $product = $stockTransferItem->product;

                $sourceInventory = $stockTransfer['source_inventories']->firstWhere(
                    'product_id',
                    $stockTransferItem->product_id
                );
                $destinationInventory = $stockTransfer['destination_inventories']->firstWhere(
                    'product_id',
                    $stockTransferItem->product_id
                );
                /** @var Collection $stockTransferItemBatches */
                $stockTransferItemBatches = $stockTransferItem->batches;
                /** @var ?StockTransferItemTransaction $transaction */
                $transaction = $stockTransferItem->transaction;
                $derivative = null;
                $transferStock = (float) $stockTransferItem->quantity;

                if (config('app.product_variant')) {
                    /** @var MasterProduct $masterProduct */
                    $masterProduct = $product->masterProduct;
                    $unitOfMeasure = $masterProduct->unitOfMeasure;
                } else {
                    $unitOfMeasure = $product->unitOfMeasure;
                }

                /** @var Collection $derivatives */
                $derivatives = null !== $unitOfMeasure ? $unitOfMeasure->derivatives : collect([]);
                $unitOfMeasureDerivativeId = $stockTransferItem->unit_of_measure_derivative_id;
                if ($unitOfMeasureDerivativeId && $unitOfMeasure && $derivatives->isNotEmpty()) {
                    /** @var UnitOfMeasureDerivative $derivative */
                    $derivative = $derivatives->firstWhere('id', $unitOfMeasureDerivativeId);
                }

                return [
                    'product_id' => $stockTransferItem->product_id,
                    'product' => [
                        'id' => $stockTransferItem->product_id,
                        'name' => $product->compound_product_name,
                    ],
                    'product_color' => config('app.product_variant') ? null : $product->color?->name,
                    'product_size' => config('app.product_variant') ? null : $product->size?->name,
                    'product_variant_values' => config(
                        'app.product_variant'
                    ) ? $product->productVariantValues ?? [] : [],
                    'product_uom' => $unitOfMeasure instanceof UnitOfMeasure ? $unitOfMeasure->getName() : null,
                    'has_batch' => config('app.product_variant') ? $masterProduct->has_batch : $product->has_batch,
                    'package_type_id' => $stockTransferItem->package_type_id,
                    'unit_of_measure_derivative_id' => $unitOfMeasureDerivativeId,
                    'derivative' => $derivative,
                    'derivatives' => $derivatives->isNotEmpty() ? $derivatives->toArray() : null,
                    'package_quantity' => $stockTransferItem->package_quantity,
                    'package_total_quantity' => $stockTransferItem->package_total_quantity,
                    'transfer_stock' => $transferStock,
                    'initial_transfer_quantity' => null !== $derivative ? $transferStock / $derivative->ratio : $transferStock,
                    'received_quantity' => $stockTransferItem->received_quantity,
                    'source_stock' => $sourceInventory ? (float) $sourceInventory->stock : 0,
                    'source_reserved_stock' => $sourceInventory ? $sourceInventory->reserved_stock : 0,
                    'destination_stock' => $destinationInventory ? $destinationInventory->stock : 0,
                    'destination_reserved_stock' => $destinationInventory ? $destinationInventory->reserved_stock : 0,
                    'remarks' => $transaction?->remarks,
                    'batch_details' => $stockTransferItemBatches->map(
                        function ($stockTransferItemBatch): array {
                            /** @var Batch $batch */
                            $batch = $stockTransferItemBatch->batch;

                            return [
                                'batch_number' => $batch->number,
                                'quantity' => $stockTransferItemBatch->quantity,
                            ];
                        }
                    ),
                ];
            }),
        ];
    }
}
