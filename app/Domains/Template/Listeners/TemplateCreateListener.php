<?php

declare(strict_types=1);

namespace App\Domains\Template\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Template\Events\TemplateCreateEvent;
use App\Domains\Template\Services\TemplateRetailPlanningIntegrationService;

class TemplateCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(TemplateCreateEvent $templateCreateEvent): void
    {
        $template = $templateCreateEvent->template;

        /** @var TemplateRetailPlanningIntegrationService $templateRetailPlanningIntegrationService */
        $templateRetailPlanningIntegrationService = resolve(TemplateRetailPlanningIntegrationService::class);
        $templateRetailPlanningIntegrationService->manageTemplate(
            $template,
            IntegrationWebhookUrls::TEMPLATE_CREATE->value
        );
    }
}
