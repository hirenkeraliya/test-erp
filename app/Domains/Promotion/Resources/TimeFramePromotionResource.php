<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TimeFramePromotionResource extends JsonResource
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

        $endDate = '';

        if ($promotion->end_date) {
            /** @var Carbon $endDateFormat */
            $endDateFormat = Carbon::createFromFormat('Y-m-d', $promotion->end_date);
            $endDate = $endDateFormat->addDay()->format('Y-m-d');
        }

        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'start_date' => $promotion->start_date,
            'end_date' => $endDate,
            'link' => route('admin.promotions.index', [
                'id' => $promotion->id,
            ]),
        ];
    }
}
