<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Jobs;

use App\Domains\MemberGroup\MemberGroupQueries;
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

class MemberGroupSyncMainJob implements ShouldQueue
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

        $memberGroupQueries = resolve(MemberGroupQueries::class);
        foreach ($saleChannels as $saleChannel) {
            $firstMemberGroup = $memberGroupQueries->getFirstForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $firstMemberGroup) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::MEMBER_GROUP->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $lastMemberGroup = $memberGroupQueries->getLastForEcommerceSync($this->companyId, $saleChannel->id);

            if (! $lastMemberGroup) {
                $syncTransactionQueries->updateStatus(
                    $saleChannel->id,
                    SyncTypes::MEMBER_GROUP->value,
                    SyncTransactionStatuses::COMPLETED->value
                );
                continue;
            }

            $jobs = [];

            for ($startId = $firstMemberGroup->id; $startId <= $lastMemberGroup->id; $startId += 100) {
                $endId = $startId + 99;
                $jobs[] = new MemberGroupSyncJob($startId, $endId, $saleChannel->id, $this->companyId);
            }

            $jobs[] = new UpdateSyncStatusJob($saleChannel->id, SyncTypes::MEMBER_GROUP->value);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        }
    }
}
