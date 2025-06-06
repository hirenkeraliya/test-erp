<?php

declare(strict_types=1);

namespace App\Domains\Region\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Region\Events\RegionCreateEvent;
use App\Domains\Region\Services\RegionRetailPlanningIntegrationService;

class RegionCreateListener
{
    public function handle(RegionCreateEvent $regionCreateEvent): void
    {
        $region = $regionCreateEvent->region;

        $regionRetailPlanningIntegrationService = resolve(RegionRetailPlanningIntegrationService::class);
        $regionRetailPlanningIntegrationService->manageRegion(
            $region,
            IntegrationWebhookUrls::REGION_CREATE->value
        );
    }
}
