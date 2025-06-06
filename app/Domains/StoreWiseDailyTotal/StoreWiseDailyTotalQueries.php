<?php

declare(strict_types=1);

namespace App\Domains\StoreWiseDailyTotal;

use App\Domains\Brand\BrandQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Region\RegionQueries;
use App\Models\StoreWiseDailyTotal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StoreWiseDailyTotalQueries
{
    public function addNew(array $data): void
    {
        StoreWiseDailyTotal::create($data);
    }

    public function update(StoreWiseDailyTotal $storeWiseDailyTotal, array $storeWiseSale): void
    {
        $storeWiseDailyTotal->total_sales_count += $storeWiseSale['total_sales_count'];
        $storeWiseDailyTotal->total_units_sold += $storeWiseSale['total_units_sold'];
        $storeWiseDailyTotal->total_sales_amount += $storeWiseSale['total_sales_amount'];
        $storeWiseDailyTotal->total_units_return += $storeWiseSale['total_units_return'];
        $storeWiseDailyTotal->total_amount_return += $storeWiseSale['total_amount_return'];
        $storeWiseDailyTotal->save();
    }

    public function updateReturns(StoreWiseDailyTotal $storeWiseDailyTotal, array $storeWiseSale): void
    {
        $storeWiseDailyTotal->total_units_return = $storeWiseSale['total_units_return'];
        $storeWiseDailyTotal->total_amount_return = $storeWiseSale['total_amount_return'];
        $storeWiseDailyTotal->save();
    }

    public function updateSales(StoreWiseDailyTotal $storeWiseDailyTotal, array $storeWiseSale): void
    {
        $storeWiseDailyTotal->total_sales_count = $storeWiseSale['total_sales_count'];
        $storeWiseDailyTotal->total_units_sold = $storeWiseSale['total_units_sold'];
        $storeWiseDailyTotal->total_sales_amount = $storeWiseSale['total_sales_amount'];
        $storeWiseDailyTotal->save();
    }

    public function getByCounterUpdateIdStoreIdCompanyIdAndDate(
        int $companyId,
        int $locationId,
        int $brandId,
        int $counterUpdateId,
        string $date,
    ): ?StoreWiseDailyTotal {
        return StoreWiseDailyTotal::query()
            ->select(
                'id',
                'date',
                'company_id',
                'location_id',
                'brand_id',
                'total_sales_count',
                'total_units_sold',
                'total_sales_amount',
                'total_units_return',
                'total_amount_return',
                'counter_update_id',
            )
            ->where('date', $date)
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->where('brand_id', $brandId)
            ->where('counter_update_id', $counterUpdateId)
            ->first();
    }

    public function getTotalSalesAmountByDate(
        string $cacheKey,
        string $fromDate,
        string $toDate,
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        bool $refresh = false
    ): StoreWiseDailyTotal {
        $cacheFileName = 'cache-hourly-sales-' . $cacheKey . '-' . $companyId . '-' . $fromDate . '-' . $toDate . '-' . $locationId . '-' . $brandId;

        if ($refresh) {
            Cache::forget($cacheFileName);
        }

        return Cache::remember(
            $cacheFileName,
            900,
            fn (): StoreWiseDailyTotal => StoreWiseDailyTotal::select(
                DB::raw('SUM(total_sales_amount - total_amount_return) as total_amount'),
                DB::raw('SUM(total_units_sold - total_units_return) as total_units_sold'),
                DB::raw('sum(total_sales_count) as total_sales_count')
            )
                ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                    $query->where('location_id', $locationId);
                })
                ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                    $query->where('brand_id', $brandId);
                })
                ->where('company_id', $companyId)
                ->where('date', '>=', $fromDate)
                ->where('date', '<=', $toDate)
                ->firstOrFail()
        );
    }

    public function getTotalSalesDetailsByDateForStoreManagerApplication(
        int $locationId,
        array $date,
        int $companyId
    ): StoreWiseDailyTotal {
        return StoreWiseDailyTotal::select(
            DB::raw('SUM(total_sales_amount) as total_sales_amount'),
            DB::raw('SUM(total_amount_return) as total_sales_return_amount'),
            DB::raw('sum(total_units_sold) as total_sales'),
            DB::raw('sum(total_units_return) as total_sales_return'),
        )
                ->where('location_id', $locationId)
                ->where('company_id', $companyId)
                ->where('date', '>=', $date[0])
                ->where('date', '<=', $date[1])
                ->firstOrFail();
    }

    public function getMonthWiseTotalSalesAmountByDate(
        string $cacheKey,
        string $fromDate,
        string $toDate,
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        bool $refresh = false
    ): Collection {
        $cacheFileName = 'cache-hourly-sales-' . $cacheKey . '-' . $locationId . '-' . $brandId;

        if ($refresh) {
            Cache::forget($cacheFileName);
        }

        $cacheValue = Cache::get($cacheFileName);

        return Cache::remember(
            $cacheFileName,
            Cache::has($cacheFileName) && null !== $cacheValue ? 600 : 150,
            fn (): Collection => StoreWiseDailyTotal::select(
                DB::raw('DATE_FORMAT(date,"%m") as month'),
                DB::raw('DATE_FORMAT(date,"%M") as month_string'),
                DB::raw('SUM(total_sales_amount - total_amount_return) as total_amount'),
                DB::raw('SUM(total_units_sold - total_units_return) as total_units_sold'),
                DB::raw('sum(total_sales_count) as total_sales_count')
            )
                ->where('company_id', $companyId)
                ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                    $query->where('store_wise_daily_totals.location_id', $locationId);
                })
                ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                    $query->where('store_wise_daily_totals.brand_id', $brandId);
                })
                ->where('date', '>=', $fromDate)
                ->where('date', '<=', $toDate)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
        );
    }

    public function yearlySalesData(int $companyId, ?int $brandId, ?int $locationId, bool $refresh = false): Collection
    {
        $cacheKey = 'location-wise-yearly-sales-data-' . $companyId . '-' . $locationId . '-' . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => DB::table('store_wise_daily_totals as st')
                ->selectRaw('YEAR(st.date) as year')
                ->selectRaw('SUM(st.total_sales_amount - st.total_amount_return) as full_year_sales')
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('location_id', $locationId);
                }, function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId);
                })
                ->when(0 !== $brandId, function ($query) use ($brandId): void {
                    $query->where('brand_id', $brandId);
                })
                ->where('date', '>=', now()->subYears(5)->format('Y-m-d'))
                ->where('date', '<=', now()->format('Y-m-d'))
                ->groupBy('year')
                ->get()
        );
    }

    public function yearlySalesDataToDate(
        int $companyId,
        ?int $brandId,
        ?int $locationId,
        bool $refresh = false
    ): Collection {
        $cacheKey = 'location-wise-yearly-sales-data-to-date-' . $companyId . '-' . $locationId . '-' . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => DB::table('store_wise_daily_totals as st')
                ->selectRaw('YEAR(st.date) as year')
                ->selectRaw('SUM(st.total_sales_amount - st.total_amount_return) as full_year_sales')
                ->whereRaw(
                    'YEAR(date) = YEAR(DATE(concat(year(st.date), "-", month(st.date), "-", day(current_date()))))'
                )
                ->whereRaw('date <= DATE(concat(year(st.date), "-' . Carbon::now()->format('m-d') . '"))')
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('location_id', $locationId);
                }, function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId);
                })
                ->when(0 !== $brandId, function ($query) use ($brandId): void {
                    $query->where('brand_id', $brandId);
                })
                ->where('date', '>=', now()->subYears(5)->format('Y-m-d'))
                ->where('date', '<=', now()->format('Y-m-d'))
                ->groupBy('year')
                ->get()
        );
    }

    public function getSaleSeasonalData(array $filterData, int $companyId): Collection
    {
        $brandQueries = new BrandQueries();
        $locationQueries = new LocationQueries();
        $regionQueries = new RegionQueries();

        return StoreWiseDailyTotal::query()
            ->select(
                'id',
                'date',
                'location_id',
                'brand_id',
                'total_sales_count',
                'total_units_sold',
                'total_sales_amount',
                'total_units_return',
                'total_amount_return',
            )
            ->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'location:' . $locationQueries->getNameColumnNameForSaleSeasons(),
                'location.region:' . $regionQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                $query->where('location_id', $filterData['location_id']);
            })
            ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                $query->where('brand_id', $filterData['brand_id']);
            })
            ->where('date', '>=', $filterData['start_date'])
            ->where('date', '<=', $filterData['end_date'])
            ->get();
    }

    public function getAnalyticsForLastTenDaysOfSeasonalData(array $filterData, int $companyId): Collection
    {
        return StoreWiseDailyTotal::query()
            ->select(
                'id',
                'location_id',
                'brand_id',
                DB::raw('SUM(total_sales_count) as total_sales_count'),
                DB::raw('SUM(total_units_sold) as total_units_sold'),
                DB::raw('SUM(total_sales_amount) as total_sales_amount'),
                DB::raw('SUM(total_units_return) as total_units_return'),
                DB::raw('SUM(total_amount_return) as total_amount_return'),
                'date',
            )
            ->where('company_id', $companyId)
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                $query->where('location_id', $filterData['location_id']);
            })
            ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                $query->where('brand_id', $filterData['brand_id']);
            })
            ->where('date', '>=', $filterData['start_date'])
            ->where('date', '<=', $filterData['end_date'])
            ->groupBy('date')
            ->orderByDesc('date')
            ->get();
    }
}
