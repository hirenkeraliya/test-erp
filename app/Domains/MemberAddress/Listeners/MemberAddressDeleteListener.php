<?php

namespace App\Domains\MemberAddress\Listeners;

use App\Domains\MemberAddress\Events\MemberAddressDeletedEvent;
use App\Domains\MemberAddress\Services\MemberAddressSaleChannelService;

class MemberAddressDeleteListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberAddressDeletedEvent $memberAddressDeleteEvent): void
    {
        $memberAddress = $memberAddressDeleteEvent->memberAddress;

        $memberAddressSaleChannelService = resolve(MemberAddressSaleChannelService::class);
        $memberAddressSaleChannelService->deleteMemberAddress($memberAddress);
    }
}
