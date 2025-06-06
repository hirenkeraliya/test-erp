<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress\Listeners;

use App\Domains\MemberAddress\Events\MemberAddressCreateEvent;
use App\Domains\MemberAddress\Jobs\MemberAddressAddJob;

class MemberAddressCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(MemberAddressCreateEvent $memberAddressCreateEvent): void
    {
        $memberAddress = $memberAddressCreateEvent->memberAddress;

        MemberAddressAddJob::dispatch($memberAddress)
            ->delay(now()->addMinutes(10))
            ->onQueue(config('horizon.default_queue_name'));
    }
}
