<?php

declare(strict_types=1);

namespace App\Domains\SyncTransaction\Jobs;

use App\Domains\SyncTransaction\Enums\SyncTransactionStatuses;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSyncStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $saleChannelId,
        private readonly int $typeId,
    ) {
    }

    public function handle(): void
    {
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);

        $syncTransactionQueries->updateStatus(
            $this->saleChannelId,
            $this->typeId,
            SyncTransactionStatuses::COMPLETED->value
        );
    }
}
