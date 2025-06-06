<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Services;

use App\CommonFunctions;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;

class SaleTargetSaleAmountService
{
    public function handleSaleTargetData(SaleTargetData $saleTargetData, int $companyId): float
    {
        if ($saleTargetData->percentage && $saleTargetData->amount_type === SaleTargetAmountTypes::PERCENTAGE->value) {
            if ($saleTargetData->location_ids && $saleTargetData->target_type === TargetType::STORE_WISE->value) {
                return $this->calculateStoreWiseTargetAmount(
                    $saleTargetData,
                    $saleTargetData->location_ids,
                    $saleTargetData->percentage
                );
            }

            if ($saleTargetData->promoter_ids && $saleTargetData->target_type === TargetType::PROMOTER_WISE->value) {
                return $this->calculatePromoterWiseTargetAmount(
                    $saleTargetData,
                    $saleTargetData->promoter_ids,
                    $saleTargetData->percentage
                );
            }

            if ($saleTargetData->target_type === TargetType::COMPANY_WISE->value) {
                return $this->calculateCompanyWiseTargetAmount(
                    $saleTargetData,
                    $companyId,
                    $saleTargetData->percentage
                );
            }
        }

        return (float) $saleTargetData->amount;
    }

    public function storeWiseDailyAndCustomSaleTargetAmount(array $dates, array $locationIds, float $percentage): float
    {
        $totalSalesAmount = $this->getTotalSaleAmount($dates[0], $dates[1], $locationIds);

        $saleReturnsAmount = $this->getTotalSaleReturnAmount($dates[0], $dates[1], $locationIds);

        $totalSaleAmountWithoutReturn = $totalSalesAmount - $saleReturnsAmount;
        $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

        $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;

        return CommonFunctions::numberFormat($saleTargetAmount);
    }

    public function getTotalSaleAmount(string $startDate, string $endDate, array $locationIds): float
    {
        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getTotalAmountForSaleStoreTarget($startDate, $endDate, $locationIds);

        return $sales->sum('total_sales_amount');
    }

    public function getTotalSaleReturnAmount(string $startDate, string $endDate, array $locationIds): float
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getTotalAmountForSaleStoreTarget($startDate, $endDate, $locationIds);

