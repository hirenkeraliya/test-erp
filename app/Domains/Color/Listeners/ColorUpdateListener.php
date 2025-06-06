<?php

namespace App\Domains\Color\Listeners;

use App\Domains\Color\Events\ColorUpdateEvent;
use App\Domains\Color\Services\ColorSaleChannelService;

class ColorUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(ColorUpdateEvent $colorUpdateEvent): void
    {
        $color = $colorUpdateEvent->color;

        $colorSaleChannelService = resolve(ColorSaleChannelService::class);
        $colorSaleChannelService->updateColor($color);
    }
}
