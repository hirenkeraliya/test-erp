<?php

namespace App\Domains\Style\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Style\Events\StyleUpdateEvent;
use App\Domains\Style\Services\StyleRetailPlanningIntegrationService;

class StyleUpdateListener
{
    public function handle(StyleUpdateEvent $styleUpdateEvent): void
    {
        $style = $styleUpdateEvent->style;

        $styleRetailPlanningIntegrationService = resolve(StyleRetailPlanningIntegrationService::class);
        $styleRetailPlanningIntegrationService->manageStyle($style, IntegrationWebhookUrls::STYLE_UPDATES->value);
    }
}
