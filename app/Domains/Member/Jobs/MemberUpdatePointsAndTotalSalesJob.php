<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\Sale\SaleQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberUpdatePointsAndTotalSalesJob implements ShouldQueueAfterCommit, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $memberId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('update_points_and_total_sales')->info('update_points_and_total_sales', [
            'Member Update Points And Sales Job Start: ' . Carbon::now()->format('Y-m-d H:i:s'),
            'member_id: ' . $this->memberId,
        ]);

        $memberQueries = resolve(MemberQueries::class);
        $memberService = resolve(MemberService::class);

        $member = $memberQueries->getByIdForMemberUpdatePointsAndTotalSalesJob($this->memberId);

        $preferredItems = $memberService->getMemberPreferencesRecords(
            $this->memberId,
            $member->company_id,
            $member->created_location_id
        );

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $totalEarnedPoints = $loyaltyPointUpdateQueries->getTotalPointRewarded($this->memberId);

        $totalRedeemedPoints = $loyaltyPointUpdateQueries->getTotalPointsRedeemedForJob($this->memberId);

        $saleQueries = resolve(SaleQueries::class);
        $totalSales = $saleQueries->getSaleTotalByMemberId($this->memberId);

        try {
            $memberQueries->updatePointsAndTotalSales(
                $member,
                $totalEarnedPoints,
                $totalRedeemedPoints,
                $totalSales,
                $preferredItems
            );
        } catch (Throwable $throwable) {
            Log::error('member Update Points And Sales Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('update_points_and_total_sales')->info('update_points_and_total_sales', [
            'Member Update Points And Sales Job completed: ' . Carbon::now()->format('Y-m-d H:i:s'),
            'member_id: ' . $this->memberId,
        ]);
    }
}
