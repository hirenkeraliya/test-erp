<?php

namespace App\Domains\MasterProduct\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\MasterProduct\Events\MasterProductUpdateEvent;
use App\Domains\MasterProduct\Services\MasterProductRetailPlanningIntegrationService;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;

class MasterProductRetailPlanningUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(MasterProductUpdateEvent $event): void
    {
        $masterProduct = $event->masterProduct;

        $masterProduct->refresh();

        if ($masterProduct->type_id === ProductTypes::REGULAR_PRODUCT->value && $masterProduct->status === Statuses::ACTIVE->value) {
            $masterProductRetailPlanningIntegrationService = resolve(
                MasterProductRetailPlanningIntegrationService::class
            );
            $masterProductRetailPlanningIntegrationService->manageMasterProduct(
                $masterProduct,
                IntegrationWebhookUrls::MASTER_PRODUCT_CREATE_OR_UPDATES->value
            );
        }
    }
}
