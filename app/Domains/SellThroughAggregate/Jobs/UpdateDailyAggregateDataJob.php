<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Jobs;

use App\Domains\SellThroughAggregate\Services\SellThroughAggregateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UpdateDailyAggregateDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly string $date,
    ) {
    }

    public function handle(): void
    {
        $sellThroughAggregateServices = resolve(SellThroughAggregateService::class);
        $sellThroughAggregateServices->addNewEntryForSellThrough($this->date);
    }
}
