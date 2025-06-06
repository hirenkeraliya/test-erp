<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Resources;

use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SalesTargetDetailsResourceForStoreManagerApp extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $salesTarget = $this->resource;

        /** @var Collection $salesTargetTimeframes */
        $salesTargetTimeframes = $salesTarget->saleTargetTimeframes;

        return [
            'id' => $salesTarget->id,
            'name' => $salesTarget->name,
            'amount' => $salesTarget->amount,
            'target_type' => TargetType::getFormattedCaseName((int) $salesTarget->target_type),
            'time_interval_type' => TimeIntervalType::getFormattedCaseName((int) $salesTarget->time_interval_type),
            'status' => $salesTarget->status,
            'sales_target_timeframes' => $salesTargetTimeframes,
        ];
    }
}
