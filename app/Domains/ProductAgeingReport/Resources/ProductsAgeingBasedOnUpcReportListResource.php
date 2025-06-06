<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Resources;

use App\Domains\Product\Services\ProductService;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsAgeingBasedOnUpcReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $productAgeing = $this->resource;

        /** @var Product $product */
        $product = $productAgeing->product;

        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?Size $size */
        $size = $product->size;

        $request->get('age_of_product_type');
        $productAgeingAgeingReportService = resolve(ProductAgeingReportService::class);
        $productService = resolve(ProductService::class);

        return [
            'id' => $product->id,
            'product' => $product->name,
            'upc' => $product->upc,
            'article_number' => $product->article_number ?? 'N/A',
            'color' => config('app.product_variant') ? null : $color?->name ?? 'N/A',
            'size' => config('app.product_variant') ? null : $size?->name ?? 'N/A',
            'last_selling_date' => $productAgeingAgeingReportService->getLastSellingDate($productAgeing),
            'quantity_sold' => (float) $productAgeing->quantity_sold,
            'quantity_remaining' => (float) $productAgeing->quantity_remaining,
            'age_of_the_product' => $productAgeing->age_category_based_on_created_at . ' Days',
            'age_of_the_product_first_grn' => null === $productAgeing->age_category_based_on_first_goods_received_note ? 'N/A' : $productAgeing->age_category_based_on_first_goods_received_note . ' Days',
            'age_of_the_product_first_transfer_in' => null === $productAgeing->age_category_based_on_first_transfer_in ? 'N/A' : $productAgeing->age_category_based_on_first_transfer_in . ' Days',
            'created_at' => $productAgeingAgeingReportService->getCreatedAt($productAgeing),
            'first_grn_date' => $productAgeingAgeingReportService->getFirstGrnDate($productAgeing),
            'first_transfer_in_date' => $productAgeingAgeingReportService->getFirstTransferInDate($productAgeing),
            'attributes' => $productService->getAttributesWithNameAndValueKey($product),
        ];
    }
}
