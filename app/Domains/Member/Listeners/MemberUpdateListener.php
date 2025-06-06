<?php

namespace App\Domains\Member\Listeners;

use App\Domains\Member\Enums\Status;
use App\Domains\Member\Events\MemberUpdateEvent;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberSaleChannelService;

class MemberUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberUpdateEvent $memberUpdateEvent): void
    {
        $member = $memberUpdateEvent->member;

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getByOnlyId($member->id);

        $memberSaleChannelService = resolve(MemberSaleChannelService::class);

        if ($member->status === Status::DELETED_BY_USER->value || $member->status === Status::DELETED_BY_ADMIN->value) {
            $memberSaleChannelService->deleteMember($member->id, $member->company_id);
        }

        if ($member->status === Status::ACTIVE->value || $member->status === Status::INACTIVE->value) {
            $memberSaleChannelService->updateMember($member);
        }
    }
}
