<?php

declare(strict_types=1);

namespace App\Domains\Member\Events;

use App\Models\Member;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberRegisteredEvent implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var Member
     */
    public $member;

    /**
     * Create a new event instance.
     */
    public function __construct(Member $member)
    {
        $this->member = $member;
    }
}
