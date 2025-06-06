<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Jobs;

use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
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

class DreamPriceSyncMainJob implements ShouldQueue
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
        $saleChannels = $saleChannelQueries->getAllByCompanyId($this->companyId);
        if (0 === $this->saleChannelId) {
            $saleChannels = $saleChannelQueries->getAllByCompanyId($this->companyId);
        } else {
            $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);
            $saleChannels = collect([$saleChannel]);
        }

        if ($saleChannels->isEmpty()) {
            return;
        }

        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstDreamPriceProduct = $dreamPriceProductQueries->getFirstForEcommerceSync(
                $this->companyId,
                $saleChannel->id
            );

            if (! $firstDreamPriceProduct) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::DREAM_PRICE->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastDreamPriceProduct = $dreamPriceProductQueries->getLastForEcommerceSync(
                $this->companyId,
                $saleChannel->id
            );

            if (! $lastDreamPriceProduct) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::DREAM_PRICE->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstDreamPriceProduct->id; $startId <= $lastDreamPriceProduct->id; $startId += 100) {
                $endId = $startId + 99;
                $jobs[] = new DreamPriceSyncJob($startId, $endId, $saleChannel->id, $this->companyId);
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::DREAM_PRICE->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
