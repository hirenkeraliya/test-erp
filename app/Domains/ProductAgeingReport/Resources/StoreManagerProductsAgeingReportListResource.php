<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Resources;

use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\Color;
use App\Models\Location;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreManagerProductsAgeingReportListResource extends JsonResource
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

        /** @var Location $location */
        $location = $productAgeing->location;

        /** @var Product $product */
        $product = $productAgeing->product;

        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?Size $size */
        $size = $product->size;

        $request->get('age_of_product_type');
        $productAgeingAgeingReportService = resolve(ProductAgeingReportService::class);

        return [
            'id' => $product->id,
            'location' => $location->name,
            'product' => $product->name,
            'upc' => $product->upc,
            'article_number' => $product->article_number ?? 'N/A',
            'color' => $color?->name ?? 'N/A',
            'size' => $size?->name ?? 'N/A',
            'last_selling_date' => $productAgeingAgeingReportService->getLastSellingDate($productAgeing),
            'quantity_sold' => (float) $productAgeing->quantity_sold,
            'quantity_remaining' => $productAgeing->quantity_remaining,
            'age_of_the_product' => $productAgeing->age_category . ' Days',
            'age_category' => $productAgeing->age_category,
            'created_at' => $productAgeingAgeingReportService->getCreatedAt($productAgeing),
            'first_grn_date' => $productAgeingAgeingReportService->getFirstGrnDate($productAgeing),
            'first_transfer_in_date' => $productAgeingAgeingReportService->getFirstTransferInDate($productAgeing),
        ];
    }
}
