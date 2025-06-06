<?php

declare(strict_types=1);

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesForClosedCounterJob;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesForCounterUpdateJob;
use App\Models\CounterUpdate;
use Illuminate\Support\Facades\Queue;

test(
    'StoreWiseDailySalesForClosedCounterJob job calls respective methods and returns the sale data',
    function (): void {
        Queue::fake()->except(StoreWiseDailySalesForClosedCounterJob::class);
        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdate): void {
            $mock->shouldReceive('getClosedCounterIds')
                ->once()
                ->andReturn(collect([$counterUpdate]));
        });

        StoreWiseDailySalesForClosedCounterJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Queue::assertPushed(StoreWiseDailySalesForCounterUpdateJob::class);
    }
);
