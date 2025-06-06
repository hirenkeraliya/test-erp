<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Integration;
use App\Models\Product;
use App\Services\RetailPlanningIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductRetailPlanningIntegrationService
{
    public function manageProduct(Product $product, int $integrationWebHookUrlValue): void
    {
        $currentExecutionTitle = $integrationWebHookUrlValue === IntegrationWebhookUrls::PRODUCT_CREATE_OR_UPDATES->value
            ? 'Product Creation or Update ' : '';

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
                $this->sendProductDetailsToRetailPlanning($integration, $product, $integrationWebHookUrlValue);
            }
        } catch (Throwable $throwable) {
            Log::channel('retail_planning')->error(
                sprintf('[%s] Failed - Integration error Product Creation or Update ', $currentExecutionTitle),
                [
                    'Error message' => $throwable->getMessage(),
                    'Error code' => $throwable->getCode(),
                    'File' => $throwable->getFile(),
                    'Line' => $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]
            );
        }
    }

    private function sendProductDetailsToRetailPlanning(
        Integration $integration,
        Product $product,
        int $integrationWebHookUrlValue
    ): void {
        $integrationWebhookUrl = $integration->integrationWebhookUrls
            ->firstWhere('webhook_url_type_id', $integrationWebHookUrlValue);

        if (! $integrationWebhookUrl) {
            return;
        }

        $url = $integrationWebhookUrl->url;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($product->company_id);
        $retailPlanningIntegrationService = resolve(RetailPlanningIntegrationService::class);
        $retailPlanningIntegrationService->sendResponse([
            'id' => $product->getKey(),
            'name' => $product->name,
            'retail_price' => $product->retail_price,
            'master_product_id' => $product->master_product_id,
            'company_id' => $product->company_id,
            'brand_id' => $product->brand_id,
            'vendor_id' => $product->vendor_id,
            'original_created_at' => $product->original_created_at,
            'article_number' => $product->article_number,
            'color_name' => config('app.product_variant') ? null : $product->color?->name,
            'size_name' => config('app.product_variant') ? null : $product->size?->name,
            'currency_code' => $currency->code,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues : [],
            'is_product_variant_enabled' => config('app.product_variant'),
        ], $url, $integration->secret);
    }
}
