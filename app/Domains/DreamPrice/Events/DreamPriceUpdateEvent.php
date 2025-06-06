<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Events;

use App\Models\DreamPrice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DreamPriceUpdateEvent implements ShouldQueueAfterCommit, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Update a new event instance.
     */
    public function __construct(
        public DreamPrice $dreamPrice
    ) {
    }
}
