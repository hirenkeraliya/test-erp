<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate\Events;

use App\Models\LoyaltyPointUpdate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoyaltyPointUpdatesCreateEvent implements ShouldQueueAfterCommit, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public LoyaltyPointUpdate $loyaltyPointUpdate
    ) {
    }
}
