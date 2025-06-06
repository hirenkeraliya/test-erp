<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Company\RoundOffConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStockOverviewTopSellingMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $member = $this->resource;

        $totalSales = $member->total_sales;
        $totalSales += RoundOffConfiguration::roundOffCalculationFor((string) $totalSales);

        return [
            'id' => $member->id,
            'name' => $member->getFullName(),
            'total_sales' => $totalSales,
        ];
    }
}
