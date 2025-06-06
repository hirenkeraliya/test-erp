<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Resources;

use App\Domains\Company\RoundOffConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStockOverviewTopSellingPromoterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $promoter = $this->resource;

        $totalAmountSold = $promoter->total_amount_sold;
        $totalAmountSold += RoundOffConfiguration::roundOffCalculationFor((string) $totalAmountSold);

        return [
            'id' => $promoter->id,
            'name' => $promoter->first_name . ' ' . $promoter->last_name,
            'total_amount_sold' => $totalAmountSold,
            'locations' => $promoter->locations->pluck('id')->toArray(),
        ];
    }
}
