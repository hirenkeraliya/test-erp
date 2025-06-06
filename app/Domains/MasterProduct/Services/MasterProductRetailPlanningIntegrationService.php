<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Integration;
use App\Models\MasterProduct;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class MasterProductRetailPlanningIntegrationService
{
    public function manageMasterProduct(MasterProduct $masterProduct, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::MASTER_PRODUCT_CREATE_OR_UPDATES->value
            ? 'MasterProduct Creation Or Update'
            : '';

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
                $this->sendMasterProductDetailsToRetailPlanning(
                    $integration,
                    $masterProduct,
                    $integrationWebHookUrlValue
                );
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

    private function sendMasterProductDetailsToRetailPlanning(
        Integration $integration,
        MasterProduct $masterProduct,
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
            'id' => $masterProduct->getKey(),
            'name' => $masterProduct->name,
            'company_id' => $masterProduct->company_id,
            'brand_id' => $masterProduct->brand_id,
            'vendor_id' => $masterProduct->vendor_id,
            'variant_template_id' => $masterProduct->variant_template_id,
            'original_created_at' => $masterProduct->original_created_at,
            'article_number' => $masterProduct->article_number,
        ], $url, $integration->secret);
    }
}
