<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderItem\Resource;

use App\Models\ExternalPurchaseOrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderEditItemReceiveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $externalPurchaseOrderPartialReceive = $this->resource;

        $items = $externalPurchaseOrderPartialReceive->items;
        $externalPurchaseOrderItems = $externalPurchaseOrderPartialReceive->externalPurchaseOrder->items;
        /** @var Carbon $receivedDate */
        $receivedDate = Carbon::createFromFormat('Y-m-d H:i:s', $externalPurchaseOrderPartialReceive->received_date);

        return [
            'id' => $externalPurchaseOrderPartialReceive->id,
            'received_date' => $receivedDate->format('Y-m-d H:i:s'),
            'notes' => $externalPurchaseOrderPartialReceive->notes,
            'receive_items' => $this->getReceiveItems($externalPurchaseOrderItems, $items),
        ];
    }

    public function getReceiveItems(Collection $externalPurchaseOrderItems, Collection $items): Collection
    {
        return $externalPurchaseOrderItems->map(function (ExternalPurchaseOrderItem $externalPurchaseOrderItem) use (
            $items
        ): array {
            /** @var Product $product */
            $product = $externalPurchaseOrderItem->product;

            $externalPurchaseOrderPartialReceiveItem = $items->firstWhere(
                'external_purchase_order_item_id',
                $externalPurchaseOrderItem->id
            );

            $itemBatches = $externalPurchaseOrderPartialReceiveItem->itemBatches;

            return [
                'id' => $externalPurchaseOrderPartialReceiveItem?->id,
                'external_purchase_order_item_id' => $externalPurchaseOrderItem->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_upc' => $product->upc,
                'product_color' => $product->color?->name,
                'product_size' => $product->size?->name,
                'product_has_batch' => $product->has_batch,
                'quantity' => ($externalPurchaseOrderItem->quantity - $externalPurchaseOrderItem->received_quantity) + $externalPurchaseOrderPartialReceiveItem?->quantity_received,
                'quantity_received' => $externalPurchaseOrderPartialReceiveItem?->quantity_received,
                'remarks' => $externalPurchaseOrderItem->remarks ?? 'N/A',
                'unit_of_measure_derivative_id' => $externalPurchaseOrderItem->unit_of_measure_derivative_id,
                'batch_details' => $product->has_batch && $itemBatches->isNotEmpty() ? $this->getBatchDetails(
                    $itemBatches
                ) : [],
            ];
        });
    }

    /**
     * @return mixed[]
     */
    private function getBatchDetails(Collection $itemBatches): array
    {
        return $itemBatches->transform(function ($itemBatch): array {
            /** @var ?Carbon $expiryDate */
            $expiryDate = Carbon::createFromFormat('Y-m-d H:i:s', $itemBatch->expiry_date);

            return [
                'batch_number' => $itemBatch->batch_number,
                'quantity' => $itemBatch->quantity,
                'expiry_date' => $expiryDate?->format('Y-m-d'),
                'notes' => $itemBatch->notes,
            ];
        })->toArray();
    }
}
