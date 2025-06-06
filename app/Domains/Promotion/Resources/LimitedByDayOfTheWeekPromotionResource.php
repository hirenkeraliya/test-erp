<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LimitedByDayOfTheWeekPromotionResource extends JsonResource
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
            'timeframe_type' => $this->timeFrameTypesWithDetails(),
            'link' => route('admin.promotions.index', [
                'id' => $promotion->id,
            ]),
        ];
    }

    private function timeFrameTypesWithDetails(): array
    {
        /** @var Promotion $promotion */
        $promotion = $this;

        $days = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];
        $selectedDays = implode(
            ', ',
            array_keys(array_intersect($days, $promotion->weekly->pluck('week_day')->toArray()))
        );

        return $this->convertDaysToAbbreviations($selectedDays);
    }

    private function convertDaysToAbbreviations(string $selectedDays): array
    {
        $dayMap = [
            'Sunday' => 'su',
            'Monday' => 'mo',
            'Tuesday' => 'tu',
            'Wednesday' => 'we',
            'Thursday' => 'th',
            'Friday' => 'fr',
            'Saturday' => 'sa',
        ];
        $daysArray = explode(',', $selectedDays);

        return array_map(fn ($day): string => trim($dayMap[trim($day)]), $daysArray);
    }
}
