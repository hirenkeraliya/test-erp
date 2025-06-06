<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Vendor\Events\VendorCreateEvent;
use App\Domains\Vendor\Services\VendorRetailPlanningIntegrationService;

class VendorCreateListener
{
    public function handle(VendorCreateEvent $vendorCreateEvent): void
    {
        $vendor = $vendorCreateEvent->vendor;

        /** @var VendorRetailPlanningIntegrationService $vendorRetailPlanningIntegrationService */
        $vendorRetailPlanningIntegrationService = resolve(VendorRetailPlanningIntegrationService::class);
        $vendorRetailPlanningIntegrationService->manageVendor(
            $vendor,
            IntegrationWebhookUrls::VENDOR_CREATE->value
        );
    }
}
