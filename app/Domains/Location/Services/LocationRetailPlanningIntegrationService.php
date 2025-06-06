<?php

declare(strict_types=1);

namespace App\Domains\Location\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\Location\Resources\RetailPlanningLocationListResource;
use App\Models\Integration;
use App\Models\Location;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LocationRetailPlanningIntegrationService
{
    public function manageLocation(Location $location, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::LOCATION_CREATE->value
            ? 'Location Creation'
            : 'Location Update';

        $integrationQueries = resolve(IntegrationQueries::class);

        $integrations = $integrationQueries->getIntegrationsByWebhookUrl(
            $integrationWebHookUrlValue,
            IntegrationConnections::RETAIL_PLANNING->value
        );

        if ($integrations->isEmpty()) {
            return;
        }

        try {
            foreach ($integrations as $integration) {
                $this->sendLocationDetailsToRetailPlanning($integration, $location, $integrationWebHookUrlValue);
            }
        } catch (Throwable $throwable) {
            Log::channel('retail_planning')->error(sprintf('[%s] Failed - Integration error', $currentExecutionTitle), [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }

    private function sendLocationDetailsToRetailPlanning(
        Integration $integration,
        Location $location,
        int $integrationWebHookUrlValue,
    ): void {
        $integrationWebhookUrl = $integration->integrationWebhookUrls
            ->firstWhere('webhook_url_type_id', $integrationWebHookUrlValue);

        if (! $integrationWebhookUrl) {
            return;
        }

        $url = $integrationWebhookUrl->url;

        $retailPlanningIntegrationService = resolve(RetailPlanningIntegrationService::class);
        $retailPlanningIntegrationService->sendResponse(
            (new RetailPlanningLocationListResource($location))->toArray(new Request()),
            $url,
            $integration->secret
        );
    }
}
