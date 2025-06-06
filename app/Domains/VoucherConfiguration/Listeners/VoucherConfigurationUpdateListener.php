<?php

namespace App\Domains\VoucherConfiguration\Listeners;

use App\Domains\VoucherConfiguration\Events\VoucherConfigurationUpdateEvent;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationSaleChannelService;

class VoucherConfigurationUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(VoucherConfigurationUpdateEvent $voucherConfigurationUpdateEvent): void
    {
        $voucherConfiguration = $voucherConfigurationUpdateEvent->voucherConfiguration;

        $voucherConfigurationSaleChannelService = resolve(VoucherConfigurationSaleChannelService::class);
        $voucherConfigurationSaleChannelService->updateVoucherConfiguration($voucherConfiguration);
    }
}
