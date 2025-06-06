<?php

declare(strict_types=1);

namespace App\Domains\Company\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Company\Events\CompanyUpdateEvent;
use App\Domains\Company\Services\CompanyRetailPlanningIntegrationService;

class CompanyUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(CompanyUpdateEvent $companyUpdateEvent): void
    {
        $company = $companyUpdateEvent->company;

        /** @var CompanyRetailPlanningIntegrationService $companyRetailPlanningIntegrationService */
        $companyRetailPlanningIntegrationService = resolve(CompanyRetailPlanningIntegrationService::class);
        $companyRetailPlanningIntegrationService->manageCompany(
            $company,
            IntegrationWebhookUrls::COMPANY_UPDATES->value
        );
    }
}
