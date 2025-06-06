<?php

declare(strict_types=1);

namespace App\Domains\AggregateProcessTracker;

use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerStatuses;
use App\Models\AggregateProcessTracker;
use Carbon\Carbon;

class AggregateProcessTrackerQueries
{
    public function getLastRefreshDateAndStatusForJobType(int $jobType, int $companyId): ?AggregateProcessTracker
    {
        return AggregateProcessTracker::query()
            ->select('id', 'last_refreshed_at', 'status')
            ->where('company_id', $companyId)
            ->where('job_type', $jobType)
            ->latest()
            ->first();
    }

    public function addNew(int $companyId, int $jobType): void
    {
        AggregateProcessTracker::create([
            'company_id' => $companyId,
            'status' => AggregateProcessTrackerStatuses::IN_PROGRESS,
            'job_type' => $jobType,
        ]);
    }

    public function updateTheStatus(int $companyId, int $jobType): void
    {
        $aggregateProcessTracker = $this->getLastRefreshDateAndStatusForJobType($jobType, $companyId);

        if (! $aggregateProcessTracker instanceof AggregateProcessTracker) {
            return;
        }

        $aggregateProcessTracker->status = AggregateProcessTrackerStatuses::COMPLETED;
        $aggregateProcessTracker->last_refreshed_at = Carbon::now()->format('Y-m-d H:i:s');
        $aggregateProcessTracker->save();
    }

    public function updateTheFailedStatus(int $companyId, int $jobType): void
    {
        $aggregateProcessTracker = $this->getLastRefreshDateAndStatusForJobType($jobType, $companyId);

        if (! $aggregateProcessTracker instanceof AggregateProcessTracker) {
            return;
        }

        $aggregateProcessTracker->status = AggregateProcessTrackerStatuses::FAILED;
        $aggregateProcessTracker->last_refreshed_at = Carbon::now()->format('Y-m-d H:i:s');
        $aggregateProcessTracker->save();
    }
}
