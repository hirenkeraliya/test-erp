<?php

declare(strict_types=1);

namespace App\Domains\Color\Jobs;

use App\Domains\Color\ColorQueries;
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

class ColorSyncMainJob implements ShouldQueue
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

        $colorQueries = resolve(ColorQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstColor = $colorQueries->getFirstForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $firstColor) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::COLOR->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastColor = $colorQueries->getLastForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $lastColor) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::COLOR->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstColor->id; $startId <= $lastColor->id; $startId += 100) {
                $endId = $startId + 99;
                $jobs[] = new ColorSyncJob($startId, $endId, $saleChannel->id, $this->companyId);
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::COLOR->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
