<?php

declare(strict_types=1);

namespace App\Domains\Location\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\Events\LocationCreateEvent;
use App\Domains\Location\LocationQueries;
use App\Domains\Location\Services\LocationRetailPlanningIntegrationService;

class LocationCreateListener
{
    public function handle(LocationCreateEvent $locationCreateEvent): void
    {
        $location = $locationCreateEvent->location;

        if ($location->type_id === LocationTypes::WAREHOUSE->value) {
            return;
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getByIdWithRelation($location->getKey());

        /** @var LocationRetailPlanningIntegrationService $locationRetailPlanningIntegrationService */
        $locationRetailPlanningIntegrationService = resolve(LocationRetailPlanningIntegrationService::class);
        $locationRetailPlanningIntegrationService->manageLocation(
            $location,
            IntegrationWebhookUrls::LOCATION_CREATE->value
        );
    }
}
