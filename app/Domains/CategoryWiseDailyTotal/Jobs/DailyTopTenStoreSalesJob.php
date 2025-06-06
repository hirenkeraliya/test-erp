<?php

declare(strict_types=1);

namespace App\Domains\CategoryWiseDailyTotal\Jobs;

use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\PastYearData\PastYearDataQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyTopTenStoreSalesJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Log::channel('daily_top_ten_stores_sales')->info('daily_top_ten_stores_sales', [
            'Start time of the Daily Top Ten Stores Sales job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $regionQueries = resolve(RegionQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $companies = $companyQueries->getWithIdAndName();

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $pastYearDataQueries = resolve(PastYearDataQueries::class);

        $locationId = null;
        $brandId = null;

        try {
            foreach ($companies as $company) {
                $companyId = $company->id;

                Cache::forget('cache-region-sales-' . $companyId . '-' . (int) $brandId);
                $regionQueries->cacheRegionSales($companyId, (int) $brandId);

                Cache::forget(
                    'cache-hourly-sales-this-year-sales-for-business-dashboard-' . $companyId . '-' . $locationId
                );
                $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
                    'this-year-sales-for-business-dashboard',
                    now()->startOfYear()->format('Y-m-d'),
                    now()->format('Y-m-d'),
                    $companyId,
                    $locationId,
                    $brandId
                );

                Cache::forget('cache-month-wise-brands-sales-' . $companyId . '-' . $locationId . '-' . $brandId);
                $brandQueries->getMonthWiseBrandsSales($companyId, $locationId, $brandId);

                Cache::forget(
                    'cache-month-wise-brands-sale-returns-' . $companyId . '-' . $locationId . '-' . $brandId
                );
                $brandQueries->getMonthWiseBrandsSaleReturns($companyId, $locationId, $brandId);

                Cache::forget('past-yearly-sales-data-' . $companyId . '-' . $locationId . '-' . $brandId);
                $pastYearDataQueries->yearlySalesData($companyId, $brandId, $locationId);

                Cache::forget('past-yearly-sales-data-to-date-' . $companyId . '-' . $locationId . '-' . $brandId);
                $pastYearDataQueries->yearlySalesDataToDate($companyId, $brandId, $locationId);

                Cache::forget('location-wise-yearly-sales-data-' . $companyId . '-' . $locationId . '-' . $brandId);
                $storeWiseDailyTotalQueries->yearlySalesData($companyId, $brandId, $locationId);

                Cache::forget(
                    'location-wise-yearly-sales-data-to-date-' . $companyId . '-' . $locationId . '-' . $brandId
                );
                $storeWiseDailyTotalQueries->yearlySalesDataToDate($companyId, $brandId, $locationId);
            }
        } catch (Throwable $throwable) {
            Log::error('Daily Top Ten Store Sales Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('daily_top_ten_stores_sales')->info('daily_top_ten_stores_sales', [
            'The end time of the Daily Top Ten Stores Sales job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
