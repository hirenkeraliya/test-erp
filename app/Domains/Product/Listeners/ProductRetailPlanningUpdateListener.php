<?php

namespace App\Domains\Product\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Events\ProductUpdateEvent;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductRetailPlanningIntegrationService;

class ProductRetailPlanningUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(ProductUpdateEvent $productUpdateEvent): void
    {
        $product = $productUpdateEvent->product;

        $product->refresh();
        if (config('app.product_variant')) {
            $productQueries = resolve(ProductQueries::class);
            $product = $productQueries->getByIdWithProductVariantValues($product->id, $product->company_id);
        }

        if ($product->type_id === ProductTypes::REGULAR_PRODUCT->value && $product->status === Statuses::ACTIVE->value) {
            $productRetailPlanningIntegrationService = resolve(ProductRetailPlanningIntegrationService::class);
            $productRetailPlanningIntegrationService->manageProduct(
                $product,
                IntegrationWebhookUrls::PRODUCT_CREATE_OR_UPDATES->value
            );
        }
    }
}
