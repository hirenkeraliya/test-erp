<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\CommonFunctions;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProfitsAndLossesReportListResource extends JsonResource
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

        $totalSales = $product->total_amount_sold ?? 0;
        $totalPurchaseCost = $product->total_purchase_cost ?? 0;
        $totalSaleReturns = $product->total_returned_amount ?? 0;

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
            'color' => config('app.product_variant') ? 'N/A' : $product->color?->name ?? 'N/A',
            'size' => config('app.product_variant') ? 'N/A' : $product->size?->name ?? 'N/A',
            'sub_department' => $product->sub_department_id ? SubDepartments::getFormattedCaseName(
                $product->sub_department_id
            ) : 'N/A',
            'unit_of_measure' => $product->unit_of_measure_name ?? 'N/A',
            'units_sold' => $product->total_quantity_sold ?? 0,
            'total_sales' => $totalSales,
            'units_returned' => $product->total_quantity_returned ?? 0,
            'total_sale_returns' => $totalSaleReturns,
            'total_purchase_cost' => CommonFunctions::numberFormat((float) $totalPurchaseCost),
            'total_profit_or_loss' => CommonFunctions::numberFormat(
                $totalSales - ($totalPurchaseCost + $totalSaleReturns)
            ),
            'location' => [
                /* @phpstan-ignore-next-line */
                'sale_location' => $product->location,
                /* @phpstan-ignore-next-line */
                'sale_return_location' => $product->location_name,
            ],
            /* @phpstan-ignore-next-line */
            'attributes' => config('app.product_variant') ? json_decode($product->product_variants, true) ?? [] : [],
        ];
    }
}
