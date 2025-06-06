<?php

declare(strict_types=1);

namespace App\Domains\Season\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Season\Events\SeasonUpdateEvent;
use App\Domains\Season\Services\SeasonRetailPlanningIntegrationService;

class SeasonUpdateListener
{
    public function handle(SeasonUpdateEvent $seasonUpdateEvent): void
    {
        $season = $seasonUpdateEvent->season;

        /** @var SeasonRetailPlanningIntegrationService $seasonRetailPlanningIntegrationService */
        $seasonRetailPlanningIntegrationService = resolve(SeasonRetailPlanningIntegrationService::class);
        $seasonRetailPlanningIntegrationService->manageSeason(
            $season,
            IntegrationWebhookUrls::SEASON_UPDATES->value
        );
    }
}
