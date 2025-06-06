<?php

declare(strict_types=1);

namespace App\Domains\AggregateProcessTracker\Services;

use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerStatuses;
use App\Models\AggregateProcessTracker;

class AggregateProcessTrackerService
{
    public function aggregateProcessTracker(?AggregateProcessTracker $aggregateProcessTracker): array
    {
        return [
            'status' => $aggregateProcessTracker instanceof AggregateProcessTracker && AggregateProcessTrackerStatuses::IN_PROGRESS === $aggregateProcessTracker->status,
            'date' => $aggregateProcessTracker instanceof AggregateProcessTracker ? $aggregateProcessTracker->last_refreshed_at : null,
        ];
    }
}
