<?php

declare(strict_types=1);

namespace App\Domains\MemberGroupMember\Listeners;

use App\Domains\MemberGroupMember\Events\MemberGroupMemberUpdateEvent;
use App\Domains\MemberGroupMember\Services\MemberGroupMemberSaleChannelService;

class MemberGroupMemberUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberGroupMemberUpdateEvent $memberGroupMemberUpdateEvent): void
    {
        $memberGroupMember = $memberGroupMemberUpdateEvent->memberGroupMember;

        $memberGroupMemberSaleChannelService = resolve(MemberGroupMemberSaleChannelService::class);
        $memberGroupMemberSaleChannelService->updateMemberGroup($memberGroupMember);
    }
}
