<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeFrameMonthlyPromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $promotion = $this->resource;

        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'month_date' => $promotion->monthly->pluck('month_date')->toArray(),
            'link' => route('admin.promotions.index', [
                'id' => $promotion->id,
            ]),
        ];
    }
}
