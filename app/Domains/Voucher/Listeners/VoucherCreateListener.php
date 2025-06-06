<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Listeners;

use App\Domains\Voucher\Events\VoucherCreateEvent;
use App\Domains\Voucher\Services\VoucherSaleChannelService;

class VoucherCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(VoucherCreateEvent $voucherCreateEvent): void
    {
        $voucher = $voucherCreateEvent->voucher;

        $voucherSaleChannelService = resolve(VoucherSaleChannelService::class);
        $voucherSaleChannelService->createVoucher($voucher);
    }
}
