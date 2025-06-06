<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Resources;

use App\Domains\Product\Services\ProductService;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductsAgeingByMonthAndYearReportListResource extends JsonResource
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

        /** @var Product` $product */
        $product = $productAgeing->product;

        /** @var Location $location */
        $location = $productAgeing->location;

        $colorName = config('app.product_variant') ? null : $product?->color->name ?? 'N/A';
        $sizeName = config('app.product_variant') ? null : $product?->size->name ?? 'N/A';

        $productAgeingReportService = resolve(ProductAgeingReportService::class);
        $productService = resolve(ProductService::class);

        return [
            'id' => $product->id,
            'product' => $product->name,
            'location' => $location->getNameWithCode(),
            'upc' => $product->upc,
            'article_number' => $product->article_number ?? 'N/A',
            'color' => $colorName,
            'size' => $sizeName,
            'last_selling_date' => $productAgeing->last_selling_date ?? 'N/A',
            'quantity_sold' => $productAgeing->quantity_sold,
            'quantity_remaining' => $productAgeing->quantity_remaining,
            'age_of_the_product' => $productAgeing->age_category . ' Days',
            'age_category' => $productAgeingReportService->getAgeCategory($productAgeing->age_category),
            'created_at' => $productAgeing->product_created_at,
            'first_grn_date' => $productAgeing->first_goods_received_note ?? 'N/A',
            'first_transfer_in_date' => $productAgeing->first_transfer_in ?? 'N/A',
            'first_month_quantity_sold' => $productAgeing->first_month_sold ?? 0,
            'second_month_quantity_sold' => $productAgeing->second_month_sold ?? 0,
            'third_month_quantity_sold' => $productAgeing->third_month_sold ?? 0,
            'fourth_month_quantity_sold' => $productAgeing->fourth_month_sold ?? 0,
            'fifth_month_quantity_sold' => $productAgeing->fifth_month_sold ?? 0,
            'sixth_month_quantity_sold' => $productAgeing->sixth_month_sold ?? 0,
            'seventh_month_quantity_sold' => $productAgeing->seventh_month_sold ?? 0,
            'eighth_month_quantity_sold' => $productAgeing->eighth_month_sold ?? 0,
            'ninth_month_quantity_sold' => $productAgeing->ninth_month_sold ?? 0,
            'tenth_month_quantity_sold' => $productAgeing->tenth_month_sold ?? 0,
            'eleventh_month_quantity_sold' => $productAgeing->eleventh_month_sold ?? 0,
            'twelfth_month_quantity_sold' => $productAgeing->twelfth_month_sold ?? 0,
            'attributes' => $productService->getAttributesWithNameAndValueKey($product),
        ];
    }
}
