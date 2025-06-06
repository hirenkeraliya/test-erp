<?php

declare(strict_types=1);

namespace App\Domains\Location\Resources;

use App\Domains\Company\RoundOffConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStockOverviewTopSellingLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $location = $this->resource;

        $totalSales = $location->total_sales;
        $totalSales += RoundOffConfiguration::roundOffCalculationFor((string) $totalSales);

        return [
            'id' => $location->id,
            'name' => $location->name,
            'total_sales' => $totalSales,
        ];
    }
}
