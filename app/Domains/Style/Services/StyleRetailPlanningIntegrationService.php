<?php

declare(strict_types=1);

namespace App\Domains\Style\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Integration;
use App\Models\Style;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class StyleRetailPlanningIntegrationService
{
    public function manageStyle(Style $style, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::STYLE_CREATE->value
            ? 'Style Creation'
            : 'Style Update';

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
                $this->sendStyleDetailsToRetailPlanning($integration, $style, $integrationWebHookUrlValue);
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

    private function sendStyleDetailsToRetailPlanning(
        Integration $integration,
        Style $style,
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
            'id' => $style->getKey(),
            'name' => $style->name,
            'company_id' => $style->company_id,
        ], $url, $integration->secret);
    }
}
