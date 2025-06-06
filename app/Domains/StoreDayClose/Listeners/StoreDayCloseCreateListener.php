<?php

namespace App\Domains\StoreDayClose\Listeners;

use App\Domains\StoreDayClose\Events\StoreDayCloseEvent;
use App\Domains\StoreDayClose\Jobs\StoreDayCloseExportJob;

class StoreDayCloseCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(StoreDayCloseEvent $storeDayCloseEvent): void
    {
        $storeDayClose = $storeDayCloseEvent->storeDayClose;

        StoreDayCloseExportJob::dispatch($storeDayClose)
            ->delay(now()->addSeconds(1))->onQueue('high');
    }
}
