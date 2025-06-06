<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TimeFrameHourOfDayPromotionResource extends JsonResource
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
            'start_date' => $promotion->start_date,
            'start_time' => $promotion->start_time,
            'end_time' => $promotion->end_time,
            'month_date' => $promotion->monthly->pluck('month_date')->toArray(),
            'start_datetime' => Carbon::parse(sprintf('%s %s', $promotion->start_date, $promotion->start_time))->format(
                'Y-m-d\TH:i:s'
            ),
            'end_datetime' => Carbon::parse(sprintf('%s %s', $promotion->start_date, $promotion->end_time))->format(
                'Y-m-d\TH:i:s'
            ),
            'link' => route('admin.promotions.index', [
                'id' => $promotion->id,
            ]),
        ];
    }
}
