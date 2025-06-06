<?php

declare(strict_types=1);

namespace App\Domains\Style\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Style\Events\StyleCreateEvent;
use App\Domains\Style\Services\StyleRetailPlanningIntegrationService;

class StyleCreateListener
{
    public function handle(StyleCreateEvent $styleCreateEvent): void
    {
        $style = $styleCreateEvent->style;

        $styleRetailPlanningIntegrationService = resolve(StyleRetailPlanningIntegrationService::class);
        $styleRetailPlanningIntegrationService->manageStyle($style, IntegrationWebhookUrls::STYLE_CREATE->value);
    }
}
