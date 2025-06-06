<?php

declare(strict_types=1);

namespace App\Domains\Color\Listeners;

use App\Domains\Color\Events\ColorCreateEvent;
use App\Domains\Color\Services\ColorSaleChannelService;

class ColorCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(ColorCreateEvent $colorCreateEvent): void
    {
        $color = $colorCreateEvent->color;

        $colorSaleChannelService = resolve(ColorSaleChannelService::class);
        $colorSaleChannelService->createColor($color);
    }
}
