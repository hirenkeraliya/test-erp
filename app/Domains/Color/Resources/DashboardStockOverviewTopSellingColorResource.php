<?php

declare(strict_types=1);

namespace App\Domains\Color\Resources;

use App\Domains\Company\RoundOffConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStockOverviewTopSellingColorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $color = $this->resource;

        $totalSales = $color->total_sales;
        $totalSales += RoundOffConfiguration::roundOffCalculationFor((string) $totalSales);

        return [
            'id' => $color->id,
            'name' => $color->name,
            'total_sales' => $totalSales,
            'total_units_sold' => $color->total_units_sold,
        ];
    }
}
