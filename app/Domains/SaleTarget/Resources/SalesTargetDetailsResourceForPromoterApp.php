<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Resources;

use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\SaleAchievedTarget;
use App\Models\SaleTargetTimeframe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SalesTargetDetailsResourceForPromoterApp extends JsonResource
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
            'sale_targets' => $salesTarget->promoters->first()->targetable->isNotEmpty() ? $this->getSalesTargetAchievements(
                $salesTarget->promoters->first()->targetable
            ) : $this->getTimeFrames($salesTargetTimeframes),
        ];
    }

    private function getSalesTargetAchievements(Collection $saleAchievementTargets): array
    {
        return $saleAchievementTargets->map(function (SaleAchievedTarget $saleAchievementTarget): array {
            /** @var SaleTargetTimeframe $saleTargetTimeframe */
            $saleTargetTimeframe = $saleAchievementTarget->saleTargetTimeframe;

            return [
                'target_label' => $saleTargetTimeframe->target_label,
                'start_date' => $saleTargetTimeframe->start_date,
                'end_date' => $saleTargetTimeframe->end_date,
                'amount' => $saleTargetTimeframe->amount,
                'percentage' => $saleTargetTimeframe->percentage,
                'target_value' => $saleAchievementTarget->target_value,
                'achieved_value' => $saleAchievementTarget->achieved_value,
            ];
        })->toArray();
    }

    private function getTimeFrames(Collection $salesTargetTimeframes): array
    {
        return $salesTargetTimeframes->map(fn (SaleTargetTimeframe $saleTargetTimeframe): array => [
            'target_label' => $saleTargetTimeframe->target_label,
            'start_date' => $saleTargetTimeframe->start_date,
            'end_date' => $saleTargetTimeframe->end_date,
            'amount' => $saleTargetTimeframe->amount,
            'percentage' => $saleTargetTimeframe->percentage,
            'target_value' => null,
            'achieved_value' => null,
        ])->toArray();
    }
}
