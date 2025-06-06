<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Resources;

use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreManagerProductsAgeingByMonthAndYearReportListResource extends JsonResource
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

        /** @var Location $location */
        $location = $productAgeing->location;

        $colorName = $product?->color->name ?? 'N/A';
        $sizeName = $product?->size->name ?? 'N/A';

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return [
            'id' => $product->id,
            'product' => $product->name,
            'location' => $location->getNameWithCode(),
            'upc' => $product->upc,
            'article_number' => $product->article_number ?? 'N/A',
            'color' => $colorName,
            'size' => $sizeName,
            'last_selling_date' => $productAgeingReportService->getLastSellingDate($productAgeing),
            'quantity_sold' => $productAgeing->quantity_sold,
            'quantity_remaining' => $productAgeing->quantity_remaining,
            'age_of_the_product' => $productAgeing->age_category . ' Days',
            'age_category' => $productAgeing->age_category,
            'created_at' => $productAgeingReportService->getCreatedAt($productAgeing),
            'first_grn_date' => $productAgeingReportService->getFirstGrnDate($productAgeing),
            'first_transfer_in_date' => $productAgeingReportService->getFirstTransferInDate($productAgeing),
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
        ];
    }
}
