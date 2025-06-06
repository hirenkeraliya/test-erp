<?php

declare(strict_types=1);

namespace App\Domains\Attribute\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Attribute;
use App\Models\Integration;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttributeRetailPlanningIntegrationService
{
    public function manageAttribute(Attribute $attribute, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::ATTRIBUTE_CREATE->value
            ? 'Attribute Creation'
            : 'Attribute Update';

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
                $this->sendAttributeDetailsToRetailPlanning(
                    $integration,
                    $this->getPrepareData($attribute),
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

    public function deleteAttribute(Attribute $attribute, int $integrationWebHookUrlValue): void
    {
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
                $this->sendAttributeDetailsToRetailPlanning($integration, [
                    'id' => $attribute->getKey(),
                    'company_id' => $attribute->company_id,
                ], $integrationWebHookUrlValue);
            }
        } catch (Throwable $throwable) {
            Log::channel('retail_planning')->error('Attribute Deletion Failed - Integration error', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }

    private function getPrepareData(Attribute $attribute): array
    {
        return [
            'id' => $attribute->getKey(),
            'company_id' => $attribute->company_id,
            'name' => $attribute->name,
            'description' => $attribute->description,
            'field_type' => $attribute->field_type,
            'default_value' => $attribute->default_value,
            'from' => $attribute->from,
            'to' => $attribute->to,
            'options' => $attribute->options,
            'is_required' => $attribute->is_required,
        ];
    }

    private function sendAttributeDetailsToRetailPlanning(
        Integration $integration,
        array $attributeData,
        int $integrationWebHookUrlValue
    ): void {
        $integrationWebhookUrl = $integration->integrationWebhookUrls
            ->firstWhere('webhook_url_type_id', $integrationWebHookUrlValue);

        if (! $integrationWebhookUrl) {
            return;
        }

        $url = $integrationWebhookUrl->url;

        $retailPlanningIntegrationService = resolve(RetailPlanningIntegrationService::class);
        $retailPlanningIntegrationService->sendResponse($attributeData, $url, $integration->secret);
    }
}
