<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Resources;

use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SaleTargetEditResource extends JsonResource
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

        /** @var Collection $saleTargetTimeframes */
        $saleTargetTimeframes = $saleTarget->saleTargetTimeframes;

        /** @var Collection $saleTargetPromoters */
        $saleTargetPromoters = $saleTarget->promoters;

        /** @var Collection $locations */
        $locations = $saleTarget->locations;

        $promoters = $saleTargetPromoters->map(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        return [
            'id' => $saleTarget->id,
            'name' => $saleTarget->name,
            'amount' => $saleTarget->amount,
            'amount_type' => $saleTarget->amount_type,
            'percentage' => $saleTarget->percentage,
            'target_type' => $saleTarget->target_type,
            'time_interval_type' => $saleTarget->time_interval_type,
            'status' => $saleTarget->status,
            'dates' => $this->preparedDate($saleTargetTimeframes, $saleTarget->time_interval_type),
            'promoters' => $promoters,
            'locations' => $locations,
            'location_ids' => $locations->pluck('id')->toArray(),
            'promoter_ids' => $promoters->pluck('id')->toArray(),
            'month_tiers' => $saleTarget->time_interval_type === TimeIntervalType::MONTHLY->value ? $this->preparedMonthDate(
                $saleTargetTimeframes
            ) : [],
            'year' => $saleTarget->time_interval_type === TimeIntervalType::YEARLY->value ?
                 $this->preparedYear($saleTargetTimeframes, $saleTarget->time_interval_type) : null,
            'week_tiers' => $saleTarget->time_interval_type === TimeIntervalType::WEEKLY->value ? $this->preparedWeekDate(
                $saleTargetTimeframes
            ) : [],
        ];
    }

    public function preparedMonthDate(Collection $saleTargetTimeframes): Collection
    {
        return $saleTargetTimeframes->map(function ($saleTargetTimeframe): array {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $saleTargetTimeframe->start_date);
            $result = [
                'months' => [
                    'month' => $date->month - 1,
                    'year' => $date->year,
                ],
            ];
            if (null !== $saleTargetTimeframe->amount) {
                $result['amount'] = $saleTargetTimeframe->amount;
            }

            if (null !== $saleTargetTimeframe->percentage) {
                $result['percentage'] = $saleTargetTimeframe->percentage;
            }

            return $result;
        });
    }

    public function preparedDate(Collection $saleTargetTimeframes, int $timeIntervalType): array
    {
        $firstSaleTargetTimeframe = $saleTargetTimeframes->sortBy('start_date')->first();

        if ($timeIntervalType === TimeIntervalType::CUSTOM_PERIOD->value && $firstSaleTargetTimeframe) {
            return [$firstSaleTargetTimeframe->start_date, $firstSaleTargetTimeframe->end_date];
        }

        $lastSaleTargetTimeframe = $saleTargetTimeframes->sortBy('start_date')->last();
        if (
            $timeIntervalType === TimeIntervalType::DAILY->value
            && $firstSaleTargetTimeframe
            && $lastSaleTargetTimeframe
        ) {
            return [$firstSaleTargetTimeframe->start_date, $lastSaleTargetTimeframe->end_date];
        }

        return [];
    }

    public function preparedYear(Collection $saleTargetTimeframes, int $timeIntervalType): string
    {
        return $saleTargetTimeframes->map(function ($saleTargetTimeframe) use ($timeIntervalType) {
            if ($timeIntervalType === TimeIntervalType::YEARLY->value) {
                return $saleTargetTimeframe->target_label;
            }
        })->first();
    }

    public function preparedWeekDate(Collection $saleTargetTimeframes): Collection
    {
        return $saleTargetTimeframes->map(function ($saleTargetTimeframe): array {
            $result = [
                'weeks' => [$saleTargetTimeframe->start_date, $saleTargetTimeframe->end_date],
            ];
            if (null !== $saleTargetTimeframe->amount) {
                $result['amount'] = $saleTargetTimeframe->amount;
            }

            if (null !== $saleTargetTimeframe->percentage) {
                $result['percentage'] = $saleTargetTimeframe->percentage;
            }

            return $result;
        });
    }
}
