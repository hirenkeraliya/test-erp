<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Resources;

use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StockTransferItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StockTransferShipResource extends JsonResource
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

        $derivative = null;

        $transferStock = (float) $stockTransferItem->quantity;

        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;
            $unitOfMeasure = $masterProduct->unitOfMeasure;
        } else {
            $unitOfMeasure = $product->unitOfMeasure;
        }

        $unitOfMeasureDerivativeId = $stockTransferItem->unit_of_measure_derivative_id;
        if ($unitOfMeasureDerivativeId && $unitOfMeasure) {
            /** @var UnitOfMeasureDerivative $derivative */
            $derivative = $unitOfMeasure->derivatives->firstWhere('id', $unitOfMeasureDerivativeId);
        }

        return [
            'id' => $stockTransferItem->id,
            'product' => $product,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'transfer_stock' => $transferStock,
            'unit_of_measure_derivative_id' => $unitOfMeasureDerivativeId,
            'derivative' => $derivative,
            'remarks' => $transactions->isNotEmpty() ? $transactions->first()->remarks : null,
            'package_type_id' => null,
            'package_quantity' => null,
            'batch_details' => [],
        ];
    }
}
