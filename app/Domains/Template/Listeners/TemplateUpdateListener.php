<?php

declare(strict_types=1);

namespace App\Domains\Template\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Template\Events\TemplateUpdateEvent;
use App\Domains\Template\Services\TemplateRetailPlanningIntegrationService;

class TemplateUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(TemplateUpdateEvent $templateUpdateEvent): void
    {
        $template = $templateUpdateEvent->template;

        /** @var TemplateRetailPlanningIntegrationService $templateRetailPlanningIntegrationService */
        $templateRetailPlanningIntegrationService = resolve(TemplateRetailPlanningIntegrationService::class);
        $templateRetailPlanningIntegrationService->manageTemplate(
            $template,
            IntegrationWebhookUrls::TEMPLATE_UPDATES->value
        );
    }
}
