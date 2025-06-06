<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberSaleChannelService;
use App\Domains\MemberAddress\Services\MemberAddressSaleChannelService;
use App\Domains\SaleChannel\SaleChannelQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $startId,
        private readonly int $endId,
        private readonly int $saleChannelId,
        private readonly int $companyId,
    ) {
    }

    public function handle(): void
    {
        $memberQueries = resolve(MemberQueries::class);
        $members = $memberQueries->getMemberEcommerceChannelByStartAndEndId(
            $this->companyId,
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);

        $memberSaleChannelService = resolve(MemberSaleChannelService::class);
        $memberAddressSaleChannelService = resolve(MemberAddressSaleChannelService::class);

        try {
            foreach ($members as $member) {
                $memberSaleChannelService->addMember($saleChannel, $member);
                $memberAddresses = $member->memberAddresses;
                foreach ($memberAddresses as $memberAddress) {
                    $memberAddressSaleChannelService->addMemberAddress($saleChannel, $memberAddress);
                }
            }
        } catch (Throwable $throwable) {
            Log::error('Member sync error', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
