<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Jobs;

use App\Domains\AggregateProcessTracker\AggregateProcessTrackerQueries;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerModules;
use App\Domains\AggregateProcessTracker\Jobs\UpdateAggregateProcessJob;
use App\Domains\Company\CompanyQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;

class UpdateDailyAggregateMainDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly bool $fromBeginning = false,
        private readonly ?string $date = null,
    ) {
    }

    public function handle(): void
    {
        $companyQueries = resolve(CompanyQueries::class);
        $companies = $companyQueries->getList();

        foreach ($companies as $company) {
            $aggregateProcessTrackerQueries = resolve(AggregateProcessTrackerQueries::class);
            $aggregateProcessTrackerQueries->addNew(
                $company->getKey(),
                AggregateProcessTrackerModules::SELL_THROUGH->value
            );
        }

        if ($this->fromBeginning) {
            $inventoryUpdateQueries = new InventoryUpdateQueries();
            $inventoryUpdateDates = $inventoryUpdateQueries->getUniqueHappenedAt();
            foreach ($inventoryUpdateDates as $inventoryUpdateDate) {
                UpdateDailyAggregateDataJob::dispatch($inventoryUpdateDate)->onQueue('high');
            }

            foreach ($companies as $company) {
                UpdateAggregateProcessJob::dispatch(
                    $company->getKey(),
                    AggregateProcessTrackerModules::SELL_THROUGH->value
                )
                    ->onQueue(config('horizon.default_queue_name'));
            }

            return;
        }

        $dates = $this->getDates();
        foreach ($dates as $date) {
            $jobs[] = new UpdateDailyAggregateDataJob($date);
        }

        foreach ($companies as $company) {
            $jobs[] = new UpdateAggregateProcessJob(
                $company->getKey(),
                AggregateProcessTrackerModules::SELL_THROUGH->value
            );

            Bus::chain($jobs)
                ->onQueue(config('horizon.default_queue_name'))
                ->dispatch();
        }
    }

    public function getDates(): array
    {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        if ($this->date) {
            return $inventoryUpdateQueries->getAffectedDatesForSellThroughAggregate($this->date);
        }

        return $inventoryUpdateQueries->getAffectedDatesForSellThroughAggregate(now()->subDay()->format('Y-m-d'));
    }
}
