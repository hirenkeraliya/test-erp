<?php

declare(strict_types=1);

namespace App\Domains\CategoryWiseDailyTotal;

use App\Models\CategoryWiseDailyTotal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryWiseDailyTotalQueries
{
    public function addNew(array $data): void
    {
        CategoryWiseDailyTotal::create($data);
    }

    public function update(CategoryWiseDailyTotal $categoryWiseDailyTotal, array $saleItemCountDetail): void
    {
        $categoryWiseDailyTotal->total_units_sold += $saleItemCountDetail['total_units_sold'];
        $categoryWiseDailyTotal->total_amount += $saleItemCountDetail['total_amount'];
        $categoryWiseDailyTotal->total_units_return += $saleItemCountDetail['total_units_return'];
        $categoryWiseDailyTotal->total_amount_return += $saleItemCountDetail['total_amount_return'];
        $categoryWiseDailyTotal->save();
    }

    public function getByCounterUpdateIdStoreIdCompanyIdAndDate(
        int $companyId,
        int $locationId,
        int $counterUpdateId,
        int $categoryId,
        string $date,
    ): ?CategoryWiseDailyTotal {
        return CategoryWiseDailyTotal::query()
            ->select(
                'id',
                'company_id',
                'location_id',
                'category_id',
                'date',
                'total_units_sold',
                'total_amount',
                'total_units_return',
                'total_amount_return',
                'counter_update_id',
            )
            ->where('date', $date)
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->where('category_id', $categoryId)
            ->where('counter_update_id', $counterUpdateId)
            ->first();
    }

    public function getTotalSalesAmount(): Collection
    {
        return CategoryWiseDailyTotal::query()
                ->select('id', 'date', 'total_units_sold', 'total_amount')
                ->get();
    }

    public function yearlySalesData(): Collection
    {
        return Cache::remember(
            'category-wise-yearly-sales-data',
            900,
            fn (): Collection => DB::table('category_wise_daily_totals as dt')
                ->selectRaw('YEAR(dt.date) as year')
                ->selectRaw('SUM(dt.total_amount) as full_year_sales')
                ->selectSub(function ($query): void {
                    $query->selectRaw('SUM(total_amount) as total_sales')
                        ->from('category_wise_daily_totals')
                        ->whereRaw(
                            'YEAR(date) = YEAR(DATE(concat(year(dt.date), "-", month(dt.date), "-", day(current_date()))))'
                        )
                        ->whereRaw('date <= DATE(concat(year(dt.date), "-' . Carbon::now()->format('m-d') . '"))');
                }, 'partial_sales')
                ->groupBy('year')
                ->get()
        );
    }
}
