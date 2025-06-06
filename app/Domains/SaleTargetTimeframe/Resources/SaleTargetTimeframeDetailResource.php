<?php

declare(strict_types=1);

namespace App\Domains\SaleTargetTimeframe\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleTargetTimeframeDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleTargetTimeframe = $this->resource;

        $startDate = Carbon::createFromFormat('Y-m-d', $saleTargetTimeframe->start_date);
        $endDate = Carbon::createFromFormat('Y-m-d', $saleTargetTimeframe->end_date);

        return [
            'start_date' => $startDate ? $startDate->format('d-m-Y') : '',
            'end_date' => $endDate ? $endDate->format('d-m-Y') : '',
            'target_label' => $saleTargetTimeframe->target_label,
        ];
    }
}
