<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Resources;

use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Models\Batch;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StockTransferItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StockTransferItemDiscrepancyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StockTransferItem $stockTransferItem */
        $stockTransferItem = $this;

        /** @var Collection $transactions */
        $transactions = $stockTransferItem->transactions;

        /** @var Collection $stockTransferItemBatches */
        $stockTransferItemBatches = $stockTransferItem->batches;

        /** @var Product $product */
        $product = $stockTransferItem->product;

        $latestTransaction = $transactions->sortDesc()->firstWhere('status', StatusTypes::RECEIVED->value);

        $mimeType = $stockTransferItem->getFirstMedia('discrepancy_proof')?->mime_type;

        /** @var ?UnitOfMeasureDerivative $derivative */
        $derivative = $stockTransferItem->unitOfMeasureDerivative;

        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;
        }

        return [
            'id' => $stockTransferItem->id,
            'product' => $product->name,
            'has_batch' => config('app.product_variant') ? $masterProduct->has_batch : $product->has_batch,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'quantity' => $stockTransferItem->quantity,
            'derivative' => $derivative instanceof UnitOfMeasureDerivative ? $derivative->name : null,
            'received_quantity' => $stockTransferItem->received_quantity,
            'discrepancy_type' => $stockTransferItem->discrepancy_type,
            'remarks' => $transactions->isNotEmpty() ? $transactions->first()->remarks : null,
            'delivery_remarks' => $latestTransaction?->remarks,
            'is_extra_item' => $stockTransferItem->is_extra_item,
            'batches' => $stockTransferItemBatches->map(function ($stockTransferItemBatch): array {
                /** @var Batch $batch */
                $batch = $stockTransferItemBatch->batch;

                return [
                    'batch_number' => $batch->number,
                    'quantity' => $stockTransferItemBatch->quantity,
                ];
            }),
            'batch_details' => [],
            'discrepancy_proof' => $stockTransferItem->getDiskBasedFirstMediaUrl('discrepancy_proof'),
            'mime_type' => $mimeType,
        ];
    }
}
