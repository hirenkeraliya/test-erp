<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Integration;
use App\Models\Vendor;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class VendorRetailPlanningIntegrationService
{
    public function manageVendor(Vendor $vendor, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::VENDOR_CREATE->value
            ? 'Vendor Creation'
            : 'Vendor Update';

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
                $this->sendVendorDetailsToRetailPlanning($integration, $vendor, $integrationWebHookUrlValue);
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

    private function sendVendorDetailsToRetailPlanning(
        Integration $integration,
        Vendor $vendor,
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
            'id' => $vendor->getKey(),
            'name' => $vendor->name,
            'company_id' => $vendor->company_id,
            'email' => $vendor->email,
            'phone' => $vendor->phone,
            'address_line_1' => $vendor->address_line_1,
            'address_line_2' => $vendor->address_line_2,
            'city' => $vendor->city,
            'area_code' => $vendor->area_code,
        ], $url, $integration->secret);
    }
}
