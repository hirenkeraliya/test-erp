<?php

declare(strict_types=1);

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesForCounterUpdateJob;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesJob;
use App\Models\CounterUpdate;
use Illuminate\Support\Facades\Queue;

test(
    'StoreWiseDailySalesJob job calls respective methods and returns the sale data',
    function (): void {
        Queue::fake()->except(StoreWiseDailySalesJob::class);
        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdate): void {
            $mock->shouldReceive('getOpenCounterIds')
                ->once()
                ->andReturn(collect([$counterUpdate]));
        });

        StoreWiseDailySalesJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Queue::assertPushed(StoreWiseDailySalesForCounterUpdateJob::class);
    }
);
