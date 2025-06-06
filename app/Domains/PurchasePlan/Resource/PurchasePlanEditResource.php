<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\Resource;

use App\Domains\Product\Services\ProductService;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasePlanEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $productService = resolve(ProductService::class);

        /** @var PurchasePlan $purchasePlan */
        $purchasePlan = $this->resource;

        /** @var Vendor $vendor */
        $vendor = $purchasePlan->vendor;

        $purchasePlanItems = $purchasePlan->getItems();

        /** @var Location $location */
        $location = $purchasePlan->location;

        return [
            'id' => $purchasePlan->id,
            'reference_number' => $purchasePlan->reference_number,
            'plan_number' => $purchasePlan->plan_number,
            'remarks' => $purchasePlan->remarks,
            'total_amount' => $purchasePlan->total_amount,
            'location_id' => $purchasePlan->location_id,
            'vendor_id' => $vendor->id,
            'type_id' => $location->type_id,
            'source_location_name' => $vendor->name,
            'destination_location_name' => $location->name,
            'transfer_items' => $purchasePlanItems->map(function (PurchasePlanItem $purchasePlanItem) use (
                $purchasePlan,
                $productService
            ): array {
                /** @var Product $product */
                $product = $purchasePlanItem->product;
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

                $sourceInventory = $purchasePlan['source_inventories']->firstWhere(
                    'product_id',
                    $purchasePlanItem->product_id
                );

                $derivative = null;
                if ($purchasePlanItem->unit_of_measure_derivative_id && $unitOfMeasureDerivative) {
                    $derivative = $unitOfMeasureDerivative
                        ->where('id', $purchasePlanItem->unit_of_measure_derivative_id)
                        ->first();
                }

                [$color, $size] = $productService->getColorAndSize($product);

                $data = [
                    'product_id' => $purchasePlanItem->product_id,
                    'product' => [
                        'id' => $purchasePlanItem->product_id,
                        'name' => $product->compound_product_name,
                    ],
                    'derivatives' => $unitOfMeasureDerivative,
                    'derivative' => $derivative,
                    'product_color' => $color,
                    'product_size' => $size,
                    'product_uom' => config(
                        'app.product_variant'
                    ) ? $masterProduct->unitOfMeasure?->name : $product->unitOfMeasure?->name,
                    'quantity' => $purchasePlanItem->quantity,
                    'unit_of_measure_derivative_id' => $purchasePlanItem->unit_of_measure_derivative_id,
                    'is_product_purchase_cost' => $purchasePlanItem->is_product_purchase_cost,
                    'stock' => $sourceInventory ? $sourceInventory->stock : 0,
                    'reserved_stock' => $sourceInventory ? $sourceInventory->reserved_stock : 0,
                ];

                if (! $purchasePlanItem->is_product_purchase_cost) {
                    $data['purchase_cost'] = $purchasePlanItem->cost_price;
                }

                return $data;
            }),
        ];
    }
}
