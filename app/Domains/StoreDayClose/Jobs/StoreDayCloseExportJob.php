<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Jobs;

use App\Domains\StoreDayClose\Services\StoreDayCloseExportService;
use App\Models\StoreDayClose;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreDayCloseExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected StoreDayClose $storeDayClose,
    ) {
    }

    public function handle(): void
    {
        Log::channel('store_day_close_export')->info('store day close add job', [
            'start time for store day close add' => Carbon::now()->format('Y-m-d H:i:s'),
            'store-day-close-id: ' . $this->storeDayClose->getKey(),
        ]);

        try {
            $storDayCloseExportService = resolve(StoreDayCloseExportService::class);
            $storDayCloseExportService->storeDayCloseExport($this->storeDayClose);
        } catch (Throwable $throwable) {
            Log::channel('store_day_close_export')->error('store day close export store day close job failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('store_day_close_export')->info('store day close add job', [
            'end time of for store day close add' => Carbon::now()->format('Y-m-d H:i:s'),
            'store-day-close-id: ' . $this->storeDayClose->getKey(),
        ]);
    }
}
