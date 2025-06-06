<?php

declare(strict_types=1);

namespace App\Domains\Size\Listeners;

use App\Domains\Size\Events\SizeCreateEvent;
use App\Domains\Size\Services\SizeSaleChannelService;

class SizeCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(SizeCreateEvent $sizeCreateEvent): void
    {
        $size = $sizeCreateEvent->size;

        $sizeSaleChannelService = resolve(SizeSaleChannelService::class);
        $sizeSaleChannelService->createSize($size);
    }
}
