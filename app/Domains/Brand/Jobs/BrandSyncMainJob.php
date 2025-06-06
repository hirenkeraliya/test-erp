<?php

declare(strict_types=1);

namespace App\Domains\Brand\Jobs;

use App\Domains\Brand\BrandQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
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

class BrandSyncMainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $saleChannelId,
    ) {
    }

    public function handle(): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        if (0 === $this->saleChannelId) {
            $saleChannels = $saleChannelQueries->getAll();
        } else {
            $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);
            $saleChannels = collect([$saleChannel]);
        }

        if ($saleChannels->isEmpty()) {
            return;
        }

        $brandQueries = resolve(BrandQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstBrand = $brandQueries->getFirstForEcommerceSync($saleChannel->id);

            if (! $firstBrand) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::BRAND->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastBrand = $brandQueries->getLastForEcommerceSync($saleChannel->id);

            if (! $lastBrand) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::BRAND->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstBrand->id; $startId <= $lastBrand->id; $startId += 100) {
                $endId = $startId + 99;
                $jobs[] = new BrandSyncJob($startId, $endId, $saleChannel->id);
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::BRAND->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