        return $saleReturns->sum('total_return_amount');
    }

    public function storeWiseMonthlySaleTargetAmount(array $monthTires, array $locationIds, float $percentage): float
    {
        $saleTargetService = resolve(SaleTargetService::class);
        $saleTargetAmount = 0;
        foreach ($monthTires as $monthTire) {
            if (! $monthTire) {
                continue;
            }

            [$startDate, $endDate] = $saleTargetService->getFirstAndLastDateOfMonth(
                $monthTire['months']['month'],
                $monthTire['months']['year']
            );

            $totalSaleAmount = $this->getTotalSaleAmount($startDate, $endDate, $locationIds);

            $totalSaleReturnAmount = $this->getTotalSaleReturnAmount($startDate, $endDate, $locationIds);

            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return CommonFunctions::numberFormat((float) $saleTargetAmount);
    }

    public function storeWiseWeeklySaleTargetAmount(array $weekTires, array $locationIds, float $percentage): float
    {
        $saleTargetAmount = 0;

        foreach ($weekTires as $weekTire) {
            if (! $weekTire) {
                continue;
            }

            $totalSaleAmount = $this->getTotalSaleAmount($weekTire['weeks'][0], $weekTire['weeks'][1], $locationIds);

            $totalSaleReturnAmount = $this->getTotalSaleReturnAmount(
                $weekTire['weeks'][0],
                $weekTire['weeks'][1],
                $locationIds
            );

            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return CommonFunctions::numberFormat((float) $saleTargetAmount);
    }

    public function storeWiseYearlySaleTargetAmount(int $year, array $locationIds, float $percentage): float
    {
        $saleTargetService = resolve(SaleTargetService::class);
        [$startDate, $endDate] = $saleTargetService->getFirstAndLastDateOfYear($year);

        $totalSalesAmount = $this->getTotalSaleAmount($startDate, $endDate, $locationIds);

        $saleReturnsAmount = $this->getTotalSaleReturnAmount($startDate, $endDate, $locationIds);

        $totalSaleAmountWithoutReturn = $totalSalesAmount - $saleReturnsAmount;
        $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

        $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;

        return CommonFunctions::numberFormat($saleTargetAmount);
    }

    public function promoterWiseDailyAndCustomSaleTargetAmount(
        array $dates,
        array $promoterIds,
        float $percentage
    ): float {
        $totalSaleAmountWithoutReturn = $this->getPromoterTotalSaleAmount($dates[0], $dates[1], $promoterIds);
        $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

        $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;

        return CommonFunctions::numberFormat($saleTargetAmount);
    }

    public function promoterWiseWeeklySaleTargetAmount(array $weekTires, array $promoterIds, float $percentage): float
    {
        $saleTargetAmount = 0;

        foreach ($weekTires as $weekTire) {
            if (! $weekTire) {
                continue;
            }

            $totalSaleAmountWithoutReturn = $this->getPromoterTotalSaleAmount(
                $weekTire['weeks'][0],
                $weekTire['weeks'][1],
                $promoterIds
            );

            $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return CommonFunctions::numberFormat((float) $saleTargetAmount);
    }

    public function promoterWiseMonthlySaleTargetAmount(array $monthTires, array $promoterIds, float $percentage): float
    {
        $saleTargetService = resolve(SaleTargetService::class);
        $saleTargetAmount = 0;
        foreach ($monthTires as $monthTire) {
            if (! $monthTire) {
                continue;
            }

            [$startDate, $endDate] = $saleTargetService->getFirstAndLastDateOfMonth(
                $monthTire['months']['month'],
                $monthTire['months']['year']
            );

            $totalSaleAmountWithoutReturn = $this->getPromoterTotalSaleAmount($startDate, $endDate, $promoterIds);
            $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return CommonFunctions::numberFormat((float) $saleTargetAmount);
    }

    public function promoterWiseYearlySaleTargetAmount(int $year, array $promoterIds, float $percentage): float
    {
        $saleTargetService = resolve(SaleTargetService::class);
        [$startDate, $endDate] = $saleTargetService->getFirstAndLastDateOfYear($year);

        $totalSaleAmountWithoutReturn = $this->getPromoterTotalSaleAmount($startDate, $endDate, $promoterIds);
        $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

        $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;

        return CommonFunctions::numberFormat($saleTargetAmount);
    }

    public function getPromoterTotalSaleAmount(string $startDate, string $endDate, array $promoterIds): float
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getTotalAmountForSalePromoterTarget($startDate, $endDate, $promoterIds);

        return $promoters->sum('amount_sold');
    }

    public function companyWiseDailyAndCustomSaleTargetAmount(array $dates, int $companyId, float $percentage): float
    {
        $totalSalesAmount = $this->getTotalSaleAmountForCompany($dates[0], $dates[1], $companyId);

        $saleReturnsAmount = $this->getTotalSaleReturnAmountForCompany($dates[0], $dates[1], $companyId);

        $totalSaleAmountWithoutReturn = $totalSalesAmount - $saleReturnsAmount;
        $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

        $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;

        return CommonFunctions::numberFormat($saleTargetAmount);
    }

    public function companyWiseMonthlySaleTargetAmount(array $monthTires, int $companyId, float $percentage): float
    {
        $saleTargetService = resolve(SaleTargetService::class);

        $saleTargetAmount = 0;
        foreach ($monthTires as $monthTire) {
            if (! $monthTire) {
                continue;
            }

            [$startDate, $endDate] = $saleTargetService->getFirstAndLastDateOfMonth(
                $monthTire['months']['month'],
                $monthTire['months']['year']
            );

            $totalSaleAmount = $this->getTotalSaleAmountForCompany($startDate, $endDate, $companyId);

            $totalSaleReturnAmount = $this->getTotalSaleReturnAmountForCompany($startDate, $endDate, $companyId);

            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return CommonFunctions::numberFormat((float) $saleTargetAmount);
    }

    public function companyWiseWeeklySaleTargetAmount(array $weekTires, int $companyId, float $percentage): float
    {
        $saleTargetAmount = 0;

        foreach ($weekTires as $weekTire) {
            if (! $weekTire) {
                continue;
            }

            $totalSaleAmount = $this->getTotalSaleAmountForCompany(
                $weekTire['weeks'][0],
                $weekTire['weeks'][1],
                $companyId
            );

            $totalSaleReturnAmount = $this->getTotalSaleReturnAmountForCompany(
                $weekTire['weeks'][0],
                $weekTire['weeks'][1],
                $companyId
            );

            $totalSaleAmountWithoutReturn = $totalSaleAmount - $totalSaleReturnAmount;
            $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

            $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;
        }

        return CommonFunctions::numberFormat((float) $saleTargetAmount);
    }

    public function companyWiseYearlySaleTargetAmount(int $year, int $companyId, float $percentage): float
    {
        $saleTargetService = resolve(SaleTargetService::class);
        [$startDate, $endDate] = $saleTargetService->getFirstAndLastDateOfYear($year);

        $totalSalesAmount = $this->getTotalSaleAmountForCompany($startDate, $endDate, $companyId);

        $saleReturnsAmount = $this->getTotalSaleReturnAmountForCompany($startDate, $endDate, $companyId);

        $totalSaleAmountWithoutReturn = $totalSalesAmount - $saleReturnsAmount;
        $totalAmount = $totalSaleAmountWithoutReturn * $percentage / 100;

        $saleTargetAmount = $totalSaleAmountWithoutReturn + $totalAmount;

        return CommonFunctions::numberFormat($saleTargetAmount);
    }

    public function getTotalSaleAmountForCompany(string $startDate, string $endDate, int $companyId): float
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getTotalAmountForSaleCompanyTarget($startDate, $endDate, $companyId);

        /* @phpstan-ignore-next-line */
        return (float) $sale->total_sales_amount;
    }

    public function getTotalSaleReturnAmountForCompany(string $startDate, string $endDate, int $companyId): float
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturn = $saleReturnQueries->getTotalAmountForSaleCompanyTarget($startDate, $endDate, $companyId);

        /* @phpstan-ignore-next-line */
        return (float) $saleReturn->total_return_amount;
    }

    private function calculateStoreWiseTargetAmount(
        SaleTargetData $saleTargetData,
        array $locationIds,
        float $percentage
    ): float {
        if ($saleTargetData->dates && ($saleTargetData->time_interval_type === TimeIntervalType::DAILY->value || $saleTargetData->time_interval_type === TimeIntervalType::CUSTOM_PERIOD->value)) {
            return $this->storeWiseDailyAndCustomSaleTargetAmount($saleTargetData->dates, $locationIds, $percentage);
        }

        if ($saleTargetData->month_tiers && $saleTargetData->time_interval_type === TimeIntervalType::MONTHLY->value) {
            return $this->storeWiseMonthlySaleTargetAmount($saleTargetData->month_tiers, $locationIds, $percentage);
        }

        if ($saleTargetData->week_tiers && $saleTargetData->time_interval_type === TimeIntervalType::WEEKLY->value) {
            return $this->storeWiseWeeklySaleTargetAmount($saleTargetData->week_tiers, $locationIds, $percentage);
        }

        if (! $saleTargetData->year) {
            return 0.0;
        }

        if ($saleTargetData->time_interval_type !== TimeIntervalType::YEARLY->value) {
            return 0.0;
        }

        return $this->storeWiseYearlySaleTargetAmount($saleTargetData->year, $locationIds, $percentage);
    }

    private function calculatePromoterWiseTargetAmount(
        SaleTargetData $saleTargetData,
        array $promoterIds,
        float $percentage
    ): float {
        if ($saleTargetData->dates && ($saleTargetData->time_interval_type === TimeIntervalType::DAILY->value || $saleTargetData->time_interval_type === TimeIntervalType::CUSTOM_PERIOD->value)) {
            return $this->promoterWiseDailyAndCustomSaleTargetAmount(
                $saleTargetData->dates,
                $promoterIds,
                $percentage
            );
        }

        if ($saleTargetData->month_tiers && $saleTargetData->time_interval_type === TimeIntervalType::MONTHLY->value) {
            return $this->promoterWiseMonthlySaleTargetAmount(
                $saleTargetData->month_tiers,
                $promoterIds,
                $percentage
            );
        }

        if ($saleTargetData->week_tiers && $saleTargetData->time_interval_type === TimeIntervalType::WEEKLY->value) {
            return $this->promoterWiseWeeklySaleTargetAmount(
                $saleTargetData->week_tiers,
                $promoterIds,
                $percentage
            );
        }

        if (! $saleTargetData->year) {
            return 0.0;
        }

        if ($saleTargetData->time_interval_type !== TimeIntervalType::YEARLY->value) {
            return 0.0;
        }

        return $this->promoterWiseYearlySaleTargetAmount($saleTargetData->year, $promoterIds, $percentage);
    }

    private function calculateCompanyWiseTargetAmount(
        SaleTargetData $saleTargetData,
        int $companyId,
        float $percentage
    ): float {
        if (
            $saleTargetData->dates &&
            (
                $saleTargetData->time_interval_type === TimeIntervalType::DAILY->value ||
                $saleTargetData->time_interval_type === TimeIntervalType::CUSTOM_PERIOD->value
            )
        ) {
            return $this->companyWiseDailyAndCustomSaleTargetAmount(
                $saleTargetData->dates,
                $companyId,
                $percentage
            );
        }

        if ($saleTargetData->year && $saleTargetData->time_interval_type === TimeIntervalType::YEARLY->value) {
            return $this->companyWiseYearlySaleTargetAmount($saleTargetData->year, $companyId, $percentage);
        }

        if ($saleTargetData->month_tiers && $saleTargetData->time_interval_type === TimeIntervalType::MONTHLY->value) {
            return $this->companyWiseMonthlySaleTargetAmount($saleTargetData->month_tiers, $companyId, $percentage);
        }

        if (! $saleTargetData->week_tiers) {
            return 0.0;
        }

        if ($saleTargetData->time_interval_type !== TimeIntervalType::WEEKLY->value) {
            return 0.0;
        }

        return $this->companyWiseWeeklySaleTargetAmount($saleTargetData->week_tiers, $companyId, $percentage);
    }
}
