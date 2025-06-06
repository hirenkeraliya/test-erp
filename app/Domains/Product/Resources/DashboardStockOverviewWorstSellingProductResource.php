<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Company\RoundOffConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStockOverviewWorstSellingProductResource extends JsonResource
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

        $totalSales = $product->total_sales;
        $totalSales += RoundOffConfiguration::roundOffCalculationFor((string) $totalSales);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'total_sales' => $totalSales,
            'total_units_sold' => $product->total_units_sold,
            'image_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }
}
