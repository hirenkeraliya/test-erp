<?php

namespace App\Domains\Brand\Listeners;

use App\Domains\Brand\Events\BrandUpdateEvent;
use App\Domains\Brand\Services\BrandRetailPlanningIntegrationService;
use App\Domains\Brand\Services\BrandSaleChannelService;
use App\Domains\Common\Enums\IntegrationWebhookUrls;

class BrandUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(BrandUpdateEvent $brandUpdateEvent): void
    {
        $brand = $brandUpdateEvent->brand;

        $brandSaleChannelService = resolve(BrandSaleChannelService::class);
        $brandSaleChannelService->updateBrand($brand);

        /** @var BrandRetailPlanningIntegrationService $brandRetailPlanningIntegrationService */
        $brandRetailPlanningIntegrationService = resolve(BrandRetailPlanningIntegrationService::class);
        $brandRetailPlanningIntegrationService->manageBrand($brand, IntegrationWebhookUrls::BRAND_UPDATES->value);
    }
}
