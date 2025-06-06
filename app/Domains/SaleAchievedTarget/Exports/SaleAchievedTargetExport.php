<?php

declare(strict_types=1);

namespace App\Domains\SaleAchievedTarget\Exports;

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
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SaleAchievedTargetExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $saleAchievedTargets
    ) {
    }

    public function collection(): Collection
    {
        return $this->saleAchievedTargets->map(function (SaleAchievedTarget $saleAchievedTarget): array {
            /** @var Location|Promoter|Company $targetable */
            $targetable = $saleAchievedTarget->targetable;

            /** @var SaleTargetTimeframe $saleTargetTimeframe */
            $saleTargetTimeframe = $saleAchievedTarget->saleTargetTimeframe;

            /** @var SaleTarget $saleTarget */
            $saleTarget = $saleTargetTimeframe->saleTarget;

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
                'name' => $saleTarget->name,
                'target_details' => 'Name: ' . $name . ' Type: ' . CommonFunctions::stringTitleLowerCase(
                    $saleAchievedTarget->targetable_type
                ),
                'time_interval_details' => 'Type: ' . TimeIntervalType::getFormattedCaseName(
                    $saleTarget->time_interval_type
                ) . $this->getDate(
                    $saleTarget->time_interval_type,
                    $saleTargetTimeframe->start_date,
                    $saleTargetTimeframe->end_date
                ),
                'target_value' => $saleAchievedTarget->target_value,
                'achieved_value' => $saleAchievedTarget->achieved_value,
                'status' => $this->getAchievedPercentage(
                    (float) $saleAchievedTarget->achieved_value,
                    (float) $saleAchievedTarget->target_value
                ),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sale Target Name',
            'Target Details',
            'Time Interval Details',
            'Target',
            'Achieved',
            'Achieved Ratio (%)',
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

            return 'Month: ' . $date->format('Y') . ' - ' . $date->format('Y');
        }

        if ($timeIntervalType === TimeIntervalType::YEARLY->value) {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $startDate);

            return 'Year: ' . $date->format('Y');
        }

        return 'From: ' . $startDate . ' To: ' . $endDate;
    }
}
