<?php

declare(strict_types=1);

namespace App\Domains\Member\Listeners;

use App\Domains\Member\Events\MemberRegisteredEvent;
use App\Domains\VoucherConfiguration\Jobs\GenerateWelcomeMemberVouchersJob;
use App\Models\Member;

class WelcomeMemberVoucherListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberRegisteredEvent $event): void
    {
        /** @var Member $member */
        $member = $event->member;

        GenerateWelcomeMemberVouchersJob::dispatch($member->id, $member->company_id)->onQueue(
            config('horizon.default_queue_name')
        );
    }
}
