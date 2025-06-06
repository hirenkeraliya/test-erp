<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Services;

use App\CommonFunctions;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Models\SaleTarget;
use Carbon\Carbon;

class SaleTargetService
{
    public function addSaleTarget(SaleTargetData $saleTargetData, int $companyId): SaleTarget
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $saleTarget = $saleTargetQueries->addNew($saleTargetData, $companyId);
        $this->addDailyTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addCustomPeriodTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addYearTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addMonthsTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addWeeksTargetTimeFrame($saleTargetData, $saleTarget);

        return $saleTarget;
    }

    public function updateSaleTarget(SaleTargetData $saleTargetData, int $saleTargetId, int $companyId): void
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $saleTarget = $saleTargetQueries->getById($saleTargetId, $companyId);

        $saleTargetQueries->update($saleTargetData, $saleTarget);

        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);
        $saleAchievedTargetQueries->deleteBySaleTarget($saleTargetId);

        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
        $saleTargetTimeframeQueries->deleteBySaleTarget($saleTarget);

        $this->addDailyTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addCustomPeriodTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addYearTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addMonthsTargetTimeFrame($saleTargetData, $saleTarget);
        $this->addWeeksTargetTimeFrame($saleTargetData, $saleTarget);
    }

    public function addDailyTargetTimeFrame(SaleTargetData $saleTargetData, SaleTarget $saleTarget): void
    {
        if (! $saleTargetData->dates) {
            return;
        }

        if ($saleTargetData->time_interval_type !== TimeIntervalType::DAILY->value) {
            return;
        }

        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        $startDate = Carbon::parse($saleTargetData->dates[0]);
        $endDate = Carbon::parse($saleTargetData->dates[1]);
        $counts = range(0, $endDate->diffInDays($startDate));

        foreach ($counts as $count) {
            $currentDate = $startDate->copy()->addDays($count);
            $startDateFormatted = $currentDate->format('Y-m-d');
            $endDateFormatted = $currentDate->format('Y-m-d');

            if ($saleTargetData->amount_type === SaleTargetAmountTypes::AMOUNT->value) {
                $saleTargetTimeframeRecord['amount'] = $saleTargetData->amount;
            }

            if ($saleTargetData->amount_type === SaleTargetAmountTypes::PERCENTAGE->value) {
                $saleTargetTimeframeRecord['percentage'] = $saleTargetData->percentage;
                $saleTargetTimeframeRecord['amount'] = $saleTarget->amount;
            }

            $saleTargetTimeframeRecord['sale_target_id'] = $saleTarget->id;
            $saleTargetTimeframeRecord['target_label'] = TimeIntervalType::getFormattedCaseName(
                $saleTargetData->time_interval_type
            );
            $saleTargetTimeframeRecord['start_date'] = $startDateFormatted;
            $saleTargetTimeframeRecord['end_date'] = $endDateFormatted;
            $saleTargetTimeframeQueries->addNew($saleTargetTimeframeRecord);
        }
    }

    public function addCustomPeriodTargetTimeFrame(SaleTargetData $saleTargetData, SaleTarget $saleTarget): void
    {
        if (! $saleTargetData->dates) {
            return;
        }

        if ($saleTargetData->time_interval_type !== TimeIntervalType::CUSTOM_PERIOD->value) {
            return;
        }

        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        if ($saleTargetData->amount_type === SaleTargetAmountTypes::AMOUNT->value) {
            $saleTargetTimeframeRecord['amount'] = $saleTargetData->amount;
        }

        if ($saleTargetData->amount_type === SaleTargetAmountTypes::PERCENTAGE->value) {
            $saleTargetTimeframeRecord['percentage'] = $saleTargetData->percentage;
            $saleTargetTimeframeRecord['amount'] = $saleTarget->amount;
        }

        $saleTargetTimeframeRecord['sale_target_id'] = $saleTarget->id;
        $saleTargetTimeframeRecord['target_label'] = TimeIntervalType::getFormattedCaseName(
            $saleTargetData->time_interval_type
        );
        $saleTargetTimeframeRecord['start_date'] = $saleTargetData->dates[0];
        $saleTargetTimeframeRecord['end_date'] = $saleTargetData->dates[1];
        $saleTargetTimeframeQueries->addNew($saleTargetTimeframeRecord);
    }

    public function addYearTargetTimeFrame(SaleTargetData $saleTargetData, SaleTarget $saleTarget): void
    {
        if (! $saleTargetData->year) {
            return;
        }

        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        if ($saleTargetData->amount_type === SaleTargetAmountTypes::AMOUNT->value) {
            $saleTargetTimeframeRecord['amount'] = $saleTargetData->amount;
        }

        if ($saleTargetData->amount_type === SaleTargetAmountTypes::PERCENTAGE->value) {
            $saleTargetTimeframeRecord['percentage'] = $saleTargetData->percentage;
            $saleTargetTimeframeRecord['amount'] = $saleTarget->amount;
        }

        $saleTargetTimeframeRecord['sale_target_id'] = $saleTarget->id;
        $saleTargetTimeframeRecord['target_label'] = $saleTargetData->year;
        [$startDate, $endDate] = $this->getFirstAndLastDateOfYear($saleTargetData->year);
        $saleTargetTimeframeRecord['start_date'] = $startDate;
        $saleTargetTimeframeRecord['end_date'] = $endDate;
        $saleTargetTimeframeQueries->addNew($saleTargetTimeframeRecord);
    }

    public function addMonthsTargetTimeFrame(SaleTargetData $saleTargetData, SaleTarget $saleTarget): void
    {
        if (! $saleTargetData->month_tiers) {
            return;
        }

        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        foreach ($saleTargetData->month_tiers as $monthTier) {
            if (! $monthTier) {
                continue;
            }

            [$startDate, $endDate, $monthName] = $this->getFirstAndLastDateOfMonth(
                $monthTier['months']['month'],
                $monthTier['months']['year']
            );

            $saleTargetAmount = 0;

            if ($saleTargetData->amount_type === SaleTargetAmountTypes::AMOUNT->value) {
                $saleTargetAmount = $monthTier['amount'];
            }

            if ($saleTargetData->amount_type === SaleTargetAmountTypes::PERCENTAGE->value) {
                $saleTargetTimeframeRecord['percentage'] = $monthTier['percentage'];

                $saleTargetAmount = $this->calculateMonthlySaleTargetAmount(
                    $saleTargetData,
                    $saleTarget,
                    $startDate,
                    $endDate,
                    $monthTier
                );
            }

            $saleTargetTimeframeRecord['amount'] = CommonFunctions::numberFormat((float) $saleTargetAmount);
            $saleTargetTimeframeRecord['sale_target_id'] = $saleTarget->id;
            $saleTargetTimeframeRecord['target_label'] = $monthName;
            $saleTargetTimeframeRecord['start_date'] = $startDate;
            $saleTargetTimeframeRecord['end_date'] = $endDate;
            $saleTargetTimeframeQueries->addNew($saleTargetTimeframeRecord);
        }
    }

    public function addWeeksTargetTimeFrame(SaleTargetData $saleTargetData, SaleTarget $saleTarget): void
    {
        if (! $saleTargetData->week_tiers) {
            return;
        }

        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
        foreach ($saleTargetData->week_tiers as $weekTier) {
            if (! $weekTier) {
                continue;
            }

            $saleTargetAmount = 0;

            if ($saleTargetData->amount_type === SaleTargetAmountTypes::AMOUNT->value) {
                $saleTargetAmount = $weekTier['amount'];
            }

            if ($saleTargetData->amount_type === SaleTargetAmountTypes::PERCENTAGE->value) {
                $saleTargetTimeframeRecord['percentage'] = $weekTier['percentage'];

                $saleTargetAmount = $this->calculateWeeklySaleTargetAmount($saleTargetData, $saleTarget, $weekTier);
            }

            $saleTargetTimeframeRecord['amount'] = CommonFunctions::numberFormat((float) $saleTargetAmount);
            $saleTargetTimeframeRecord['sale_target_id'] = $saleTarget->id;
            $saleTargetTimeframeRecord['target_label'] = $this->getWeekNumbers($weekTier['weeks'][0]);
            $saleTargetTimeframeRecord['start_date'] = $weekTier['weeks'][0];
            $saleTargetTimeframeRecord['end_date'] = $weekTier['weeks'][1];
            $saleTargetTimeframeQueries->addNew($saleTargetTimeframeRecord);
        }
    }

    public function getFirstAndLastDateOfYear(int $year): array
    {
        /** @var Carbon $startOfYear */
        $startOfYear = Carbon::create($year, 1, 1);

        /** @var Carbon $endOfYear */
        $endOfYear = Carbon::create($year, 12, 31);

        $startDate = $startOfYear->toDateString();
        $endDate = $endOfYear->toDateString();

        return [$startDate, $endDate];
    }

    public function getFirstAndLastDateOfMonth(int $month, int $year): array
    {
        /** @var Carbon $date */
        $date = Carbon::create($year, $month + 1, 1);

        $monthName = $date->format('F');
        $startDate = $date->firstOfMonth()->toDateString();
        $endDate = $date->endOfMonth()->toDateString();

        return [$startDate, $endDate, $monthName];
    }

    public function getWeekNumbers(string $startDate): string
    {
        /** @var Carbon $startDate */
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate);

        return 'Week-' . $startDate->weekOfMonth;
    }

    private function calculateMonthlySaleTargetAmount(
        SaleTargetData $saleTargetData,
        SaleTarget $saleTarget,
        string $startDate,
        string $endDate,
        array $monthTier
    ): float {
        $saleTargetSaleAmountService = resolve(SaleTargetSaleAmountService::class);

        $saleTargetAmount = 0;

        if ($saleTargetData->target_type === TargetType::COMPANY_WISE->value) {
            $totalSaleAmount = $saleTargetSaleAmountService->getTotalSaleAmountForCompany(
                $startDate,
                $endDate,
                $saleTarget->company_id
            );

            $totalSaleReturnAmount = $saleTargetSaleAmountService->getTotalSaleReturnAmountForCompany(
                $startDate,
                $endDate,
                $saleTarget->company_id
            );
            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $monthTier['percentage'] / 100;
            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        if ($saleTargetData->target_type === TargetType::PROMOTER_WISE->value) {
            /** @var array $promoterIds */
            $promoterIds = $saleTargetData->promoter_ids;

            $totalSaleAmountWithoutReturn = $saleTargetSaleAmountService->getPromoterTotalSaleAmount(
                $startDate,
                $endDate,
                $promoterIds
            );
            $totalAmount = $totalSaleAmountWithoutReturn * $monthTier['percentage'] / 100;
            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        if ($saleTargetData->target_type === TargetType::STORE_WISE->value) {
            /** @var array $locationIds */
            $locationIds = $saleTargetData->location_ids;

            $totalSaleAmount = $saleTargetSaleAmountService->getTotalSaleAmount($startDate, $endDate, $locationIds);

            $totalSaleReturnAmount = $saleTargetSaleAmountService->getTotalSaleReturnAmount(
                $startDate,
                $endDate,
                $locationIds
            );

            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $monthTier['percentage'] / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return $saleTargetAmount;
    }

    private function calculateWeeklySaleTargetAmount(
        SaleTargetData $saleTargetData,
        SaleTarget $saleTarget,
        array $weekTier
    ): float {
        $saleTargetSaleAmountService = resolve(SaleTargetSaleAmountService::class);

        $saleTargetAmount = 0;

        if ($saleTargetData->target_type === TargetType::COMPANY_WISE->value) {
            $totalSaleAmount = $saleTargetSaleAmountService->getTotalSaleAmountForCompany(
                $weekTier['weeks'][0],
                $weekTier['weeks'][1],
                $saleTarget->company_id
            );

            $totalSaleReturnAmount = $saleTargetSaleAmountService->getTotalSaleReturnAmountForCompany(
                $weekTier['weeks'][0],
                $weekTier['weeks'][1],
                $saleTarget->company_id
            );

            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $weekTier['percentage'] / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        if ($saleTargetData->target_type === TargetType::STORE_WISE->value) {
            /** @var array $locationIds */
            $locationIds = $saleTargetData->location_ids;

            $totalSaleAmount = $saleTargetSaleAmountService->getTotalSaleAmount(
                $weekTier['weeks'][0],
                $weekTier['weeks'][1],
                $locationIds
            );

            $totalSaleReturnAmount = $saleTargetSaleAmountService->getTotalSaleReturnAmount(
                $weekTier['weeks'][0],
                $weekTier['weeks'][1],
                $locationIds
            );

            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $weekTier['percentage'] / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        if ($saleTargetData->target_type === TargetType::PROMOTER_WISE->value) {
            /** @var array $promoterIds */
            $promoterIds = $saleTargetData->promoter_ids;

            $totalSaleAmountWithoutReturn = $saleTargetSaleAmountService->getPromoterTotalSaleAmount(
                $weekTier['weeks'][0],
                $weekTier['weeks'][1],
                $promoterIds
            );

            $totalAmount = $totalSaleAmountWithoutReturn * $weekTier['percentage'] / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return $saleTargetAmount;
    }
}
