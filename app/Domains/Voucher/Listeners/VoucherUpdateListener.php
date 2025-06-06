<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Listeners;

use App\Domains\Voucher\Events\VoucherUpdateEvent;
use App\Domains\Voucher\Services\VoucherSaleChannelService;

class VoucherUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(VoucherUpdateEvent $voucherUpdateEvent): void
    {
        $voucher = $voucherUpdateEvent->voucher;

        $voucherSaleChannelService = resolve(VoucherSaleChannelService::class);
        $voucherSaleChannelService->createVoucher($voucher);
    }
}
