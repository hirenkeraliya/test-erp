<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlanItem\Resource;

use App\Models\Product;
use App\Models\PurchasePlanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PurchasePlanShippingEditItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $externalPurchaseOrder = $this->resource;

        $items = $externalPurchaseOrder->items;
        $purchasePlanItems = $externalPurchaseOrder->purchasePlan->items;

        return [
            'id' => $externalPurchaseOrder->id,
            'purchase_plan_id' => $externalPurchaseOrder->purchase_plan_id,
            'fob' => $externalPurchaseOrder->fob,
            'freight_charges' => $externalPurchaseOrder->freight_charges,
            'insurance_charges' => $externalPurchaseOrder->insurance_charges,
            'duty' => $externalPurchaseOrder->duty,
            'sst' => $externalPurchaseOrder->sst,
            'handling_charges' => $externalPurchaseOrder->handling_charges,
            'other_charges' => $externalPurchaseOrder->other_charges,
            'notes' => $externalPurchaseOrder->notes,
            'transfer_items' => $this->getTransferItems($purchasePlanItems, $items),
        ];
    }

    public function getTransferItems(Collection $purchasePlanItems, Collection $items): Collection
    {
        return $purchasePlanItems->map(function (PurchasePlanItem $purchasePlanItem) use ($items): array {
            /** @var Product $product */
            $product = $purchasePlanItem->product;

            $externalPurchaseOrderItem = $items->firstWhere('purchase_plan_item_id', $purchasePlanItem->id);

            return [
                'id' => $externalPurchaseOrderItem?->id,
                'purchase_plan_item_id' => $purchasePlanItem->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_upc' => $product->upc,
                'product_color' => $product->color?->name,
                'product_size' => $product->size?->name,
                'product_has_batch' => $product->has_batch,
                'quantity' => ($purchasePlanItem->quantity - $purchasePlanItem->transferred_quantity) + $externalPurchaseOrderItem?->quantity,
                'received_quantity' => $externalPurchaseOrderItem?->quantity,
                'remarks' => $externalPurchaseOrderItem->remarks ?? '',
                'cost_price' => $purchasePlanItem->cost_price,
                'unit_of_measure_derivative_id' => $purchasePlanItem->unit_of_measure_derivative_id,
            ];
        });
    }
}
