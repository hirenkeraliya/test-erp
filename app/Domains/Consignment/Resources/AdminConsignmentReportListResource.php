<?php

declare(strict_types=1);

namespace App\Domains\Consignment\Resources;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminConsignmentReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $product = $this->resource;

        $saleItems = $product->saleItems;

        $unitSold = $saleItems->sum('quantity');

        $total = $unitSold * $product->retail_price;

        /** @var Vendor|null $vendor */
        $vendor = config('app.product_variant') ? $product->masterProduct?->vendor : $product->vendor;

        $commission = $vendor ? ($total * $vendor->commission_percentage) / 100 : 0;

        /** @var array $categories = [] */
        $categories = config('app.product_variant') ? $product->masterProduct?->categories->pluck(
            'name'
        )->toArray() ?? [] : $product->categories->pluck('name')->toArray();

        return [
            'product' => $product->name,
            'upc' => $product->upc,
            'article_number' => config(
                'app.product_variant'
            ) ? $product->masterProduct?->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
            'vendor' => $vendor ? $vendor->name : 'N/A',
            'categories' => $categories,
            'brand' => config('app.product_variant') ? $product->masterProduct?->brand?->name : $product->brand?->name,
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'unit_sold' => $unitSold,
            'price' => $product->retail_price,
            'total' => $total,
            'commission' => $commission,
            'attributes' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ];
    }
}
