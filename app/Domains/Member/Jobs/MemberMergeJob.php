<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\HoldSaleDetail\HoldSaleDetailQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\ManualNotification\ManualNotificationQueries;
use App\Domains\Media\MediaQueries;
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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberMergeJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected User $user,
        protected int $oldMemberId,
        protected int $newMemberId,
        protected int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mergeMemberTransactionQueries = resolve(MergeMemberTransactionQueries::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $holdSaleDetailsQueries = resolve(HoldSaleDetailQueries::class);
        $manualNotificationQueries = resolve(ManualNotificationQueries::class);
        $membershipAssignmentQueries = resolve(MembershipAssignmentQueries::class);
        $orderCreditNoteQueries = resolve(OrderCreditNoteQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $memberQueries = resolve(MemberQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $memberProductReviewQueries = resolve(MemberProductReviewQueries::class);
        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberSaleChannelService = resolve(MemberSaleChannelService::class);
        $mediaQueries = resolve(MediaQueries::class);

        DB::beginTransaction();

        try {
            $mergeMemberTransactionQueries->addNew($this->user, $this->oldMemberId, $this->newMemberId);
            $bookingPaymentQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $mediaQueries->updateMemberMedia($this->oldMemberId, $this->newMemberId);
            $creditNoteQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $holdSaleDetailsQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $manualNotificationQueries->updateMemberIdsInManualNotificationMemberPivot(
                $this->oldMemberId,
                $this->newMemberId
            );
            $membershipAssignmentQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $orderCreditNoteQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $orderQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $saleReturnQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $saleQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $voucherQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $memberProductReviewQueries->updateMember($this->oldMemberId, $this->newMemberId);
            $memberAddressQueries->deleteOldMember($this->oldMemberId);
            $loyaltyPointService->mergeLoyaltyPoints($this->oldMemberId, $this->newMemberId);
            $oldMemberChannelReferences = $memberChannelReferenceQueries->getRecordsByMemberId($this->oldMemberId);
            $newMemberChannelReferences = $memberChannelReferenceQueries->getRecordsByMemberId($this->newMemberId);

            $memberQueries->updateNewMemberDetailsAndDeleteOldMember(
                $this->companyId,
                $this->oldMemberId,
                $this->newMemberId,
                $this->user->getKey()
            );

            $memberSpentTillNow = $memberQueries->getLatestSpentTillNow($this->companyId, $this->newMemberId);
            $membershipId = $membershipQueries->getByIdBasedOnMemberSpentTillNow($this->companyId, $memberSpentTillNow);

            if ($membershipId) {
                $memberQueries->setMembershipId($membershipId, $this->newMemberId);
            }

            DB::commit();

            if ($oldMemberChannelReferences->isNotEmpty()) {
                $memberSaleChannelService->mergeMember(
                    $this->oldMemberId,
                    $this->newMemberId,
                    $this->companyId,
                    $oldMemberChannelReferences,
                    $newMemberChannelReferences
                );
            }
        } catch (Throwable $throwable) {
            DB::rollBack();

            CommonFunctions::logErrorDetails($throwable, 'Member Merge Error');

            $this->fail($throwable);
        }
    }
}
