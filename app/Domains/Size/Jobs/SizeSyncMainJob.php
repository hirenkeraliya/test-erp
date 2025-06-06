<?php

declare(strict_types=1);

namespace App\Domains\Size\Jobs;

use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\SyncTransaction\Enums\SyncTransactionStatuses;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\Jobs\UpdateSyncStatusJob;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class SizeSyncMainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $saleChannelId,
        private readonly int $companyId,
    ) {
    }

    public function handle(): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        if (0 === $this->saleChannelId) {
            $saleChannels = $saleChannelQueries->getAllByCompanyId($this->companyId);
        } else {
            $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);
            $saleChannels = collect([$saleChannel]);
        }

        if ($saleChannels->isEmpty()) {
            return;
        }

        $sizeQueries = resolve(SizeQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstSize = $sizeQueries->getFirstForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $firstSize) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::SIZE->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastSize = $sizeQueries->getLastForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $lastSize) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::SIZE->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstSize->id; $startId <= $lastSize->id; $startId += 100) {
                $endId = $startId + 99;
                SizeSyncJob::dispatch($startId, $endId, $saleChannel->id, $this->companyId)->onQueue('high');

                $jobs[] = new SizeSyncJob($startId, $endId, $saleChannel->id, $this->companyId);
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::SIZE->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
