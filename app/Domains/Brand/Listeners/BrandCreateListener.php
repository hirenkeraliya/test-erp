<?php

declare(strict_types=1);

namespace App\Domains\Brand\Listeners;

use App\Domains\Brand\Events\BrandCreateEvent;
use App\Domains\Brand\Services\BrandRetailPlanningIntegrationService;
use App\Domains\Brand\Services\BrandSaleChannelService;
use App\Domains\Common\Enums\IntegrationWebhookUrls;

class BrandCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(BrandCreateEvent $brandCreateEvent): void
    {
        $brand = $brandCreateEvent->brand;

        $brandSaleChannelService = resolve(BrandSaleChannelService::class);
        $brandSaleChannelService->createBrand($brand);

        /** @var BrandRetailPlanningIntegrationService $brandRetailPlanningIntegrationService */
        $brandRetailPlanningIntegrationService = resolve(BrandRetailPlanningIntegrationService::class);
        $brandRetailPlanningIntegrationService->manageBrand($brand, IntegrationWebhookUrls::BRAND_CREATE->value);
    }
}
