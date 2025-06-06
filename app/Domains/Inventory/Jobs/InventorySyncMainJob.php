<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Jobs;

use App\Domains\Inventory\InventoryQueries;
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

class InventorySyncMainJob implements ShouldQueue
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

        $inventoryQueries = resolve(InventoryQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstInventory = $inventoryQueries->getFirstForEcommerceSync(
                $this->companyId,
                $saleChannel->id,
                $saleChannel->default_location_id
            );

            if (! $firstInventory) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::INVENTORY->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastInventory = $inventoryQueries->getLastForEcommerceSync(
                $this->companyId,
                $saleChannel->id,
                $saleChannel->default_location_id
            );

            if (! $lastInventory) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::INVENTORY->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstInventory->id; $startId <= $lastInventory->id; $startId += 100) {
                $endId = $startId + 99;

                $jobs[] = new InventorySyncJob(
                    $startId,
                    $endId,
                    $saleChannel->id,
                    $saleChannel->default_location_id,
                    $this->companyId
                );
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::INVENTORY->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
