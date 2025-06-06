<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Listeners;

use App\Domains\VoucherConfiguration\Events\VoucherConfigurationCreateEvent;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationSaleChannelService;

class VoucherConfigurationCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(VoucherConfigurationCreateEvent $voucherConfigurationCreateEvent): void
    {
        $voucherConfiguration = $voucherConfigurationCreateEvent->voucherConfiguration;

        $voucherConfigurationSaleChannelService = resolve(VoucherConfigurationSaleChannelService::class);
        $voucherConfigurationSaleChannelService->createVoucherConfiguration($voucherConfiguration);
    }
}
