<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItem\Resources;

use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Models\Product;
use App\Models\StockTransferItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StockTransferItemDeliveryNoteResource extends JsonResource
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

        /** @var Product $product */
        $product = $stockTransferItem->product;

        $latestTransaction = $transactions->sortDesc()->firstWhere('status', StatusTypes::RECEIVED->value);
        $mimeType = $stockTransferItem->getFirstMedia('discrepancy_proof')?->mime_type;

        /** @var ?UnitOfMeasureDerivative $derivative */
        $derivative = $stockTransferItem->unitOfMeasureDerivative;

        return [
            'id' => $stockTransferItem->id,
            'stock_transfer_id' => $stockTransferItem->stock_transfer_id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'discrepancy_type' => $stockTransferItem->discrepancy_type,
            'derivative' => $derivative instanceof UnitOfMeasureDerivative ? $derivative->name : null,
            'quantity' => $stockTransferItem->quantity,
            'received_quantity' => $stockTransferItem->received_quantity,
            'remarks' => $transactions->isNotEmpty() ? $transactions->first()->remarks : null,
            'delivery_remarks' => $latestTransaction?->remarks,
            'is_extra_item' => $stockTransferItem->is_extra_item,
            'discrepancy_proof' => $stockTransferItem->getDiskBasedFirstMediaUrl('discrepancy_proof'),
            'mime_type' => $mimeType,
        ];
    }
}
