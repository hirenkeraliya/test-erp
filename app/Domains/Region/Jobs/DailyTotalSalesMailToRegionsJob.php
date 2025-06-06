<?php

declare(strict_types=1);

namespace App\Domains\Region\Jobs;

use App\Domains\Region\RegionQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyTotalSalesMailToRegionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $regionQueries = resolve(RegionQueries::class);

        $fromDate = now()->subDay()->startOfDay()->format('Y-m-d H:i:s');
        $toDate = now()->subDay()->endOfDay()->format('Y-m-d H:i:s');

        $regions = $regionQueries->getRegionsIdColumn();

        try {
            foreach ($regions as $region) {
                DailyTotalSalesMailToRegionJob::dispatch($region->id, $fromDate, $toDate)->onQueue(
                    config('horizon.default_queue_name')
                );
            }
        } catch (Throwable $throwable) {
            Log::error('Daily Sales Mail To Regions Main Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
