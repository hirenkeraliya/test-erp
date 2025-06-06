<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Resources;

use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTargetTimeframe\Resources\SaleTargetTimeframeDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreManagerSaleTargetListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleTarget = $this->resource;

        return [
            'id' => $saleTarget->id,
            'name' => $saleTarget->name,
            'amount' => $saleTarget->amount,
            'target_type' => TargetType::getFormattedCaseName((int) $saleTarget->target_type),
            'time_interval_type' => TimeIntervalType::getFormattedCaseName((int) $saleTarget->time_interval_type),
            'sale_target_timeframe_details' => SaleTargetTimeframeDetailResource::collection(
                $saleTarget->saleTargetTimeframes
            ),
        ];
    }
}
