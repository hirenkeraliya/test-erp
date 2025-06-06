<?php

declare(strict_types=1);

namespace App\Domains\MemberGroupMember\Listeners;

use App\Domains\MemberGroupMember\Events\MemberGroupMemberCreateEvent;
use App\Domains\MemberGroupMember\Services\MemberGroupMemberSaleChannelService;

class MemberGroupMemberDeleteListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberGroupMemberCreateEvent $memberGroupMemberCreateEvent): void
    {
        $memberGroupMember = $memberGroupMemberCreateEvent->memberGroupMember;

        $memberGroupMemberSaleChannelService = resolve(MemberGroupMemberSaleChannelService::class);
        $memberGroupMemberSaleChannelService->updateMemberGroup($memberGroupMember);
    }
}
