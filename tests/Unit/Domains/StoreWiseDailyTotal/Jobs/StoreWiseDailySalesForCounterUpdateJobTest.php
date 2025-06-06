<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesForCounterUpdateJob;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Models\StoreWiseDailyTotal;

test(
    'StoreWiseDailySalesForCounterUpdateJob job calls respective methods and returns the sale data',
    function (): void {
        $data = (object) [
            'id' => 1,
            'location_id' => 1,
            'brand_id' => 1,
            'category_id' => 1,
            'total_sales_count' => 1,
            'total_units_sold' => 1,
            'total_sales_amount' => 1,
            'company_id' => 1,
            'counter_update_id' => 1,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ];

        $storeWiseDailyTotal = StoreWiseDailyTotal::factory()->make([
            'id' => 1,
            'date' => now()->format('Y-m-d'),
            'company_id' => 1,
            'location_id' => 1,
            'brand_id' => 1,
            'counter_update_id' => 1,
            'total_amount' => 100,
            'total_sales_count' => 10,
            'total_units_sold' => 20,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getDailyStoreWiseDataForCounterUpdate')
                ->once()
                ->andReturn(collect([$data]));
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('getDailyStoreWiseDataForCounterUpdate')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotal): void {
            $mock->shouldReceive('getByCounterUpdateIdStoreIdCompanyIdAndDate')
                ->once()
                ->andReturn($storeWiseDailyTotal);
            $mock->shouldReceive('addNew')
                ->times(0);
            $mock->shouldReceive('updateSales')
                ->once();
        });

        StoreWiseDailySalesForCounterUpdateJob::dispatch(1)->onQueue(config('horizon.default_queue_name'));
    }
);
