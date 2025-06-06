<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductsReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Product $product */
        $product = $this;

        return [
            'id' => $product->id,
            'product' => $product->name,
            'upc' => $product->upc,
            'article_number' => $product->article_number ?? 'N/A',
            /* @phpstan-ignore-next-line */
            'categories' => $product->category_names ? explode(',', $product->category_names) : [],
            /* @phpstan-ignore-next-line */
            'brand' => $product->brand_name,
            'season' => $product->season_name ?? 'N/A',
            'department' => $product->department_name ?? 'N/A',
            'color' => config('app.product_variant') ? 'N/A' : $product->color_name ?? 'N/A',
            'size' => config('app.product_variant') ? 'N/A' : $product->size_name ?? 'N/A',
            'sub_department' => $product->sub_department_id ? SubDepartments::getFormattedCaseName(
                $product->sub_department_id
            ) : 'N/A',
            'unit_of_measure' => $product->unit_of_measure_name ?? 'N/A',
            'units_sold' => $product->sum_sale_quantity ?? 0,
            'total_sales' => $product->sum_sale_amount ?? 0,
            'units_returned' => $product->sum_sale_return_quantity ?? 0,
            'total_sale_returns' => $product->sum_sale_return_amount ?? 0,
            /* @phpstan-ignore-next-line */
            'location' => $product->location_name,
            /* @phpstan-ignore-next-line */
            'location_id' => $product->location_id,
            'verification_count' => $product->verification_count ?? 0,
            'verification_id' => $product->verification_id ?? null,
            /* @phpstan-ignore-next-line */
            'attributes' => config('app.product_variant') ? json_decode($product->product_variants, true) ?? [] : [],
        ];
    }
}
