<?php

namespace App\Domains\Region\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Region\Events\RegionUpdateEvent;
use App\Domains\Region\Services\RegionRetailPlanningIntegrationService;

class RegionUpdateListener
{
    public function handle(RegionUpdateEvent $regionUpdateEvent): void
    {
        $region = $regionUpdateEvent->region;

        $regionRetailPlanningIntegrationService = resolve(RegionRetailPlanningIntegrationService::class);
        $regionRetailPlanningIntegrationService->manageRegion(
            $region,
            IntegrationWebhookUrls::REGION_UPDATES->value
        );
    }
}
