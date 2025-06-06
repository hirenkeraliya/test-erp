<?php

declare(strict_types=1);

use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\Sale\SaleQueries;
use App\Models\Member;
use Illuminate\Support\Facades\Queue;

test(
    'MemberUpdatePointsAndTotalSalesJob method cell getByIdForMemberUpdatePointsAndTotalSalesJob for employee queries',
    function (): void {
        Queue::fake()->except([MemberUpdatePointsAndTotalSalesJob::class]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getByIdForMemberUpdatePointsAndTotalSalesJob')
                ->once()
                ->andReturn($member);
            $mock->shouldReceive('updatePointsAndTotalSales')
                ->once();
        });

        $this->mock(MemberService::class, function ($mock): void {
            $mock->shouldReceive('getMemberPreferencesRecords')
                ->once()
                ->andReturn([]);
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getSaleTotalByMemberId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getTotalPointRewarded')
                ->once()
                ->andReturn(1);
            $mock->shouldReceive('getTotalPointsRedeemedForJob')
                ->once()
                ->andReturn(1);
        });

        MemberUpdatePointsAndTotalSalesJob::dispatch($member->id)->onQueue(config('horizon.default_queue_name'));
    }
);
