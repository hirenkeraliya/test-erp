<?php

declare(strict_types=1);

namespace App\Domains\Member\Listeners;

use App\Domains\Member\Events\MemberCreateEvent;
use App\Domains\Member\Services\MemberSaleChannelService;

class MemberCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberCreateEvent $memberCreateEvent): void
    {
        $member = $memberCreateEvent->member;

        $memberSaleChannelService = resolve(MemberSaleChannelService::class);
        $memberSaleChannelService->createMember($member);
    }
}
