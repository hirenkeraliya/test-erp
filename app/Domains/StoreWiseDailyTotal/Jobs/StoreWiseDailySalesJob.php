<?php

declare(strict_types=1);

namespace App\Domains\StoreWiseDailyTotal\Jobs;

use App\Domains\CounterUpdate\CounterUpdateQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreWiseDailySalesJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Log::channel('daily_store_wise_sales')->info('daily_store_wise_sales', [
            'The start time for the Daily Store Wise Sales Update job: ' . now()->format(
                'Y-m-d H:i:s'
            ) . ' Date : ' . now()->format('Y-m-d H:i:s'),
        ]);

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdates = $counterUpdateQueries->getOpenCounterIds();

        try {
            foreach ($counterUpdates as $counterUpdate) {
                StoreWiseDailySalesForCounterUpdateJob::dispatch($counterUpdate->id)->onQueue('medium');
            }
        } catch (Throwable $throwable) {
            Log::error('Store Wise Daily Sales Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('daily_store_wise_sales')->info('daily_store_wise_sales', [
            'The job end time for the Daily Store Wise Sales Update is: ' . now()->format(
                'Y-m-d H:i:s'
            ) . ' Date : ' . now()->format('Y-m-d H:i:s'),
        ]);
    }
}
