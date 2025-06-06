<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Resources;

use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsAgeingBasedOnArticleNumberReportListResource extends JsonResource
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

        $request->get('age_of_product_type');
        $productAgeingAgeingReportService = resolve(ProductAgeingReportService::class);

        return [
            'id' => $product->id,
            'product' => $product->name,
            'article_number' => $product->article_number ?? 'N/A',
            'last_selling_date' => $productAgeingAgeingReportService->getLastSellingDate($productAgeing),
            'quantity_sold' => (float) $productAgeing->quantity_sold,
            'quantity_remaining' => (float) $productAgeing->quantity_remaining,
            'age_of_the_product' => $productAgeing->age_category_based_on_created_at . ' Days',
            'age_of_the_product_first_grn' => null === $productAgeing->age_category_based_on_first_goods_received_note ? 'N/A' : $productAgeing->age_category_based_on_first_goods_received_note . ' Days',
            'age_of_the_product_first_transfer_in' => null === $productAgeing->age_category_based_on_first_transfer_in ? 'N/A' : $productAgeing->age_category_based_on_first_transfer_in . ' Days',
            'created_at' => $productAgeingAgeingReportService->getCreatedAt($productAgeing),
            'first_grn_date' => $productAgeingAgeingReportService->getFirstGrnDate($productAgeing),
            'first_transfer_in_date' => $productAgeingAgeingReportService->getFirstTransferInDate($productAgeing),
        ];
    }
}
