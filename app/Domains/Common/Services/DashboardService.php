<?php

declare(strict_types=1);

namespace App\Domains\Common\Services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\PastYearData\PastYearDataQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PurchaseOrder\Enums\DashboardPurchaseOrderStatuses;
use App\Domains\PurchaseOrder\Enums\DashboardPurchaseRequestStatuses;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Enums\TransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesJob;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Domains\Style\StyleQueries;
use App\Models\Employee;
use App\Models\PastYearData;
use App\Models\Promoter;
use App\Models\StoreWiseDailyTotal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getTodaySalesDetails(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh
    ): array {
        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $returnData = [
            'totalAmount' => 0,
            'totalSalesCount' => 0,
            'totalUnitsSold' => 0,
        ];
        if (now()->format('Y-m-d') === $date) {
            $saleItemQueries = resolve(SaleItemQueries::class);
            $todaySaleDetails = $saleItemQueries->getCachedTodaySalesForDashboard(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $date,
                $refresh,
            );

            $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
            $todaySaleReturnDetails = $saleReturnItemQueries->getCachedTodaySaleReturnsForDashboard(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $date,
                $refresh,
            );

            $returnData['totalAmount'] = $todaySaleDetails['total_amount'] - $todaySaleReturnDetails['return_amount'];
            $returnData['totalSalesCount'] = $todaySaleDetails['total_sales_count'] ?? 0;
            $returnData['totalUnitsSold'] = $todaySaleDetails['total_units_sold'] - $todaySaleReturnDetails['return_units'];
        }

        if (now()->format('Y-m-d') !== $date) {
            $todaySaleDetails = $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
                'today-sale',
                $date,
                $date,
                $companyId,
                $locationId,
                $brandId,
                $refresh
            );

            $returnData['totalAmount'] = $todaySaleDetails['total_amount'] ?? 0;
            $returnData['totalSalesCount'] = $todaySaleDetails['total_sales_count'] ?? 0;
            $returnData['totalUnitsSold'] = $todaySaleDetails['total_units_sold'] ?? 0;
        }

        /** @var Carbon $selectedDate */
        $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

        $previousDaySaleDetails = $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
            'previous-day-sale',
            $selectedDate->subDay()->format('Y-m-d'),
            $selectedDate->format('Y-m-d'),
            $companyId,
            $locationId,
            $brandId,
            $refresh
        );

        $previousDaySaleTotalAmount = ($previousDaySaleDetails['total_amount'] ?? 0);

        $returnData['todayTotalSalePercentage'] = 0.00;
        if ($previousDaySaleTotalAmount > 0) {
            $returnData['todayTotalSalePercentage'] = CommonFunctions::numberFormat(
                ($returnData['totalAmount'] - $previousDaySaleTotalAmount) * 100 / $previousDaySaleTotalAmount
            );
        }

        $returnData['todayUpt'] = 0.00;
        if ($returnData['totalSalesCount'] > 0) {
            $returnData['todayUpt'] = CommonFunctions::numberFormat(
                $returnData['totalUnitsSold'] / $returnData['totalSalesCount']
            );
        }

        $returnData['todayAtv'] = 0.00;
        if ($returnData['totalSalesCount'] > 0) {
            $returnData['todayAtv'] = CommonFunctions::numberFormat(
                $returnData['totalAmount'] / $returnData['totalSalesCount']
            );
        }

        /** @var Carbon $selectedDate */
        $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

        $previousYearTodayDaySales = $this->getLastYearSaleData(
            $companyId,
            $locationId,
            $selectedDate->subYear()->format('Y-m-d'),
            $selectedDate->format('Y-m-d'),
            $brandId,
            $refresh
        );

        $previousYearTodaySaleAmount = ($previousYearTodayDaySales['total_amount'] ?? 0);

        $returnData['previousYearTodaySalePercentage'] = 0.00;
        $returnData['previousYearTodaySaleAmount'] = $previousYearTodaySaleAmount;
        if ($previousYearTodaySaleAmount > 0) {
            $returnData['previousYearTodaySalePercentage'] = CommonFunctions::numberFormat(
                ($returnData['totalAmount'] - $previousYearTodaySaleAmount) * 100 / $previousYearTodaySaleAmount
            );
        }

        $returnData['totalAmount'] += RoundOffConfiguration::roundOffCalculationFor(
            (string) $returnData['totalAmount']
        );

        $returnData['previousYearTodaySaleAmount'] += RoundOffConfiguration::roundOffCalculationFor(
            (string) $returnData['previousYearTodaySaleAmount']
        );

        $returnData['todayAtv'] += RoundOffConfiguration::roundOffCalculationFor((string) $returnData['todayAtv']);

        return $returnData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getThisMonthSalesDetails(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh
    ): array {
        /** @var Carbon $selectedDate */
        $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

        if ($refresh) {
            StoreWiseDailySalesJob::dispatch()->onQueue('high');
        }

        $returnData = [];
        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $thisMonthSalesDetails = $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
            'this-month-sales',
            $selectedDate->startOfMonth()->format('Y-m-d'),
            $date,
            $companyId,
            $locationId,
            $brandId,
            $refresh
        );

        $thisMonthSales = $this->addTodaySalesDetails($thisMonthSalesDetails);

        $returnData['totalAmount'] = $thisMonthSales['totalAmount'] ?? 0;
        $returnData['totalSalesCount'] = $thisMonthSales['totalSalesCount'] ?? 0;
        $returnData['totalUnitsSold'] = $thisMonthSales['totalUnitsSold'] ?? 0;

        /** @var Carbon $previousMonthDate */
        $previousMonthDate = Carbon::createFromFormat('Y-m-d', $date);

        $previousMonthSalesDetails = $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
            'previous-month-sales',
            $selectedDate->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
            $previousMonthDate->subMonthNoOverflow()->format('Y-m-d'),
            $companyId,
            $locationId,
            $brandId,
            $refresh
        );

        $returnData['mtdTotalSalePercentage'] = 0.00;
        if ($previousMonthSalesDetails['total_amount'] > 0) {
            $returnData['mtdTotalSalePercentage'] = CommonFunctions::numberFormat(
                ($thisMonthSales['totalAmount'] - $previousMonthSalesDetails['total_amount']) * 100 / $previousMonthSalesDetails['total_amount']
            );
        }

        $returnData['mtdUpt'] = 0.00;
        if ($thisMonthSales['totalSalesCount'] > 0) {
            $returnData['mtdUpt'] = CommonFunctions::numberFormat(
                $thisMonthSales['totalUnitsSold'] / $thisMonthSales['totalSalesCount']
            );
        }

        $returnData['mtdAtv'] = 0.00;
        if ($thisMonthSales['totalSalesCount'] > 0) {
            $returnData['mtdAtv'] = CommonFunctions::numberFormat(
                $thisMonthSales['totalAmount'] / $thisMonthSales['totalSalesCount']
            );
        }

        /** @var Carbon $previousMonthDate */
        $previousMonthDate = Carbon::createFromFormat('Y-m-d', $date);

        /** @var Carbon $selectedDate */
        $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

        $previousYearMonthSales = $this->getLastYearSaleData(
            $companyId,
            $locationId,
            $selectedDate->subYear()->startOfMonth()->format('Y-m-d'),
            $previousMonthDate->subYear()->format('Y-m-d'),
            $brandId,
            $refresh
        );

        $previousYearMonthSaleAmount = ($previousYearMonthSales['total_amount'] ?? 0);

        $returnData['previousYearMonthSalePercentage'] = 0.00;
        $returnData['previousYearMonthSaleAmount'] = $previousYearMonthSaleAmount;
        if ($previousYearMonthSaleAmount > 0) {
            $returnData['previousYearMonthSalePercentage'] = CommonFunctions::numberFormat(
                ($thisMonthSales['totalAmount'] - $previousYearMonthSaleAmount) * 100 / $previousYearMonthSaleAmount
            );
        }

        $returnData['totalAmount'] += RoundOffConfiguration::roundOffCalculationFor(
            (string) $returnData['totalAmount']
        );

        $returnData['previousYearMonthSaleAmount'] += RoundOffConfiguration::roundOffCalculationFor(
            (string) $returnData['previousYearMonthSaleAmount']
        );

        $returnData['mtdAtv'] += RoundOffConfiguration::roundOffCalculationFor((string) $returnData['mtdAtv']);

        return $returnData;
    }

    public function addTodaySalesDetails(?StoreWiseDailyTotal $storeWiseDailyTotals): array
    {
        return [
            'totalAmount' => ($storeWiseDailyTotals['total_amount'] ?? 0),
            'totalUnitsSold' => ($storeWiseDailyTotals['total_units_sold'] ?? 0),
            'totalSalesCount' => ($storeWiseDailyTotals['total_sales_count'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getThisYearSalesDetails(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh
    ): array {
        $returnData = [];
        /** @var Carbon $selectedDate */
        $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $thisYearSalesDetails = $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
            'this-year-sales-' . $date,
            $selectedDate->startOfYear()->format('Y-m-d'),
            $date,
            $companyId,
            $locationId,
            $brandId,
            $refresh
        );

        $thisYearSales = $this->addTodaySalesDetails($thisYearSalesDetails);

        $returnData['totalAmount'] = $thisYearSales['totalAmount'] ?? 0;
        $returnData['totalSalesCount'] = $thisYearSales['totalSalesCount'] ?? 0;
        $returnData['totalUnitsSold'] = $thisYearSales['totalUnitsSold'] ?? 0;

        /** @var Carbon $previousYearDate */
        $previousYearDate = Carbon::createFromFormat('Y-m-d', $date);

        $previousYearSalesDetails = $this->getLastYearSaleData(
            $companyId,
            $locationId,
            $selectedDate->subYearNoOverflow()->startOfYear()->format('Y-m-d'),
            $previousYearDate->subYearNoOverflow()->format('Y-m-d'),
            $brandId,
            $refresh
        );

        $returnData['ytdTotalSalePercentage'] = 0.00;
        if ($previousYearSalesDetails && $previousYearSalesDetails['total_amount'] > 0) {
            $returnData['ytdTotalSalePercentage'] = CommonFunctions::numberFormat(
                ($thisYearSales['totalAmount'] - $previousYearSalesDetails['total_amount']) * 100 / $previousYearSalesDetails['total_amount']
            );
        }

        $returnData['ytdUpt'] = 0.00;
        if ($thisYearSales['totalSalesCount'] > 0) {
            $returnData['ytdUpt'] = CommonFunctions::numberFormat(
                $thisYearSales['totalUnitsSold'] / $thisYearSales['totalSalesCount']
            );
        }

        $returnData['ytdAtv'] = 0.00;
        if ($thisYearSales['totalSalesCount'] > 0) {
            $returnData['ytdAtv'] = CommonFunctions::numberFormat(
                $thisYearSales['totalAmount'] / $thisYearSales['totalSalesCount']
            );
        }

        $returnData['totalAmount'] += RoundOffConfiguration::roundOffCalculationFor(
            (string) $returnData['totalAmount']
        );

        $returnData['ytdAtv'] += RoundOffConfiguration::roundOffCalculationFor((string) $returnData['ytdAtv']);

        /** @var Carbon $selectedLastYearDate */
        $selectedLastYearDate = Carbon::createFromFormat('Y-m-d', $date);

        $previousYearThisDaySales = $this->getLastYearSaleData(
            $companyId,
            $locationId,
            $selectedLastYearDate->copy()->subYear()->startOfYear()->format('Y-m-d'),
            $selectedLastYearDate->subYear()->format('Y-m-d'),
            $brandId,
            $refresh
        );

        $previousYearTodaySaleAmount = ($previousYearThisDaySales['total_amount'] ?? 0);

        $returnData['previousYearTillTodaySalePercentage'] = 0.00;
        $returnData['previousYearTillTodaySaleAmount'] = $previousYearTodaySaleAmount;
        if ($previousYearTodaySaleAmount > 0) {
            $returnData['previousYearTillTodaySalePercentage'] = CommonFunctions::numberFormat(
                ($returnData['totalAmount'] - $previousYearTodaySaleAmount) * 100 / $previousYearTodaySaleAmount
            );
        }

        $returnData['previousYearTillTodaySaleAmount'] += RoundOffConfiguration::roundOffCalculationFor(
            (string) $returnData['previousYearTillTodaySaleAmount']
        );

        return $returnData;
    }

    public function getMonthWiseSalesDetails(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh
    ): Collection {
        /** @var Carbon $selectedDate */
        $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

        return $storeWiseDailyTotalQueries->getMonthWiseTotalSalesAmountByDate(
            'month-wise-sales-' . $date,
            $selectedDate->startOfYear()->format('Y-m-d'),
            $date,
            $companyId,
            $locationId,
            $brandId,
            $refresh
        );
    }

    public function getRevenueChartData(Collection $monthWiseSalesDetails): array
    {
        $returnData = collect();
        $labels = collect();
        foreach ($monthWiseSalesDetails as $monthWiseSaleDetail) {
            $returnData->push((float) $monthWiseSaleDetail['total_amount']);
            $labels->push($monthWiseSaleDetail['month_string']);
        }

        return [
            'data' => $returnData,
            'labels' => $labels,
        ];
    }

    public function getRevenueChartDataWithLastYear(
        Collection $monthWiseSalesDetails,
        Collection $lastYearMonthWiseSalesDetails
    ): array {
        $currentMonthWiseData = collect();
        $lastYearMonthWiseData = collect();
        $labels = collect();

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        foreach ($months as $month) {
            $monthWiseSale = $monthWiseSalesDetails->firstWhere('month_string', $month);
            $lastYearMonthWiseSale = $lastYearMonthWiseSalesDetails->firstWhere('month_string', $month);

            if ($lastYearMonthWiseSale || $monthWiseSale) {
                $currentMonthWiseData->push($monthWiseSale['total_amount'] ?? 0);
                $lastYearMonthWiseData->push($lastYearMonthWiseSale['total_amount'] ?? 0);
                $labels->push($month);
            }
        }

        $sortedMonthLabels = $labels->unique()->filter()->values()->sortBy(
            fn ($item): int|false => array_search($item, $months, true)
        );

        return [
            'current_year_data' => $currentMonthWiseData,
            'last_year_data' => $lastYearMonthWiseData,
            'labels' => $sortedMonthLabels->values()->toArray(),
        ];
    }

    public function getATVChartData(Collection $monthWiseSalesDetails): array
    {
        $labels = collect();
        $returnData = collect();
        foreach ($monthWiseSalesDetails as $monthWiseSaleDetail) {
            if ($monthWiseSaleDetail['total_sales_count'] > 0) {
                $returnData->push(
                    CommonFunctions::numberFormat(
                        $monthWiseSaleDetail['total_amount'] / $monthWiseSaleDetail['total_sales_count']
                    )
                );
                $labels->push($monthWiseSaleDetail['month_string']);
            }
        }

        return [
            'data' => $returnData,
            'labels' => $labels,
        ];
    }

    public function getUPTChartData(Collection $monthWiseSalesDetails): array
    {
        $labels = collect();
        $returnData = collect();
        foreach ($monthWiseSalesDetails as $monthWiseSaleDetail) {
            if ($monthWiseSaleDetail['total_sales_count'] > 0) {
                $returnData->push(
                    CommonFunctions::numberFormat(
                        $monthWiseSaleDetail['total_units_sold'] / $monthWiseSaleDetail['total_sales_count']
                    )
                );
                $labels->push($monthWiseSaleDetail['month_string']);
            }
        }

        return [
            'data' => $returnData,
            'labels' => $labels,
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getOnlyFourSales(array $labels, array $totalSales): array
    {
        $labelsSlice = array_slice($labels, 0, 10);
        $totalSalesSlice = array_slice($totalSales, 0, 10);

        $otherLabel = 'Other';
        $otherTotalSales = array_sum(array_slice($totalSales, 10));

        if (0 !== $otherTotalSales) {
            $newLabels = [...$labelsSlice, $otherLabel];
            $newTotalSales = [...$totalSalesSlice, $otherTotalSales];
        }

        $totalSales = 0 !== $otherTotalSales ? $newTotalSales : $totalSalesSlice;
        $totalSales = array_map('floatval', $totalSales);

        return [
            'labels' => 0 !== $otherTotalSales ? $newLabels : $labelsSlice,
            'total_sales' => $totalSales,
        ];
    }

    public function getHourlyBasedData(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $saleQueries = resolve(SaleQueries::class);
        $hourlySales = $saleQueries->getHourlyBasedData($companyId, $locationId, $brandId, $date, $refresh);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $hourlySaleReturns = $saleReturnQueries->getHourlyBasedData($companyId, $locationId, $brandId, $date, $refresh);
        /** @var Carbon $previousDate */
        $previousDate = Carbon::createFromFormat('Y-m-d', $date);

        $yesterdayDayHourlySales = $saleQueries->getHourlyBasedData(
            $companyId,
            $locationId,
            $brandId,
            $previousDate->subDay()->format('Y-m-d'),
            $refresh
        );

        $previousDayHourlySaleReturns = $saleReturnQueries->getHourlyBasedData(
            $companyId,
            $locationId,
            $brandId,
            $previousDate->format('Y-m-d'),
            $refresh
        );

        $hours = $hourlySales->pluck('hour_of_day')->merge($yesterdayDayHourlySales->pluck('hour_of_day'))->sort();
        $hours = $hours->unique();

        $todayHourlySales = collect();
        $todayHourlyTotalSales = collect();
        $labels = collect();
        $todayHourlyTotal = 0;

        $yesterdayHourlySales = collect();
        $yesterdayHourlyTotalSales = collect();
        $yesterdayHourlyTotal = 0;
        foreach ($hours as $hour) {
            $todayHourlySale = 0;

            $hourlySale = $hourlySales->where('hour_of_day', $hour)->first();
            if ($hourlySale) {
                $todayHourlySale = (float) $hourlySale->today_sales;

                $labels->push([
                    'id' => $hourlySale->hour_of_day,
                    'name' => $hourlySale->hour_of_day_string,
                ]);
            }

            $hourlySaleReturn = $hourlySaleReturns->where('hour_of_day', $hour)->first();
            if ($hourlySaleReturn) {
                $todayHourlySale -= (float) $hourlySaleReturn->today_sales;
            }

            $todayHourlyTotal += $todayHourlySale;
            $todayHourlySales->push($todayHourlySale);

            if (0 !== $todayHourlySale) {
                $todayHourlyTotalSales->push($todayHourlyTotal);
            } else {
                $todayHourlyTotalSales->push($todayHourlySale);
            }

            $previousDayHourlySaleAmount = 0;

            $yesterdayDayHourlySale = $yesterdayDayHourlySales->where('hour_of_day', $hour)->first();
            if ($yesterdayDayHourlySale) {
                $previousDayHourlySaleAmount = (float) $yesterdayDayHourlySale->today_sales;

                $labels->push([
                    'id' => $yesterdayDayHourlySale->hour_of_day,
                    'name' => $yesterdayDayHourlySale->hour_of_day_string,
                ]);
            }

            $previousDayHourlySaleReturn = $previousDayHourlySaleReturns->where('hour_of_day', $hour)->first();
            if ($previousDayHourlySaleReturn) {
                $previousDayHourlySaleAmount -= (float) $previousDayHourlySaleReturn->today_sales;
            }

            $yesterdayHourlySales->push($previousDayHourlySaleAmount);
            $yesterdayHourlyTotal += $previousDayHourlySaleAmount;

            if (0 !== $previousDayHourlySaleAmount) {
                $yesterdayHourlyTotalSales->push($yesterdayHourlyTotal);
            } else {
                $yesterdayHourlyTotalSales->push($previousDayHourlySaleAmount);
            }
        }

        return [
            $todayHourlySales,
            $yesterdayHourlySales,
            $todayHourlyTotalSales,
            $yesterdayHourlyTotalSales,
            $labels->unique()->sortBy('id')->pluck('name'),
        ];
    }

    public function getLastYearSaleData(
        int $companyId,
        ?int $locationId,
        string $fromDate,
        string $toDate,
        ?int $brandId,
        bool $refresh
    ): StoreWiseDailyTotal|PastYearData|null {
        if (now()->format('Y') > 2023) {
            $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

            $key = 'previous-year-sales-for-business-dashboard-' . $locationId . '-' . $brandId;

            $returnData = $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
                $key,
                $fromDate,
                $toDate,
                $companyId,
                $locationId,
                $brandId,
                $refresh
            );
            $returnData['total_amount'] += RoundOffConfiguration::roundOffCalculationFor(
                (string) $returnData['total_amount']
            );

            return $returnData;
        }

        $pastYearDataQueries = resolve(PastYearDataQueries::class);

        return $pastYearDataQueries->getTotalSalesAmountByDate(
            $fromDate,
            $toDate,
            $companyId,
            $locationId,
            $brandId,
            $refresh
        );
    }

    public function getYearlySalesDetails(int $companyId, int $brandId, ?int $locationId, bool $refresh): array
    {
        $yearlySalesDetails = collect([]);
        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $yearlySales = $storeWiseDailyTotalQueries->yearlySalesData($companyId, $brandId, $locationId, $refresh);
        $yearlySalesDataToDates = $storeWiseDailyTotalQueries->yearlySalesDataToDate(
            $companyId,
            $brandId,
            $locationId,
            $refresh
        );

        foreach ($yearlySales as $yearlySale) {
            $yearlySalesDataToDate = $yearlySalesDataToDates->firstWhere('year', $yearlySale->year);

            $yearlyFullSales = $yearlySale->full_year_sales;
            $yearlyFullSales += RoundOffConfiguration::roundOffCalculationFor((string) $yearlyFullSales);

            $yearlyFullSalesDataToDate = $yearlySalesDataToDate->full_year_sales ?? 0;
            $yearlyFullSalesDataToDate += RoundOffConfiguration::roundOffCalculationFor(
                (string) $yearlyFullSalesDataToDate
            );

            $yearlySalesDetails->push([
                'year' => $yearlySale->year,
                'full_year_sales' => $yearlyFullSales,
                'partial_sales' => $yearlyFullSalesDataToDate,
            ]);
        }

        $totalYear = $yearlySalesDetails->count();
        if ($totalYear < 5) {
            $pastYearDataQueries = resolve(PastYearDataQueries::class);

            $pastYearlySales = $pastYearDataQueries->yearlySalesData($companyId, $brandId, $locationId);
            $pastYearlySalesDataToDates = $pastYearDataQueries->yearlySalesDataToDate(
                $companyId,
                $brandId,
                $locationId,
                $refresh
            );
            foreach ($pastYearlySales as $pastYearlySale) {
                $pastYearlySalesDataToDate = $pastYearlySalesDataToDates->firstWhere('year', $pastYearlySale->year);

                $pastYearlyFullSales = $pastYearlySale->full_year_sales;
                $pastYearlyFullSales += RoundOffConfiguration::roundOffCalculationFor(
                    (string) $pastYearlyFullSales
                );

                $pastYearlyFullSalesDataToDate = $pastYearlySalesDataToDate->full_year_sales ?? 0;
                $pastYearlyFullSalesDataToDate += RoundOffConfiguration::roundOffCalculationFor(
                    (string) $pastYearlyFullSalesDataToDate
                );

                $existingIndex = $yearlySalesDetails->search(
                    fn ($item): bool => $item['year'] === $pastYearlySale->year
                );

                if (false === $existingIndex) {
                    $yearlySalesDetails->push([
                        'year' => $pastYearlySale->year,
                        'full_year_sales' => $pastYearlyFullSales,
                        'partial_sales' => $pastYearlyFullSalesDataToDate,
                    ]);

                    continue;
                }

                $yearlySalesDetails->transform(function (array $item) use (
                    $pastYearlySale,
                    $pastYearlyFullSales,
                    $pastYearlyFullSalesDataToDate
                ): array {
                    if ($item['year'] === $pastYearlySale->year) {
                        $item['full_year_sales'] += $pastYearlyFullSales;
                        $item['partial_sales'] += $pastYearlyFullSalesDataToDate;
                    }

                    return $item;
                });
            }
        }

        return $yearlySalesDetails->sortByDesc('year')->values()->toArray();
    }

    public function getBrandWiseData(int $companyId, ?int $locationId, ?int $brandId): array
    {
        resolve(CompanyQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $monthWiseBrandsSales = $brandQueries->getMonthWiseBrandsSales($companyId, $locationId, $brandId)->groupBy(
            'id'
        );
        $monthWiseBrandsSaleReturns = $brandQueries->getMonthWiseBrandsSaleReturns($companyId, $locationId, $brandId);

        $brandWiseData = [];
        $labels = [];
        foreach ($monthWiseBrandsSales as $monthWiseBrandSale) {
            $brandData = [];
            $brandData['data'] = [];
            foreach ($monthWiseBrandSale as $brandSale) {
                $brandData['name'] = $brandSale['name'];
                $brandData['type'] = 'bar';
                $monthWiseBrandsSaleReturn = $monthWiseBrandsSaleReturns->where('id', $brandSale['id'])->firstWhere(
                    'month',
                    $brandSale['month']
                );
                $brandData['data'][$brandSale['month']] = ($brandSale['total_amount'] - ($monthWiseBrandsSaleReturn->total_amount ?? 0));
                if (! in_array($brandSale['month_string'], $labels, false)) {
                    $labels[] = $brandSale['month_string'];
                }
            }

            $brandData['data'] = collect($brandData['data'])->values()->toArray();

            $brandWiseData[] = $brandData;
        }

        $legendData = collect($brandWiseData)->pluck('name')->toArray();

        return [
            'data' => $brandWiseData,
            'legendData' => $legendData,
            'labels' => $labels,
        ];
    }

    public function getStyleWiseData(array $date, int $companyId, ?int $locationId, ?int $brandId): array
    {
        $styleQueries = resolve(StyleQueries::class);
        $monthWiseSales = $styleQueries->getMonthWiseSales($date, $companyId, $locationId, $brandId)->groupBy('id');
        $monthWiseSaleReturns = $styleQueries->getMonthWiseSaleReturns($date, $companyId, $locationId, $brandId);
        $styleWiseData = [];
        $labels = [];
        $companyData = [];
        $companyData['data'] = [];
        foreach ($monthWiseSales as $monthWiseSale) {
            $styleData = [];
            $styleData['name'] = $monthWiseSale->first()->name;
            $styleData['type'] = 'bar';
            $styleData['data'] = [];
            foreach ($monthWiseSale as $styleSale) {
                $monthWiseSaleReturn = $monthWiseSaleReturns->where('id', $styleSale->id)->firstWhere(
                    'month',
                    $styleSale->month
                );
                $styleData['data'][$styleSale->month] = ($styleSale->total_amount - ($monthWiseSaleReturn->total_amount ?? 0));
                if (! in_array($styleSale->month_string, $labels, false)) {
                    $labels[] = $styleSale->month_string;
                }

                if (! array_key_exists($styleSale->month, $companyData['data'])) {
                    $companyData['data'][$styleSale->month] = 0;
                }

                $companyData['data'][$styleSale->month] += $styleData['data'][$styleSale->month];
            }

            $styleData['data'] = collect($styleData['data'])->values()->toArray();
            $styleWiseData[] = $styleData;
        }

        $legendData = collect($styleWiseData)->pluck('name')->toArray();

        return [
            'data' => $styleWiseData,
            'legendData' => $legendData,
            'labels' => $labels,
        ];
    }

    public function getCachedBrandsSalesForChart(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $brandQueries = resolve(BrandQueries::class);
        $totalSalesByBrands = $brandQueries->getCachedBrandsSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $totalSalesByBrands = $totalSalesByBrands->map(function ($totalSalesByBrand): array {
            $averageTransactionValue = $totalSalesByBrand->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesByBrand->total_sales / $totalSalesByBrand->sales_count
            ) : 0;

            return [
                'id' => $totalSalesByBrand->id,
                'name' => $totalSalesByBrand->name,
                'sales_count' => $totalSalesByBrand->sales_count,
                'total_sales' => $totalSalesByBrand->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesByBrand->total_sales
                ),
                'total_units_sold' => (float) $totalSalesByBrand->total_units_sold,
                'upt' => $totalSalesByBrand->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesByBrand->total_units_sold / $totalSalesByBrand->sales_count
                ) : 0,
                'atv' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue
                ),
            ];
        });

        $averageTransactionValue = $totalSalesByBrands->sum('sales_count') > 0 ? CommonFunctions::numberFormat(
            $totalSalesByBrands->sum('total_sales') / $totalSalesByBrands->sum('sales_count')
        ) : 0;

        $grandTotalSalesByBrand = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesByBrands->sum('total_sales'))
            ),
            'sales_count' => $totalSalesByBrands->sum('sales_count'),
            'total_units_sold' => CommonFunctions::truncateDecimal($totalSalesByBrands->sum('total_units_sold')),
            'upt' => $totalSalesByBrands->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesByBrands->sum('total_units_sold') / $totalSalesByBrands->sum('sales_count')
            ) : 0,
            'atv' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        $totalSalesByBrandChartData = $this->getOnlyFourSales(
            $totalSalesByBrands->pluck('name')->toArray(),
            $totalSalesByBrands->pluck('total_sales')->toArray()
        );

        return [$totalSalesByBrands, $grandTotalSalesByBrand, $totalSalesByBrandChartData];
    }

    public function getCachedColorsSalesForChart(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $colorQueries = resolve(ColorQueries::class);
        $totalSalesByColors = $colorQueries->getCachedColorsSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $totalSalesByColors = $totalSalesByColors->map(function ($totalSalesByColor): array {
            $averageTransactionValue = $totalSalesByColor->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesByColor->total_sales / $totalSalesByColor->sales_count
            ) : 0;

            return [
                'id' => $totalSalesByColor->id,
                'name' => $totalSalesByColor->name,
                'sales_count' => $totalSalesByColor->sales_count,
                'total_sales' => $totalSalesByColor->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesByColor->total_sales
                ),
                'total_units_sold' => (float) $totalSalesByColor->total_units_sold,
                'upt' => $totalSalesByColor->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesByColor->total_units_sold / $totalSalesByColor->sales_count
                ) : 0,
                'atv' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue
                ),
            ];
        });

        $averageTransactionValue = $totalSalesByColors->sum('sales_count') > 0 ? CommonFunctions::numberFormat(
            $totalSalesByColors->sum('total_sales') / $totalSalesByColors->sum('sales_count')
        ) : 0;

        $grandTotalSalesByColor = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesByColors->sum('total_sales'))
            ),
            'sales_count' => $totalSalesByColors->sum('sales_count'),
            'total_units_sold' => CommonFunctions::truncateDecimal($totalSalesByColors->sum('total_units_sold')),
            'upt' => $totalSalesByColors->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesByColors->sum('total_units_sold') / $totalSalesByColors->sum('sales_count')
            ) : 0,
            'atv' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        $totalSalesByColorChartData = $this->getOnlyFourSales(
            $totalSalesByColors->pluck('name')->toArray(),
            $totalSalesByColors->pluck('total_sales')->toArray()
        );

        return [$totalSalesByColors, $grandTotalSalesByColor, $totalSalesByColorChartData];
    }

    public function getCachedCategoriesSalesForChart(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $categoryQueries = resolve(CategoryQueries::class);
        $totalSalesByCategories = $categoryQueries->getCachedCategoriesSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $totalSalesByCategories = $totalSalesByCategories->map(function ($totalSalesByCategory): array {
            $averageTransactionValue = $totalSalesByCategory->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesByCategory->total_sales / $totalSalesByCategory->sales_count
            ) : 0;

            return [
                'id' => $totalSalesByCategory->id,
                'name' => $totalSalesByCategory->name,
                'sales_count' => $totalSalesByCategory->sales_count,
                'total_sales' => $totalSalesByCategory->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesByCategory->total_sales
                ),
                'total_units_sold' => (float) $totalSalesByCategory->total_units_sold,
                'upt' => $totalSalesByCategory->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesByCategory->total_units_sold / $totalSalesByCategory->sales_count
                ) : 0,
                'atv' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue
                ),
            ];
        });

        $averageTransactionValue = $totalSalesByCategories->sum('sales_count') > 0 ? CommonFunctions::numberFormat(
            $totalSalesByCategories->sum('total_sales') / $totalSalesByCategories->sum('sales_count')
        ) : 0;

        $grandTotalSalesByCategory = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesByCategories->sum('total_sales'))
            ),
            'sales_count' => $totalSalesByCategories->sum('sales_count'),
            'total_units_sold' => CommonFunctions::truncateDecimal($totalSalesByCategories->sum('total_units_sold')),
            'upt' => $totalSalesByCategories->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesByCategories->sum('total_units_sold') / $totalSalesByCategories->sum('sales_count')
            ) : 0,
            'atv' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        $totalSalesByCategoryChartData = $this->getOnlyFourSales(
            $totalSalesByCategories->pluck('name')->toArray(),
            $totalSalesByCategories->pluck('total_sales')->toArray()
        );

        return [$totalSalesByCategories, $grandTotalSalesByCategory, $totalSalesByCategoryChartData];
    }

    public function getCachedDepartmentSaleForChart(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $departmentQueries = resolve(DepartmentQueries::class);
        $totalSalesByDepartments = $departmentQueries->getCachedDepartmentSaleForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $totalSalesByDepartments = $totalSalesByDepartments->map(function ($totalSalesByDepartment): array {
            $averageTransactionValue = $totalSalesByDepartment->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesByDepartment->total_sales / $totalSalesByDepartment->sales_count
            ) : 0;

            return [
                'id' => $totalSalesByDepartment->id,
                'name' => $totalSalesByDepartment->name,
                'sales_count' => $totalSalesByDepartment->sales_count,
                'total_sales' => $totalSalesByDepartment->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesByDepartment->total_sales
                ),
                'total_units_sold' => (float) $totalSalesByDepartment->total_units_sold,
                'upt' => $totalSalesByDepartment->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesByDepartment->total_units_sold / $totalSalesByDepartment->sales_count
                ) : 0,
                'atv' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue
                ),
            ];
        });

        $averageTransactionValue = $totalSalesByDepartments->sum(
            'sales_count'
        ) > 0 ? CommonFunctions::numberFormat(
            $totalSalesByDepartments->sum('total_sales') / $totalSalesByDepartments->sum('sales_count')
        ) : 0;

        $grandTotalSalesByDepartment = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesByDepartments->sum('total_sales'))
            ),
            'sales_count' => $totalSalesByDepartments->sum('sales_count'),
            'total_units_sold' => CommonFunctions::truncateDecimal($totalSalesByDepartments->sum('total_units_sold')),
            'upt' => $totalSalesByDepartments->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesByDepartments->sum('total_units_sold') / $totalSalesByDepartments->sum('sales_count')
            ) : 0,
            'atv' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        $totalSalesByDepartmentChartData = $this->getOnlyFourSales(
            $totalSalesByDepartments->pluck('name')->toArray(),
            $totalSalesByDepartments->pluck('total_sales')->toArray()
        );

        return [$totalSalesByDepartments, $grandTotalSalesByDepartment, $totalSalesByDepartmentChartData];
    }

    public function getCachedColorGroupSalesForChart(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $colorGroupQueries = resolve(ColorGroupQueries::class);
        $totalSalesByColorGroups = $colorGroupQueries->getCachedColorGroupSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $totalSalesByColorGroups = $totalSalesByColorGroups->map(function ($totalSalesByColorGroup): array {
            $averageTransactionValue = $totalSalesByColorGroup->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesByColorGroup->total_sales / $totalSalesByColorGroup->sales_count
            ) : 0;

            return [
                'id' => $totalSalesByColorGroup->id,
                'name' => $totalSalesByColorGroup->name,
                'sales_count' => (int) $totalSalesByColorGroup->sales_count,
                'total_sales' => (float) $totalSalesByColorGroup->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesByColorGroup->total_sales
                ),
                'total_units_sold' => (float) $totalSalesByColorGroup->total_units_sold,
                'upt' => (float) ($totalSalesByColorGroup->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesByColorGroup->total_units_sold / $totalSalesByColorGroup->sales_count
                ) : 0),
                'atv' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue),
            ];
        });

        $averageTransactionValue = $totalSalesByColorGroups->sum(
            'sales_count'
        ) > 0 ? CommonFunctions::numberFormat(
            $totalSalesByColorGroups->sum('total_sales') / $totalSalesByColorGroups->sum('sales_count')
        ) : 0;

        $grandTotalSalesByColorGroup = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesByColorGroups->sum('total_sales'))
            ),
            'sales_count' => (int) $totalSalesByColorGroups->sum('sales_count'),
            'total_units_sold' => (float) CommonFunctions::truncateDecimal(
                $totalSalesByColorGroups->sum('total_units_sold')
            ),
            'upt' => (float) ($totalSalesByColorGroups->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesByColorGroups->sum('total_units_sold') / $totalSalesByColorGroups->sum('sales_count')
            ) : 0),
            'atv' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        $totalSalesByColorGroupChartData = $this->getOnlyFourSales(
            $totalSalesByColorGroups->pluck('name')->toArray(),
            $totalSalesByColorGroups->pluck('total_sales')->toArray()
        );

        return [$totalSalesByColorGroups, $grandTotalSalesByColorGroup, $totalSalesByColorGroupChartData];
    }

    public function getCachedSizeSalesForChart(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $sizeQueries = resolve(SizeQueries::class);
        $totalSalesBySizes = $sizeQueries->getCachedSizeSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );
        $totalSalesBySizes = $totalSalesBySizes->map(function ($totalSalesBySize): array {
            $averageTransactionValue = $totalSalesBySize->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesBySize->total_sales / $totalSalesBySize->sales_count
            ) : 0;

            return [
                'id' => $totalSalesBySize->id,
                'name' => $totalSalesBySize->name,
                'sales_count' => $totalSalesBySize->sales_count,
                'total_sales' => $totalSalesBySize->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesBySize->total_sales
                ),
                'total_units_sold' => (float) $totalSalesBySize->total_units_sold,
                'upt' => $totalSalesBySize->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesBySize->total_units_sold / $totalSalesBySize->sales_count
                ) : 0,
                'atv' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue
                ),
            ];
        });

        $averageTransactionValue = $totalSalesBySizes->sum('sales_count') > 0 ? CommonFunctions::numberFormat(
            $totalSalesBySizes->sum('total_sales') / $totalSalesBySizes->sum('sales_count')
        ) : 0;

        $grandTotalSalesBySize = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesBySizes->sum('total_sales'))
            ),
            'sales_count' => $totalSalesBySizes->sum('sales_count'),
            'total_units_sold' => CommonFunctions::truncateDecimal($totalSalesBySizes->sum('total_units_sold')),
            'upt' => $totalSalesBySizes->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesBySizes->sum('total_units_sold') / $totalSalesBySizes->sum('sales_count')
            ) : 0,
            'atv' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        $totalSalesBySizeChartData = $this->getOnlyFourSales(
            $totalSalesBySizes->pluck('name')->toArray(),
            $totalSalesBySizes->pluck('total_sales')->toArray()
        );

        return [$totalSalesBySizes, $grandTotalSalesBySize, $totalSalesBySizeChartData];
    }

    public function getCachedStyleSalesForChart(
        int $companyId,
        int $locationId,
        int $brandId,
        string $date,
        bool $refresh
    ): array {
        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $styleQueries = resolve(StyleQueries::class);
        $totalSalesByStyle = $styleQueries->getCachedStylesSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );
        $totalSalesByStyle = $totalSalesByStyle->map(function ($totalSalesByStyle): array {
            $averageTransactionValue = $totalSalesByStyle->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesByStyle->total_sales / $totalSalesByStyle->sales_count
            ) : 0;

            return [
                'id' => $totalSalesByStyle->id,
                'name' => $totalSalesByStyle->name,
                'sales_count' => $totalSalesByStyle->sales_count,
                'total_sales' => $totalSalesByStyle->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesByStyle->total_sales
                ),
                'total_units_sold' => (float) $totalSalesByStyle->total_units_sold,
                'upt' => $totalSalesByStyle->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesByStyle->total_units_sold / $totalSalesByStyle->sales_count
                ) : 0,
                'atv' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue
                ),
            ];
        });

        $averageTransactionValue = $totalSalesByStyle->sum('sales_count') > 0 ? CommonFunctions::numberFormat(
            $totalSalesByStyle->sum('total_sales') / $totalSalesByStyle->sum('sales_count')
        ) : 0;

        $grandTotalSalesByStyle = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesByStyle->sum('total_sales'))
            ),
            'sales_count' => $totalSalesByStyle->sum('sales_count'),
            'total_units_sold' => CommonFunctions::truncateDecimal($totalSalesByStyle->sum('total_units_sold')),
            'upt' => $totalSalesByStyle->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesByStyle->sum('total_units_sold') / $totalSalesByStyle->sum('sales_count')
            ) : 0,
            'atv' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        $totalSalesByStyleChartData = $this->getOnlyFourSales(
            $totalSalesByStyle->pluck('name')->toArray(),
            $totalSalesByStyle->pluck('total_sales')->toArray()
        );

        return [$totalSalesByStyle, $grandTotalSalesByStyle, $totalSalesByStyleChartData];
    }

    public function getTopPromoters(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh
    ): Collection {
        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getSalesByPromotersForDashboard(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $date,
            $refresh
        );

        return $promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;
            $amountSold = $promoter['amount_sold'] ?? 0;
            $amountSold += RoundOffConfiguration::roundOffCalculationFor((string) $amountSold);

            return [
                'id' => $promoter['id'],
                'name' => $employee->getFullName() . '(' . $employee->staff_id . ')',
                'units_sold' => $promoter['units_sold'] ?? 0,
                'amount_sold' => $amountSold,
            ];
        })->values();
    }

    public function getYearlyTopPromoters(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh
    ): Collection {
        /** @var Carbon $selectedDate */
        $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getSalesByPromotersForDashboard(
            $companyId,
            $locationId,
            $brandId,
            $selectedDate->startOfYear()->format('Y-m-d'),
            $date,
            $refresh
        );

        return $promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;
            $amountSold = $promoter['amount_sold'] ?? 0;
            $amountSold += RoundOffConfiguration::roundOffCalculationFor((string) $amountSold);

            return [
                'id' => $promoter['id'],
                'name' => $employee->getFullName() . '(' . $employee->staff_id . ')',
                'units_sold' => $promoter['units_sold'] ?? 0,
                'amount_sold' => $amountSold,
            ];
        })->values();
    }

    public function getPurchaseOrderCount(array $filterData, int $companyId): array
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderCounts = $purchaseOrderQueries->getDashboardStatusCount($filterData, $companyId);

        $orders = [];
        foreach (DashboardPurchaseOrderStatuses::getList() as $status) {
            $purchaseOrderCount = $purchaseOrderCounts->firstWhere('status', $status['id']);
            $orders[$status['name']] = [
                'count' => $purchaseOrderCount ? $purchaseOrderCount->count : 0,
                'id' => $status['id'],
                'name' => $status['name'],
            ];
        }

        return $orders;
    }

    public function getPurchaseRequestCount(array $filterData, int $companyId): array
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderCounts = $purchaseOrderQueries->getDashboardStatusCount($filterData, $companyId);

        $orders = [];
        foreach (DashboardPurchaseRequestStatuses::getList() as $status) {
            $purchaseOrderCount = $purchaseOrderCounts->firstWhere('status', $status['id']);
            $orders[$status['name']] = [
                'count' => (int) $purchaseOrderCount?->count,
                'id' => $status['id'],
                'name' => $status['name'],
            ];
        }

        return $orders;
    }

    public function getPurchaseOrderFulfillmentCount(array $filterData, int $companyId): array
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentCounts = $purchaseOrderFulfillmentQueries->getDashboardStatusCount(
            $filterData,
            $companyId
        );

        $deliveryOrders = [];
        foreach (FulfillmentStatuses::getList() as $fulfillmentStatus) {
            $purchaseOrderFulfillmentCount = $purchaseOrderFulfillmentCounts->firstWhere(
                'status',
                $fulfillmentStatus['id']
            );

            $deliveryOrders[$fulfillmentStatus['name']] = [
                'count' => (int) $purchaseOrderFulfillmentCount?->count,
                'id' => $fulfillmentStatus['id'],
                'name' => $fulfillmentStatus['name'],
            ];
        }

        return $deliveryOrders;
    }

    public function getCachedSeasonalTopFiveColorsSalesForChart(
        array $filterData,
        int $companyId,
        array $colorCodes,
        bool $refreshData
    ): array {
        $colorQueries = resolve(ColorQueries::class);
        $totalSalesByColors = $colorQueries->getCachedSeasonalTopFiveColorsSalesForChart(
            $filterData,
            $companyId,
            $refreshData
        );

        $colorNames = $totalSalesByColors->pluck('name');
        $colorHexCodes = $this->preparedColors($colorNames, $colorCodes);
        $totalSaleData = $this->preparedDataWithHexCodes($totalSalesByColors->pluck('total_sales'), $colorHexCodes);

        $totalSalesByColors = $this->preparedDataWithHexCodes(
            $totalSalesByColors->pluck('total_units_sold'),
            $colorHexCodes
        );

        $topFiveColorChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveColorChartData['labels'] = $colorNames->toArray();
        $topFiveColorChartData['data'][] = [
            'data' => $totalSaleData,
            'name' => 'Sales',
            'type' => 'bar',
        ];
        $topFiveColorChartData['data'][] = [
            'data' => $totalSalesByColors,
            'name' => 'Units Sold',
            'type' => 'bar',
        ];
        $topFiveColorChartData['legendData'] = collect($topFiveColorChartData['data'])->pluck('name')->toArray();

        return $topFiveColorChartData;
    }

    public function getCachedSeasonalTopFiveColorsSalesForChartComparison(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $colorQueries = resolve(ColorQueries::class);

        $xTotalSalesByColors = $colorQueries->getCachedSeasonalTopFiveColorsSalesForChart(
            [
                'start_date' => $filterData['x_start_date'],
                'end_date' => $filterData['x_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData
        );

        $yTotalSalesByColors = $colorQueries->getCachedSeasonalTopFiveColorsSalesForChart(
            [
                'start_date' => $filterData['y_start_date'],
                'end_date' => $filterData['y_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData,
            $xTotalSalesByColors->pluck('id')->toArray()
        );

        $topFiveColorChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveColorChartData['labels'] = $xTotalSalesByColors->pluck('name')->toArray();
        $topFiveColorChartData['data'][] = [
            'data' => $xTotalSalesByColors->pluck('total_sales')->toArray(),
            'name' => $filterData['x_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveColorChartData['data'][] = [
            'data' => $yTotalSalesByColors->pluck('total_sales')->toArray(),
            'name' => $filterData['y_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveColorChartData['legendData'] = collect($topFiveColorChartData['data'])->pluck('name')->toArray();

        return $topFiveColorChartData;
    }

    public function getCachedWeekDistributionColorForChart(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $colorQueries = resolve(ColorQueries::class);
        $totalSalesByColors = $colorQueries->getCachedWeekDistributionColorForChart(
            $filterData,
            $companyId,
            $refreshData
        )->whereNotNull('week_number');

        return [
            'labels' => $totalSalesByColors->pluck('week_number')->toArray(),
            'data' => $totalSalesByColors->pluck('total_units_sold')->toArray(),
        ];
    }

    public function getCachedSeasonalTopFiveCategorySalesForChart(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $categoryQueries = resolve(CategoryQueries::class);
        $totalSalesByCategories = $categoryQueries->getCachedSeasonalTopFiveCategoriesSalesForChart(
            $filterData,
            $companyId,
            $refreshData
        );

        $topFiveCategoriesChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveCategoriesChartData['labels'] = $totalSalesByCategories->pluck('code')->toArray();
        $topFiveCategoriesChartData['data'][] = [
            'data' => $totalSalesByCategories->pluck('total_sales')->toArray(),
            'name' => 'Sales',
            'type' => 'bar',
        ];
        $topFiveCategoriesChartData['data'][] = [
            'data' => $totalSalesByCategories->pluck('total_units_sold')->toArray(),
            'name' => 'Units Sold',
            'type' => 'bar',
        ];
        $topFiveCategoriesChartData['legendData'] = collect($topFiveCategoriesChartData['data'])->pluck(
            'name'
        )->toArray();

        return $topFiveCategoriesChartData;
    }

    public function getCachedSeasonalTopFiveCategorySalesForChartComparison(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $categoryQueries = resolve(CategoryQueries::class);
        $xTotalSalesByCategories = $categoryQueries->getCachedSeasonalTopFiveCategoriesSalesForChart(
            [
                'start_date' => $filterData['x_start_date'],
                'end_date' => $filterData['x_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData
        );

        $yTotalSalesByCategories = $categoryQueries->getCachedSeasonalTopFiveCategoriesSalesForChart(
            [
                'start_date' => $filterData['y_start_date'],
                'end_date' => $filterData['y_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData,
            $xTotalSalesByCategories->pluck('id')->toArray()
        );

        $topFiveCategoriesChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveCategoriesChartData['labels'] = $xTotalSalesByCategories->pluck('code')->toArray();
        $topFiveCategoriesChartData['data'][] = [
            'data' => $xTotalSalesByCategories->pluck('total_sales')->toArray(),
            'name' => $filterData['x_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveCategoriesChartData['data'][] = [
            'data' => $yTotalSalesByCategories->pluck('total_sales')->toArray(),
            'name' => $filterData['y_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveCategoriesChartData['legendData'] = collect($topFiveCategoriesChartData['data'])->pluck(
            'name'
        )->toArray();

        return $topFiveCategoriesChartData;
    }

    public function getCachedSeasonalTopFiveStyleSalesForChart(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $styleQueries = resolve(StyleQueries::class);
        $totalSalesByStyles = $styleQueries->getCachedSeasonalTopFiveStyleSalesForChart(
            $filterData,
            $companyId,
            $refreshData
        );

        $topFiveStyleChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveStyleChartData['labels'] = $totalSalesByStyles->pluck('code')->toArray();
        $topFiveStyleChartData['data'][] = [
            'data' => $totalSalesByStyles->pluck('total_sales')->toArray(),
            'name' => 'Sales',
            'type' => 'bar',
        ];
        $topFiveStyleChartData['data'][] = [
            'data' => $totalSalesByStyles->pluck('total_units_sold')->toArray(),
            'name' => 'Units Sold',
            'type' => 'bar',
        ];
        $topFiveStyleChartData['legendData'] = collect($topFiveStyleChartData['data'])->pluck('name')->toArray();

        return $topFiveStyleChartData;
    }

    public function getCachedSeasonalTopFiveStyleSalesForChartComparison(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $styleQueries = resolve(StyleQueries::class);
        $xTotalSalesByStyles = $styleQueries->getCachedSeasonalTopFiveStyleSalesForChart(
            [
                'start_date' => $filterData['x_start_date'],
                'end_date' => $filterData['x_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData
        );

        $yTotalSalesByStyles = $styleQueries->getCachedSeasonalTopFiveStyleSalesForChart(
            [
                'start_date' => $filterData['y_start_date'],
                'end_date' => $filterData['y_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData,
            $xTotalSalesByStyles->pluck('id')->toArray()
        );

        $topFiveStyleChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveStyleChartData['labels'] = $xTotalSalesByStyles->pluck('code')->toArray();
        $topFiveStyleChartData['data'][] = [
            'data' => $xTotalSalesByStyles->pluck('total_sales')->toArray(),
            'name' => $filterData['x_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveStyleChartData['data'][] = [
            'data' => $yTotalSalesByStyles->pluck('total_sales')->toArray(),
            'name' => $filterData['y_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveStyleChartData['legendData'] = collect($topFiveStyleChartData['data'])->pluck('name')->toArray();

        return $topFiveStyleChartData;
    }

    public function getCachedSeasonalTopFiveDepartmentSalesForChart(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $departmentQueries = resolve(DepartmentQueries::class);
        $totalSalesByDepartments = $departmentQueries->getCachedSeasonalTopFiveDepartmentSalesForChart(
            $filterData,
            $companyId,
            $refreshData
        );

        $topFiveDepartmentChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveDepartmentChartData['labels'] = $totalSalesByDepartments->pluck('code')->toArray();
        $topFiveDepartmentChartData['data'][] = [
            'data' => $totalSalesByDepartments->pluck('total_sales')->toArray(),
            'name' => 'Sales',
            'type' => 'bar',
        ];
        $topFiveDepartmentChartData['data'][] = [
            'data' => $totalSalesByDepartments->pluck('total_units_sold')->toArray(),
            'name' => 'Units Sold',
            'type' => 'bar',
        ];
        $topFiveDepartmentChartData['legendData'] = collect($topFiveDepartmentChartData['data'])->pluck(
            'name'
        )->toArray();

        return $topFiveDepartmentChartData;
    }

    public function getCachedSeasonalTopFiveDepartmentSalesForChartComparison(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $departmentQueries = resolve(DepartmentQueries::class);
        $xTotalSalesByDepartments = $departmentQueries->getCachedSeasonalTopFiveDepartmentSalesForChart(
            [
                'start_date' => $filterData['x_start_date'],
                'end_date' => $filterData['x_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData
        );

        $yTotalSalesByDepartments = $departmentQueries->getCachedSeasonalTopFiveDepartmentSalesForChart(
            [
                'start_date' => $filterData['y_start_date'],
                'end_date' => $filterData['y_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData,
            $xTotalSalesByDepartments->pluck('id')->toArray()
        );

        $topFiveDepartmentChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveDepartmentChartData['labels'] = $xTotalSalesByDepartments->pluck('code')->toArray();
        $topFiveDepartmentChartData['data'][] = [
            'data' => $xTotalSalesByDepartments->pluck('total_sales')->toArray(),
            'name' => $filterData['x_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveDepartmentChartData['data'][] = [
            'data' => $yTotalSalesByDepartments->pluck('total_sales')->toArray(),
            'name' => $filterData['y_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveDepartmentChartData['legendData'] = collect($topFiveDepartmentChartData['data'])->pluck(
            'name'
        )->toArray();

        return $topFiveDepartmentChartData;
    }

    public function getCachedSeasonalTopFiveColorGroupSalesForChart(
        array $filterData,
        int $companyId,
        array $colorCodes,
        bool $refreshData
    ): array {
        $colorGroupQueries = resolve(ColorGroupQueries::class);
        $totalSalesByColorGroups = $colorGroupQueries->getCachedSeasonalTopFiveColorGroupSalesForChart(
            $filterData,
            $companyId,
            $refreshData
        );

        $topFiveColorGroupChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $colorNames = $totalSalesByColorGroups->pluck('name');
        $colorHexCodes = $this->preparedColors($colorNames, $colorCodes);
        $totalSaleData = $this->preparedDataWithHexCodes(
            $totalSalesByColorGroups->pluck('total_sales'),
            $colorHexCodes
        );

        $totalUnitsSoldData = $this->preparedDataWithHexCodes(
            $totalSalesByColorGroups->pluck('total_units_sold'),
            $colorHexCodes
        );

        $topFiveColorGroupChartData['labels'] = $colorNames->toArray();
        $topFiveColorGroupChartData['data'][] = [
            'data' => $totalSaleData,
            'name' => 'Sales',
            'type' => 'bar',
        ];
        $topFiveColorGroupChartData['data'][] = [
            'data' => $totalUnitsSoldData,
            'name' => 'Units Sold',
            'type' => 'bar',
        ];
        $topFiveColorGroupChartData['legendData'] = collect($topFiveColorGroupChartData['data'])->pluck(
            'name'
        )->toArray();

        return $topFiveColorGroupChartData;
    }

    public function getCachedSeasonalTopFiveColorGroupSalesForChartComparison(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $colorGroupQueries = resolve(ColorGroupQueries::class);
        $xTotalSalesByColorGroups = $colorGroupQueries->getCachedSeasonalTopFiveColorGroupSalesForChart(
            [
                'start_date' => $filterData['x_start_date'],
                'end_date' => $filterData['x_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData
        );

        $yTotalSalesByColorGroups = $colorGroupQueries->getCachedSeasonalTopFiveColorGroupSalesForChart(
            [
                'start_date' => $filterData['y_start_date'],
                'end_date' => $filterData['y_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData,
            $xTotalSalesByColorGroups->pluck('id')->toArray()
        );

        $topFiveColorGroupChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveColorGroupChartData['labels'] = $xTotalSalesByColorGroups->pluck('name')->toArray();
        $topFiveColorGroupChartData['data'][] = [
            'data' => $xTotalSalesByColorGroups->pluck('total_sales')->toArray(),
            'name' => $filterData['x_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveColorGroupChartData['data'][] = [
            'data' => $yTotalSalesByColorGroups->pluck('total_sales')->toArray(),
            'name' => $filterData['y_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveColorGroupChartData['legendData'] = collect($topFiveColorGroupChartData['data'])->pluck(
            'name'
        )->toArray();

        return $topFiveColorGroupChartData;
    }

    public function getCachedSeasonalTopFiveSizeSalesForChart(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $sizeQueries = resolve(SizeQueries::class);
        $totalSalesBySize = $sizeQueries->getCachedSeasonalTopFiveSizeSalesForChart(
            $filterData,
            $companyId,
            $refreshData
        );

        $topFiveSizeChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveSizeChartData['labels'] = $totalSalesBySize->pluck('name')->toArray();
        $topFiveSizeChartData['data'][] = [
            'data' => $totalSalesBySize->pluck('total_sales')->toArray(),
            'name' => 'Sales',
            'type' => 'bar',
        ];
        $topFiveSizeChartData['data'][] = [
            'data' => $totalSalesBySize->pluck('total_units_sold')->toArray(),
            'name' => 'Units Sold',
            'type' => 'bar',
        ];
        $topFiveSizeChartData['legendData'] = collect($topFiveSizeChartData['data'])->pluck('name')->toArray();

        return $topFiveSizeChartData;
    }

    public function getCachedSeasonalTopFiveSizeSalesForChartComparison(
        array $filterData,
        int $companyId,
        bool $refreshData
    ): array {
        $sizeQueries = resolve(SizeQueries::class);
        $xTotalSalesBySize = $sizeQueries->getCachedSeasonalTopFiveSizeSalesForChart(
            [
                'start_date' => $filterData['x_start_date'],
                'end_date' => $filterData['x_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData
        );

        $yTotalSalesBySize = $sizeQueries->getCachedSeasonalTopFiveSizeSalesForChart(
            [
                'start_date' => $filterData['y_start_date'],
                'end_date' => $filterData['y_end_date'],
                'location_id' => $filterData['location_id'],
                'brand_id' => $filterData['brand_id'],
            ],
            $companyId,
            $refreshData,
            $xTotalSalesBySize->pluck('id')->toArray()
        );

        $topFiveSizeChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $topFiveSizeChartData['labels'] = $xTotalSalesBySize->pluck('name')->toArray();
        $topFiveSizeChartData['data'][] = [
            'data' => $xTotalSalesBySize->pluck('total_sales')->toArray(),
            'name' => $filterData['x_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveSizeChartData['data'][] = [
            'data' => $yTotalSalesBySize->pluck('total_sales')->toArray(),
            'name' => $filterData['y_sale_season_name'],
            'type' => 'bar',
        ];
        $topFiveSizeChartData['legendData'] = collect($topFiveSizeChartData['data'])->pluck('name')->toArray();

        return $topFiveSizeChartData;
    }

    public function getCachedStockWithSizeForChart(array $filterData, int $companyId, bool $refreshData): array
    {
        $sizeQueries = resolve(SizeQueries::class);
        $totalSalesBySizeWithStock = $sizeQueries->getCachedSeasonalSizeWithStockSalesForChart(
            $filterData,
            $companyId,
            $refreshData
        );

        $sizeWithStockChartData = [
            'data' => [],
            'labels' => [],
            'legendData' => [],
        ];

        $sizeWithStockChartData['labels'] = $totalSalesBySizeWithStock->pluck('name')->toArray();
        $sizeWithStockChartData['data'][] = [
            'data' => $totalSalesBySizeWithStock->pluck('stock')->toArray(),
            'name' => 'Stock',
            'type' => 'bar',
        ];
        $sizeWithStockChartData['data'][] = [
            'data' => $totalSalesBySizeWithStock->pluck('total_units_sold')->toArray(),
            'name' => 'Units Sold',
            'type' => 'bar',
        ];
        $sizeWithStockChartData['legendData'] = collect($sizeWithStockChartData['data'])->pluck('name')->toArray();

        return $sizeWithStockChartData;
    }

    public function getTransferOrder(int $locationId, int $companyId): array
    {
        $transferOrders = [];
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $filterData = [
            'search_text' => null,
            'stock_transfer_date' => null,
            'location_id' => $locationId,
            'select_status' => null,
            'transfer_type' => null,
        ];

        $transferOrderStatusCounts = $stockTransferQueries->transferOrderStatusCount(
            StockTransferTypes::TRANSFER_ORDER->value,
            $filterData,
            $companyId
        );

        $transferInOrderStatusCounts = null;
        $transferOutOrderStatusCounts = null;

        if ($filterData['location_id'] > 0) {
            $filterData['transfer_type'] = TransferTypes::TRANSFER_IN->value;
            $transferInOrderStatusCounts = $stockTransferQueries->transferOrderStatusCount(
                StockTransferTypes::TRANSFER_ORDER->value,
                $filterData,
                $companyId,
            );

            $filterData['transfer_type'] = TransferTypes::TRANSFER_OUT->value;
            $transferOutOrderStatusCounts = $stockTransferQueries->transferOrderStatusCount(
                StockTransferTypes::TRANSFER_ORDER->value,
                $filterData,
                $companyId,
            );
        }

        foreach ($transferOrderStatusCounts as $transferOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrderStatusCount->status);
            $transferOrders[] = [
                'id' => $transferOrderStatusCount->status,
                'name' => $statusName,
                'count' => $transferOrderStatusCount->count,
                'transfer_in_count' => $transferInOrderStatusCounts instanceof Collection ? $transferInOrderStatusCounts->where(
                    'status',
                    $transferOrderStatusCount->status
                )->first()?->count : null,
                'transfer_out_count' => $transferOutOrderStatusCounts instanceof Collection ? $transferOutOrderStatusCounts->where(
                    'status',
                    $transferOrderStatusCount->status
                )->first()?->count : null,
            ];
        }

        return $transferOrders;
    }

    public function getPurchaseRequest(int $locationId, int $companyId): array
    {
        $filterData = [
            'location_id' => $locationId,
            'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        ];

        $purchaseRequests = $this->getPurchaseRequestCount($filterData, $companyId);
        $closedStatus = Statuses::getFormattedCaseName(Statuses::CLOSED->value);

        if (isset($purchaseRequests[$closedStatus])) {
            unset($purchaseRequests[$closedStatus]);
        }

        return $purchaseRequests;
    }

    public function getTransferRequest(int $locationId, int $companyId): array
    {
        $filterData = [
            'location_id' => $locationId,
            'order_type' => OrderTypes::TRANSFER_REQUEST->value,
        ];

        $transferRequests = $this->getPurchaseRequestCount($filterData, $companyId);
        $closedStatus = Statuses::getFormattedCaseName(Statuses::CLOSED->value);

        if (isset($transferRequests[$closedStatus])) {
            unset($transferRequests[$closedStatus]);
        }

        return $transferRequests;
    }

    public function getSalesOrder(int $locationId, int $companyId): array
    {
        $filterData = [
            'location_id' => $locationId,
            'order_type' => OrderTypes::SALES_ORDER->value,
        ];

        $salesOrders = $this->getPurchaseOrderCount($filterData, $companyId);

        $salesDeliveryOrders = $this->getPurchaseOrderFulfillmentCount($filterData, $companyId);

        return [$salesOrders, $salesDeliveryOrders];
    }

    public function getPurchaseOrder(int $locationId, int $companyId): array
    {
        $filterData = [
            'location_id' => $locationId,
            'order_type' => OrderTypes::PURCHASE_ORDER->value,
        ];

        $purchaseOrders = $this->getPurchaseOrderCount($filterData, $companyId);

        $purchaseDeliveryOrders = $this->getPurchaseOrderFulfillmentCount($filterData, $companyId);

        return [$purchaseOrders, $purchaseDeliveryOrders];
    }

    public function getRequestOrder(int $locationId, int $companyId): array
    {
        $requestOrders = [];
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $filterData = [
            'search_text' => null,
            'stock_transfer_date' => null,
            'location_id' => $locationId,
            'select_status' => null,
            'transfer_type' => null,
        ];

        $transferInOrderStatusCounts = null;
        $transferOutOrderStatusCounts = null;

        $requestOrderStatusCounts = $stockTransferQueries->requestOrderStatusCount(
            StockTransferTypes::REQUEST_ORDER->value,
            $filterData,
            $companyId
        );

        if (0 !== $filterData['location_id']) {
            $filterData['transfer_type'] = TransferTypes::TRANSFER_IN->value;
            $transferInOrderStatusCounts = $stockTransferQueries->transferOrderStatusCount(
                StockTransferTypes::REQUEST_ORDER->value,
                $filterData,
                $companyId
            );

            $filterData['transfer_type'] = TransferTypes::TRANSFER_OUT->value;
            $transferOutOrderStatusCounts = $stockTransferQueries->transferOrderStatusCount(
                StockTransferTypes::REQUEST_ORDER->value,
                $filterData,
                $companyId
            );
        }

        foreach ($requestOrderStatusCounts as $requestOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($requestOrderStatusCount->status);
            $requestOrders[] = [
                'id' => $requestOrderStatusCount->status,
                'name' => $statusName,
                'count' => $requestOrderStatusCount->count,
                'transfer_in_count' => $transferInOrderStatusCounts instanceof Collection ? $transferInOrderStatusCounts->where(
                    'status',
                    $requestOrderStatusCount->status
                )->first()?->count : null,
                'transfer_out_count' => $transferOutOrderStatusCounts instanceof Collection ? $transferOutOrderStatusCounts->where(
                    'status',
                    $requestOrderStatusCount->status
                )->first()?->count : null,
            ];
        }

        return $requestOrders;
    }

    public function getAllSalesDetailsByCompanyId(int $companyId, string $fromDate, string $toDate): array
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleDetails = $saleItemQueries->getSalesForDashboardByDate($companyId, $fromDate, $toDate);

        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $saleReturnDetails = $saleReturnItemQueries->getSalesReturnForDashboardByDate(
            $companyId,
            $fromDate,
            $toDate
        );

        $allDetails = collect();

        $saleDetailsCollection = collect($saleDetails)->keyBy(
            fn ($sale): string => $sale->company_id . '-' . $sale->opened_date
        );
        $saleReturnDetailsCollection = collect($saleReturnDetails)->keyBy(
            fn ($return): string => $return->company_id . '-' . $return->opened_date
        );

        $allKeys = $saleDetailsCollection->keys()->merge($saleReturnDetailsCollection->keys())->unique();

        foreach ($allKeys as $key) {
            $sale = $saleDetailsCollection->get($key);
            $return = $saleReturnDetailsCollection->get($key);

            $allDetails->push([
                'company_id' => $sale->company_id ?? $return->company_id,
                'company_uuid' => $sale->company_uuid ?? $return->company_uuid,
                'opened_date' => $sale->opened_date ?? $return->opened_date,
                'total_amount' => $sale->total_amount ?? 0,
                'total_units_sold' => $sale->total_units_sold ?? 0,
                'total_sales_count' => $sale->total_sales_count ?? 0,
                'return_amount' => $return->return_amount ?? 0,
                'return_units' => $return->return_units ?? 0,
            ]);
        }

        return $allDetails->toArray();
    }

    private function preparedColors(Collection $colorNames, array $colorCodes): array
    {
        return $colorNames->map(function ($colorName) use ($colorCodes) {
            $index = array_search(Str::lower($colorName), array_column($colorCodes, 'color'), true);
            $color = $index ? $colorCodes[$index] : null;

            return $color ? Str::upper($color['hex']) : '#c1c1c1';
        })->toArray();
    }

    private function preparedDataWithHexCodes(Collection $records, array $colorHexCodes): array
    {
        return $records->transform(fn ($saleAmount, $index): array => [
            'value' => $saleAmount,
            'itemStyle' => [
                'color' => $colorHexCodes[$index],
            ],
        ])->toArray();
    }
}
