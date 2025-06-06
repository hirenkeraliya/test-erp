<?php

namespace App\Domains\MemberAddress\Listeners;

use App\Domains\MemberAddress\Events\MemberAddressUpdateEvent;
use App\Domains\MemberAddress\Services\MemberAddressSaleChannelService;

class MemberAddressUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberAddressUpdateEvent $memberAddressUpdateEvent): void
    {
        $memberAddress = $memberAddressUpdateEvent->memberAddress;

        $memberAddressSaleChannelService = resolve(MemberAddressSaleChannelService::class);
        $memberAddressSaleChannelService->updateMemberAddress($memberAddress);
    }
}
