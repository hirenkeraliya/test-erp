<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Resource;

use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->resource;

        $purchaseOrderItems = $purchaseOrder->getItems();

        /** @var Location $location */
        $location = $purchaseOrder->location;

        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        $sourceCompanyName = '';
        $destinationCompanyName = $purchaseOrder->externalCompany?->getNameWithCode();
        $sourceLocationName = $location->getNameWithCode();
        $destinationLocationName = $externalLocation->getNameWithCode();

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value) {
            $sourceCompanyName = $purchaseOrder->externalCompany?->getNameWithCode();
            $destinationCompanyName = '';
            $sourceLocationName = $externalLocation->getNameWithCode();
            $destinationLocationName = $location->getNameWithCode();
        }

        return [
            'id' => $purchaseOrder->id,
            'require_date' => $purchaseOrder->require_date,
            'reference_number' => $purchaseOrder->reference_number,
            'attention' => $purchaseOrder->attention,
            'remarks' => $purchaseOrder->remarks,
            'location_id' => $purchaseOrder->location_id,
            'type_id' => $location->type_id,
            'external_location_id' => $purchaseOrder->external_location_id,
            'external_type_id' => $externalLocation->type_id,
            'external_company_id' => $purchaseOrder->external_company_id,
            'created_by_company_id' => $purchaseOrder->created_by_company_id,
            'source_location_name' => $sourceLocationName,
            'destination_location_name' => $destinationLocationName,
            'source_company_name' => $sourceCompanyName,
            'destination_company_name' => $destinationCompanyName,
            'transfer_items' => $purchaseOrderItems->map(function (PurchaseOrderItem $purchaseOrderItem) use (
                $purchaseOrder,
            ): array {
                /** @var Product $product */
                $product = $purchaseOrderItem->product;
                $unitOfMeasureDerivative = null;

                if (config('app.product_variant')) {
                    /** @var MasterProduct $masterProduct */
                    $masterProduct = $product->masterProduct;
                }

                if (config('app.product_variant')) {
                    if ($product->masterProduct && $product->masterProduct->unitOfMeasure) {
                        $unitOfMeasureDerivative = $masterProduct->unitOfMeasure?->derivatives;
                    }
                } elseif ($product->unitOfMeasure) {
                    $unitOfMeasureDerivative = $product->unitOfMeasure->derivatives;
                }

                $derivative = null;
                if ($purchaseOrderItem->unit_of_measure_derivative_id && $unitOfMeasureDerivative) {
                    $derivative = $unitOfMeasureDerivative
                        ->where('id', $purchaseOrderItem->unit_of_measure_derivative_id)
                        ->first();
                }

                $sourceInventory = $purchaseOrder['source_inventories']->firstWhere(
                    'product_id',
                    $purchaseOrderItem->product_id
                );
                $externalInventory = $purchaseOrder['external_inventories']->firstWhere('upc', $product->upc);

                return [
                    'product_id' => $purchaseOrderItem->product_id,
                    'product' => [
                        'id' => $purchaseOrderItem->product_id,
                        'name' => $product->compound_product_name,
                    ],
                    'unit_of_measure_derivative_id' => $purchaseOrderItem->unit_of_measure_derivative_id,
                    'derivatives' => $unitOfMeasureDerivative,
                    'derivative' => $derivative,
                    'product_color' => config('app.product_variant') ? null : $product->color?->name,
                    'product_size' => config('app.product_variant') ? null : $product->size?->name,
                    'product_variant_values' => config(
                        'app.product_variant'
                    ) ? $product->productVariantValues ?? [] : [],
                    'product_uom' => config(
                        'app.product_variant'
                    ) ? $masterProduct->unitOfMeasure?->name : $product->unitOfMeasure?->name,
                    'quantity' => $purchaseOrderItem->quantity,
                    'stock' => $sourceInventory ? $sourceInventory->stock : 0,
                    'reserved_stock' => $sourceInventory ? $sourceInventory->reserved_stock : 0,
                    'external_stock' => $externalInventory ? $externalInventory['external_stock'] : 0,
                    'external_reserved_stock' => $externalInventory ? $externalInventory['external_reserved_stock'] : 0,
                ];
            }),
        ];
    }
}
