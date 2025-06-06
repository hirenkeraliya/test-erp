<?php

namespace App\Domains\MemberGroup\Listeners;

use App\Domains\MemberGroup\Events\MemberGroupUpdateEvent;
use App\Domains\MemberGroup\Services\MemberGroupSaleChannelService;

class MemberGroupUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberGroupUpdateEvent $memberGroupUpdateEvent): void
    {
        $memberGroup = $memberGroupUpdateEvent->memberGroup;

        $memberGroupSaleChannelService = resolve(MemberGroupSaleChannelService::class);
        $memberGroupSaleChannelService->updateMemberGroup($memberGroup);
    }
}
