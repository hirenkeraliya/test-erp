<?php

declare(strict_types=1);

namespace App\Domains\Company\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Company\Events\CompanyCreateEvent;
use App\Domains\Company\Services\CompanyRetailPlanningIntegrationService;

class CompanyCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(CompanyCreateEvent $companyCreateEvent): void
    {
        $company = $companyCreateEvent->company;

        /** @var CompanyRetailPlanningIntegrationService $companyRetailPlanningIntegrationService */
        $companyRetailPlanningIntegrationService = resolve(CompanyRetailPlanningIntegrationService::class);
        $companyRetailPlanningIntegrationService->manageCompany(
            $company,
            IntegrationWebhookUrls::COMPANY_CREATE->value
        );
    }
}
