<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Vendor\Events\VendorUpdateEvent;
use App\Domains\Vendor\Services\VendorRetailPlanningIntegrationService;

class VendorUpdateListener
{
    public function handle(VendorUpdateEvent $vendorUpdateEvent): void
    {
        $vendor = $vendorUpdateEvent->vendor;

        /** @var VendorRetailPlanningIntegrationService $vendorRetailPlanningIntegrationService */
        $vendorRetailPlanningIntegrationService = resolve(VendorRetailPlanningIntegrationService::class);
        $vendorRetailPlanningIntegrationService->manageVendor(
            $vendor,
            IntegrationWebhookUrls::VENDOR_UPDATES->value
        );
    }
}
