<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NewMemberBenefitsJob implements ShouldQueueAfterCommit
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
        private readonly int $locationId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('new_member_benefits')->info('new_member_benefits', [
            'New Member Benefits Job Start: ' . Carbon::now()->format('Y-m-d H:i:s'),
            'member_id: ' . $this->memberId,
        ]);

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getByIdForNewMemberBenefitsJob($this->memberId);

        if ($member->created_location_id) {
            return;
        }

        /** @var Company $company */
        $company = $member->company;
        if ($company->location_assignment_type !== LocationAssignmentTypes::BASED_ON_FIRST_PURCHASE->value) {
            return;
        }

        try {
            $memberQueries->storeUpdate($member, $this->locationId);

            $memberService = resolve(MemberService::class);
            $memberService->addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers($member);
        } catch (Throwable $throwable) {
            Log::error('New Member Benefits Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('new_member_benefits')->info('new_member_benefits', [
            'New Member Benefits Job completed: ' . Carbon::now()->format('Y-m-d H:i:s'),
            'member_id: ' . $this->memberId,
        ]);
    }
}
