<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

use App\Domains\AggregateProcessTracker\AggregateProcessTrackerQueries;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerModules;
use App\Domains\Company\CompanyQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProductAgeingTableUpdatesMainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly ?string $date = null,
    ) {
    }

    public function handle(): void
    {
        Log::channel('product_ageing_table')->info('product-ageing-table-updates', [
            'The main job for product ageing has started. Date: ' . now()->format('Y-m-d'),
        ]);

        $companyQueries = resolve(CompanyQueries::class);
        $companies = $companyQueries->getList();
        $dates = $this->getDates();
        foreach ($dates as $date) {
            foreach ($companies as $company) {
                $aggregateProcessTrackerQueries = resolve(AggregateProcessTrackerQueries::class);
                $aggregateProcessTrackerQueries->addNew(
                    $company->getKey(),
                    AggregateProcessTrackerModules::PRODUCT_AGEING->value
                );

                ProductAgeingTableUpdatesJob::dispatch($date, $company->getKey())->onQueue(
                    config('horizon.default_queue_name')
                );
            }
        }

        Log::channel('product_ageing_table')->info('product-ageing-table-updates', [
            'The main job for product ageing has ended. Date: ' . now()->format('Y-m-d'),
        ]);
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
