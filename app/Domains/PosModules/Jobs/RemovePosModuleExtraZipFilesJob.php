<?php

declare(strict_types=1);

namespace App\Domains\PosModules\Jobs;

use App\Domains\Product\Jobs\PosProductsExtraZipFilesRemoveJob;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemovePosModuleExtraZipFilesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! config('services.pos_modules.allow_pos_modules_zip')) {
            return;
        }

        Log::channel('pos_modules')->info('pos_modules', [
            'Remove POS module Extra ZIP Files job start time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        PosProductsExtraZipFilesRemoveJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Log::channel('pos_modules')->info('pos_modules', [
            'Remove POS module Extra ZIP Files job end time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
