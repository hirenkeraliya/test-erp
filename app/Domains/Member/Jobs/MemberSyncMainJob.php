<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\Domains\Member\MemberQueries;
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

class MemberSyncMainJob implements ShouldQueue
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

        $memberQueries = resolve(MemberQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstMember = $memberQueries->getFirstForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $firstMember) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::MEMBER->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastMember = $memberQueries->getLastForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $lastMember) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::MEMBER->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstMember->id; $startId <= $lastMember->id; $startId += 100) {
                $endId = $startId + 99;
                $jobs[] = new MemberSyncJob($startId, $endId, $saleChannel->id, $this->companyId);
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::MEMBER->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
