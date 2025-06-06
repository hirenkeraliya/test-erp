<?php

declare(strict_types=1);

namespace App\Domains\State\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Integration;
use App\Models\State;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class StateRetailPlanningIntegrationService
{
    public function manageState(State $state, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::STATE_CREATE->value
            ? 'State Creation'
            : 'State Update';

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
                $this->sendStateDetailsToRetailPlanning($integration, $state, $integrationWebHookUrlValue);
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

    private function sendStateDetailsToRetailPlanning(
        Integration $integration,
        State $state,
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
            'id' => $state->getKey(),
            'name' => $state->name,
            'country_id' => $state->country_id,
        ], $url, $integration->secret);
    }
}
