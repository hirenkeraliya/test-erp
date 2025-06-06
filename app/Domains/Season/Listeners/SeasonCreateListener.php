<?php

declare(strict_types=1);

namespace App\Domains\Season\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Season\Events\SeasonCreateEvent;
use App\Domains\Season\Services\SeasonRetailPlanningIntegrationService;

class SeasonCreateListener
{
    public function handle(SeasonCreateEvent $seasonCreateEvent): void
    {
        $season = $seasonCreateEvent->season;

        /** @var SeasonRetailPlanningIntegrationService $seasonRetailPlanningIntegrationService */
        $seasonRetailPlanningIntegrationService = resolve(SeasonRetailPlanningIntegrationService::class);
        $seasonRetailPlanningIntegrationService->manageSeason(
            $season,
            IntegrationWebhookUrls::SEASON_CREATE->value
        );
    }
}
