<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\HoldSaleDetail\HoldSaleDetailQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\ManualNotification\ManualNotificationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Member\Jobs\MemberMergeJob;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberSaleChannelService;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\MemberProductReview\MemberProductReviewQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\MembershipAssignment\MembershipAssignmentQueries;
use App\Domains\MergeMemberTransaction\MergeMemberTransactionQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderCreditNote\OrderCreditNoteQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Models\Admin;
use App\Models\Member;

test(
    'MemberMergeJobTest job calls respective methods as expected.',
    function (): void {
        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->mock(MergeMemberTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(BookingPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(CreditNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(HoldSaleDetailQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(MediaQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMemberMedia')
                ->once();
        });

        $this->mock(ManualNotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMemberIdsInManualNotificationMemberPivot')
                ->once();
        });

        $this->mock(MembershipAssignmentQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(OrderCreditNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(OrderQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(MemberProductReviewQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(MemberAddressQueries::class, function ($mock): void {
            $mock->shouldReceive('deleteOldMember')
                ->once();
        });

        $this->mock(LoyaltyPointService::class, function ($mock): void {
            $mock->shouldReceive('mergeLoyaltyPoints')
                ->once();
        });

        $this->mock(MembershipQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdBasedOnMemberSpentTillNow')
                ->once()
                ->andReturn(1);
        });

        $this->mock(MemberChannelReferenceQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getRecordsByMemberId')
                ->twice()
                ->andReturn(collect([$member]));
        });

        $this->mock(MemberSaleChannelService::class, function ($mock): void {
            $mock->shouldReceive('mergeMember')
                ->once();
        });

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('updateNewMemberDetailsAndDeleteOldMember')
                ->once();

            $mock->shouldReceive('getLatestSpentTillNow')
                ->once()
                ->andReturn(100.00);

            $mock->shouldReceive('setMembershipId')
                ->once();
        });

        MemberMergeJob::dispatch($admin, 1, 1, 1)->onQueue(config('horizon.default_queue_name'));
    }
);
