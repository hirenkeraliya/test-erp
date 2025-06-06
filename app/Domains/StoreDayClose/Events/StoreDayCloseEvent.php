<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Events;

use App\Models\StoreDayClose;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreDayCloseEvent implements ShouldQueueAfterCommit, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StoreDayClose $storeDayClose,
    ) {
    }
}
