<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Location;
use App\Models\StoreWiseDailyTotal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->storeWiseDailyTotalQueries = new StoreWiseDailyTotalQueries();
});

test('getTotalSalesAmountByDate methods returns date wise sales data', function (): void {
    $date = now()->format('Y-m-d');
    $brandId = Brand::factory()->create()->id;

    StoreWiseDailyTotal::factory()->create([
        'date' => $date,
        'company_id' => $this->companyId,
        'location_id' => $this->location->id,
        'brand_id' => $brandId,
        'total_sales_count' => 10,
        'total_units_sold' => 10,
        'total_sales_amount' => 100,
    ]);

    Cache::forget(
        'cache-hourly-sales-this-month-' . $this->companyId . '-' . $date . '-' . $date . '-' . $this->location->id . '-' . $brandId
    );

    $response = $this->storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
        'this-month',
        $date,
        $date,
        $this->companyId,
        $this->location->id,
        $brandId
    );

    $response->toArray();

    expect($response->toArray())
        ->toHaveKey('total_amount', null)
        ->toHaveKey('total_units_sold', null)
        ->toHaveKey('total_sales_count', 10);

    expect(
        Cache::has(
            'cache-hourly-sales-this-month-' . $this->companyId . '-' . $date . '-' . $date . '-' . $this->location->id . '-' . $brandId
        )
    )->toBeTrue();

    $cachedResponse = $this->storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
        'this-month',
        $date,
        $date,
        $this->companyId,
        $this->location->id,
        $brandId,
    );

    expect($cachedResponse)->toEqual($response);
});

test('getTotalSalesDetailsByDateForStoreManagerApplication methods returns date wise sales data', function (): void {
    $date = now()->format('Y-m-d');

    StoreWiseDailyTotal::factory()->create([
        'date' => $date,
        'company_id' => $this->companyId,
        'location_id' => $this->location->id,
        'total_sales_count' => 10,
        'total_units_sold' => 10,
        'total_sales_amount' => 100,
    ]);

    $response = $this->storeWiseDailyTotalQueries->getTotalSalesDetailsByDateForStoreManagerApplication(
        $this->location->id,
        [$date, $date],
        $this->companyId,
    );

    expect($response->toArray())
        ->toHaveKey('total_sales_amount', 100)
        ->toHaveKey('total_sales_return_amount', null)
        ->toHaveKey('total_sales', 10)
        ->toHaveKey('total_sales_return', null);
});

test('getMonthWiseTotalSalesAmountByDate methods returns month wise sales data', function (): void {
    $date = now()->format('Y-m-d');
    $brandId = Brand::factory()->create()->id;

    StoreWiseDailyTotal::factory()->create([
        'date' => $date,
        'company_id' => $this->companyId,
        'location_id' => $this->location->id,
        'brand_id' => $brandId,
        'total_sales_count' => 10,
        'total_units_sold' => 10,
        'total_sales_amount' => 100,
        'total_amount_return' => 10,
        'total_units_return' => 5,
    ]);

    Cache::forget('cache-hourly-sales-month-wise-' . $this->location->id . '-' . $brandId);

    $response = $this->storeWiseDailyTotalQueries->getMonthWiseTotalSalesAmountByDate(
        'month-wise',
        $date,
        $date,
        $this->companyId,
        $this->location->id,
        $brandId,
    );

    expect($response->first()->toArray())
        ->toHaveKey('month', Carbon::now()->month)
        ->toHaveKey('total_amount', '90.00')
        ->toHaveKey('total_units_sold', '5.00')
        ->toHaveKey('total_sales_count', '10');

    expect(Cache::has('cache-hourly-sales-month-wise-' . $this->location->id . '-' . $brandId))->toBeTrue();
});

test('can add a new store wise daily total record', function (): void {
    $storeWise = StoreWiseDailyTotal::factory()->make([
        'date' => now(),
    ]);

    $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

    $storeWiseDailyTotalQueries->addNew($storeWise->toArray());

    $this->assertDatabaseHas('store_wise_daily_totals', [
        'total_units_sold' => $storeWise->total_units_sold,
        'total_sales_amount' => $storeWise->total_sales_amount,
    ]);
});

test('updateReturns method update return data in store wise daily data', function (): void {
    $storeWise = StoreWiseDailyTotal::factory()->create([
        'date' => now(),
        'total_units_return' => 0,
        'total_amount_return' => 0,
    ]);

    $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

    $storeWiseDailyTotalQueries->updateReturns(
        $storeWise,
        [
            'total_units_return' => 100,
            'total_amount_return' => 150,
        ]
    );

    $this->assertDatabaseHas('store_wise_daily_totals', [
        'total_units_return' => 100,
        'total_amount_return' => 150,
    ]);
});

test('updateSales method update return data in store wise daily data', function (): void {
    $storeWise = StoreWiseDailyTotal::factory()->create([
        'date' => now(),
        'total_sales_count' => 0,
        'total_units_sold' => 0,
        'total_sales_amount' => 0,
    ]);

    $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

    $storeWiseDailyTotalQueries->updateSales(
        $storeWise,
        [
            'total_sales_count' => 100,
            'total_units_sold' => 150,
            'total_sales_amount' => 200,
        ]
    );

    $this->assertDatabaseHas('store_wise_daily_totals', [
        'total_sales_count' => 100,
        'total_units_sold' => 150,
        'total_sales_amount' => 200,
    ]);
});

test('getSaleSeasonalData method returns the collection for the dashboard data', function (): void {
    $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

    $date = now()->format('Y-m-d');

    $filterData = [
        'start_date' => $date,
        'end_date' => $date,
        'location_id' => 0,
        'brand_id' => 0,
    ];

    $storeWise = StoreWiseDailyTotal::factory()->create([
        'date' => $date,
        'company_id' => $this->companyId,
    ]);

    $response = $storeWiseDailyTotalQueries->getSaleSeasonalData($filterData, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('total_sales_count', $storeWise->total_sales_count)
        ->toHaveKey('total_sales_amount', $storeWise->total_sales_amount)
        ->toHaveKey('total_amount_return', $storeWise->total_amount_return);
});

test(
    'getAnalyticsForLastTenDaysOfSeasonalData method returns the collection for the dashboard data',
    function (): void {
        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

        $date = now()->format('Y-m-d');

        $filterData = [
            'start_date' => $date,
            'end_date' => $date,
            'location_id' => 0,
            'brand_id' => 0,
        ];

        $storeWise = StoreWiseDailyTotal::factory()->create([
            'date' => $date,
            'company_id' => $this->companyId,
        ]);

        $response = $storeWiseDailyTotalQueries->getAnalyticsForLastTenDaysOfSeasonalData(
            $filterData,
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('total_sales_count', $storeWise->total_sales_count)
            ->toHaveKey('total_sales_amount', $storeWise->total_sales_amount)
            ->toHaveKey('total_amount_return', $storeWise->total_amount_return);
    }
);
