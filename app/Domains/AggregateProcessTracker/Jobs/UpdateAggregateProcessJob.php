<?php

declare(strict_types=1);

namespace App\Domains\AggregateProcessTracker\Jobs;

use App\Domains\AggregateProcessTracker\AggregateProcessTrackerQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UpdateAggregateProcessJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $companyId,
        private readonly int $jobType,
    ) {
    }

    public function handle(): void
    {
        $aggregateProcessTrackerQueries = resolve(AggregateProcessTrackerQueries::class);
        $aggregateProcessTrackerQueries->updateTheStatus($this->companyId, $this->jobType);
    }
}
