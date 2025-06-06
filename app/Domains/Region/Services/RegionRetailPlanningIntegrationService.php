<?php

declare(strict_types=1);

namespace App\Domains\Region\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Integration;
use App\Models\Region;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegionRetailPlanningIntegrationService
{
    public function manageRegion(Region $region, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::REGION_CREATE->value
            ? 'Region Creation'
            : 'Region Update';

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
                $this->sendRegionDetailsToRetailPlanning($integration, $region, $integrationWebHookUrlValue);
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

    private function sendRegionDetailsToRetailPlanning(
        Integration $integration,
        Region $region,
        int $integrationWebHookUrlValue
    ): void {
        $integrationWebhookUrl = $integration->integrationWebhookUrls
            ->firstWhere('webhook_url_type_id', $integrationWebHookUrlValue);

        if (! $integrationWebhookUrl) {
            return;
        }

        $url = $integrationWebhookUrl->url;

        $retailPlanningIntegrationService = resolve(RetailPlanningIntegrationService::class);
        $retailPlanningIntegrationService->sendResponse([
            'id' => $region->getKey(),
            'name' => $region->name,
            'company_id' => $region->company_id,
        ], $url, $integration->secret);
    }
}
