<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\CategoryWiseDailyTotal\Jobs\DailyTopTenStoreSalesJob;
use App\Domains\Company\CompanyQueries;
use App\Domains\PastYearData\PastYearDataQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Models\Company;

test(
    'DailyTopTenStoreSalesJob job calls respective methods and expired gift card as expected',
    function (): void {
        setCompanyIdInSession();

        $this->mock(StoreWiseDailyTotalQueries::class, function ($mock): void {
            $mock->shouldReceive('getTotalSalesAmountByDate')
                ->once();

            $mock->shouldReceive('yearlySalesData')
                ->once();

            $mock->shouldReceive('yearlySalesDataToDate')
                ->once();
        });

        $this->mock(BrandQueries::class, function ($mock): void {
            $mock->shouldReceive('getMonthWiseBrandsSales')
                ->once();

            $mock->shouldReceive('getMonthWiseBrandsSaleReturns')
                ->once();
        });

        $this->mock(PastYearDataQueries::class, function ($mock): void {
            $mock->shouldReceive('yearlySalesData')
                ->once();

            $mock->shouldReceive('yearlySalesDataToDate')
                ->once();
        });

        $this->mock(RegionQueries::class, function ($mock): void {
            $mock->shouldReceive('cacheRegionSales')
                ->once();
        });

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getWithIdAndName')
                ->once()
                ->andReturn(collect([$company]));
        });

        DailyTopTenStoreSalesJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);
