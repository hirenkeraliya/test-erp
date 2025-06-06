<?php

namespace App\Domains\Size\Listeners;

use App\Domains\Size\Events\SizeUpdateEvent;
use App\Domains\Size\Services\SizeSaleChannelService;

class SizeUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(SizeUpdateEvent $sizeUpdateEvent): void
    {
        $size = $sizeUpdateEvent->size;

        $sizeSaleChannelService = resolve(SizeSaleChannelService::class);
        $sizeSaleChannelService->updateSize($size);
    }
}
