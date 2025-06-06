<?php

declare(strict_types=1);

namespace App\Domains\PastYearData;

use App\Models\PastYearData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PastYearDataQueries
{
    public function getTotalSalesAmountByDate(
        string $fromDate,
        string $toDate,
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        bool $refresh = false
    ): ?PastYearData {
        $cacheFileName = 'cache-hourly-past-year-sales-' . $fromDate . '-' . $toDate . '-' . $companyId . '-' . $locationId . '-' . $brandId;

        if ($refresh) {
            Cache::forget($cacheFileName);
        }

        return Cache::remember(
            $cacheFileName,
            900,
            fn (): ?PastYearData => PastYearData::select(
                DB::raw('SUM(net_sales) as total_amount'),
                DB::raw('SUM(units_sold-units_return) as total_units_sold'),
                DB::raw('sum(total_sale) as total_sales_count')
            )
                ->where('company_id', $companyId)
                 ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                     $query->where('past_year_data.location_id', $locationId);
                 })
                ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                    $query->where('past_year_data.brand_id', $brandId);
                })
                ->where('date', '>=', $fromDate)
                ->where('date', '<=', $toDate)
                ->first()
        );
    }

    public function yearlySalesData(int $companyId, ?int $brandId, ?int $locationId): Collection
    {
        return Cache::remember(
            'past-yearly-sales-data-' . $companyId . '-' . $locationId . '-' . $brandId,
            900,
            fn (): Collection => DB::table('past_year_data')
                ->selectRaw('YEAR(date) as year')
                ->selectRaw('SUM(net_sales) as full_year_sales')
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('location_id', $locationId);
                }, function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId);
                })
                ->when(0 !== $brandId, function ($query) use ($brandId): void {
                    $query->where('brand_id', $brandId);
                })
                ->where('date', '>=', now()->subYears(5)->startOfYear()->format('Y-m-d'))
                ->where('date', '<=', now()->format('Y-m-d'))
                ->groupBy('year')
                ->orderByDesc('year')
                ->get()
        );
    }

    public function yearlySalesDataToDate(
        int $companyId,
        ?int $brandId,
        ?int $locationId,
        bool $refresh = false
    ): Collection {
        $cashKey = 'past-yearly-sales-data-to-date-' . $companyId . '-' . $locationId . '-' . $brandId;

        if ($refresh) {
            Cache::forget($cashKey);
        }

        return Cache::remember(
            $cashKey,
            900,
            fn (): Collection => DB::table('past_year_data')
                ->selectRaw('YEAR(date) as year')
                ->selectRaw('SUM(net_sales) as full_year_sales')
                ->whereRaw(
                    'YEAR(date) = YEAR(DATE(concat(year(date), "-", month(date), "-", day(current_date()))))'
                )
                ->whereRaw('date <= DATE(concat(year(date), "-' . Carbon::now()->format('m-d') . '"))')
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('location_id', $locationId);
                }, function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId);
                })
                ->when(0 !== $brandId, function ($query) use ($brandId): void {
                    $query->where('brand_id', $brandId);
                })
                ->where('date', '>=', now()->subYears(5)->startOfYear()->format('Y-m-d'))
                ->where('date', '<=', now()->format('Y-m-d'))
                ->groupBy('year')
                ->get()
        );
    }
}
