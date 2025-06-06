<?php

declare(strict_types=1);

namespace App\Domains\SaleAchievedTarget\Resources;

use App\CommonFunctions;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\SaleAchievedTarget;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleTargetAchievedListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var SaleAchievedTarget $saleAchievedTarget */
        $saleAchievedTarget = $this;

        /** @var SaleTargetTimeframe $saleTargetTimeframe */
        $saleTargetTimeframe = $saleAchievedTarget->saleTargetTimeframe;

        /** @var SaleTarget $saleTarget */
        $saleTarget = $saleTargetTimeframe->saleTarget;

        /** @var Location|Promoter|Company $targetable */
        $targetable = $saleAchievedTarget->targetable;

        $name = null;

        if ($targetable instanceof Promoter) {
            /** @var Employee $employee */
            $employee = $targetable->employee;
            $name = $employee->getFullName();
        }

        if ($targetable instanceof Location) {
            $name = $targetable->name;
        }

        if ($targetable instanceof Company) {
            $name = $targetable->name;
        }

        return [
            'id' => $saleAchievedTarget->id,
            'target_value' => $saleAchievedTarget->target_value,
            'achieved_value' => $saleAchievedTarget->achieved_value,
            'target_table_type' => $saleAchievedTarget->targetable_type ? CommonFunctions::stringTitleLowerCase(
                $saleAchievedTarget->targetable_type
            ) : 'N/A',
            'target_name' => $name,
            'name' => $saleTarget->name,
            'time_interval_type' => TimeIntervalType::getFormattedCaseName($saleTarget->time_interval_type),
            'date' => $this->getDate(
                $saleTarget->time_interval_type,
                $saleTargetTimeframe->start_date,
                $saleTargetTimeframe->end_date
            ),
            'status' => $this->getAchievedPercentage(
                (float) $saleAchievedTarget->achieved_value,
                (float) $saleAchievedTarget->target_value
            ),
        ];
    }

    private function getAchievedPercentage(float $achievedValue, float $targetValue): int
    {
        $achievedRatio = (int) ($achievedValue / $targetValue) * 100;

        return $achievedRatio >= 100 ? 100 : $achievedRatio;
    }

    private function getDate(int $timeIntervalType, string $startDate, string $endDate): string
    {
        if ($timeIntervalType === TimeIntervalType::DAILY->value) {
            return 'Date: ' . $startDate;
        }

        if ($timeIntervalType === TimeIntervalType::WEEKLY->value) {
            return 'From: ' . $startDate . ' To: ' . $endDate;
        }

        if ($timeIntervalType === TimeIntervalType::MONTHLY->value) {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $startDate);

            return 'Month: ' . $date->format('F') . ' - ' . $date->format('Y');
        }

        if ($timeIntervalType === TimeIntervalType::YEARLY->value) {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $startDate);

            return 'Year: ' . $date->format('Y');
        }

        return 'From: ' . $startDate . ' To: ' . $endDate;
    }
}
