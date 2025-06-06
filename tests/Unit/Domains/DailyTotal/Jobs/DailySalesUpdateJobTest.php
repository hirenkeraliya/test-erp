<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\CategoryWiseDailyTotal\CategoryWiseDailyTotalQueries;
use App\Domains\CategoryWiseDailyTotal\Jobs\DailySalesUpdateJob;
use App\Models\CategoryWiseDailyTotal;

test(
    'DailySalesUpdateJob job calls respective methods and returns the sale data',
    function (): void {
        $data = (object) [
            'id' => 1,
            'location_id' => 1,
            'category_id' => 1,
            'total_units_sold' => 1,
            'total_amount' => 1,
            'company_id' => 1,
            'counter_update_id' => 1,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ];

        $categoryWiseDailyTotal = CategoryWiseDailyTotal::factory()->make([
            'company_id' => 1,
            'location_id' => 1,
            'category_id' => 1,
            'date' => now()->format('Y-m-d'),
            'total_units_sold' => 1,
            'total_amount' => 1,
        ]);

        $this->mock(CategoryQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getSaleItemsTotalSum')
                ->once()
                ->andReturn(collect([$data]));
            $mock->shouldReceive('getSaleReturnItemsTotalSum')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CategoryWiseDailyTotalQueries::class, function ($mock) use ($categoryWiseDailyTotal): void {
            $mock->shouldReceive('getByCounterUpdateIdStoreIdCompanyIdAndDate')
                ->once()
                ->andReturn($categoryWiseDailyTotal);
            $mock->shouldReceive('addNew')
                ->times(0);
            $mock->shouldReceive('update')
                ->once();
        });

        DailySalesUpdateJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);
