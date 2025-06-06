<?php

declare(strict_types=1);

namespace App\Domains\Category\Jobs;

use App\Domains\Category\CategoryQueries;
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

class CategorySyncMainJob implements ShouldQueue
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

        $categoryQueries = resolve(CategoryQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstCategory = $categoryQueries->getFirstForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $firstCategory) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::CATEGORY->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastCategory = $categoryQueries->getLastForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $lastCategory) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::CATEGORY->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstCategory->id; $startId <= $lastCategory->id; $startId += 100) {
                $endId = $startId + 99;
                $jobs[] = new CategorySyncJob($startId, $endId, $saleChannel->id, $this->companyId);
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::CATEGORY->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
