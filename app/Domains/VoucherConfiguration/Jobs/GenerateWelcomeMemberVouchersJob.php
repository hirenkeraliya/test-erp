<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Jobs;

use App\Domains\Member\MemberQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateWelcomeMemberVouchersJob implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $memberId,
        private readonly int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('voucher_generation')->info('welcome_member_voucher_generation', [
            'Please generate a welcome member voucher prior to job start time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getById($this->memberId, $this->companyId);

        try {
            if (! $member->created_location_id) {
                Log::channel('voucher_generation')->info(
                    'welcome_member_voucher_generation',
                    ['created_location_id not set for Member ID : ' . $member->id]
                );

                return;
            }

            $today = Carbon::now();

            $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
            $voucherConfiguration = $voucherConfigurationQueries->getWelcomeMemberVoucherConfigurationByCompanyId(
                $member->company_id,
                $today
            );

            if (! $voucherConfiguration) {
                Log::channel('voucher_generation')->info(
                    'welcome_member_voucher_generation',
                    [
                        'Please generate a welcome member voucher for the end of the job: ' . Carbon::now()->format(
                            'Y-m-d H:i:s'
                        ) . ' Voucher configuration not found in our database for Member ID : ' . $member->id,
                    ]
                );

                return;
            }

            if ($member->welcome_member_voucher_generated_at) {
                Log::channel('voucher_generation')->info(
                    'welcome_member_voucher_generation',
                    [
                        'Generate a voucher to welcome new members by the end of the job: ' . Carbon::now()->format(
                            'Y-m-d H:i:s'
                        ) . ' Welcome, member! The voucher has already been generated for your Member ID : ' . $member->id,
                    ]
                );

                return;
            }

            $expiryDate = null;
            if ($voucherConfiguration->validity_days > 0) {
                $expiryDate = now()->addDays($voucherConfiguration->validity_days);
            }

            $voucherQueries = resolve(VoucherQueries::class);
            $voucher = $voucherQueries->addNew(
                $voucherConfiguration,
                (float) $voucherConfiguration->get_value,
                $voucherConfiguration->discount_type,
                $expiryDate,
                $member->id,
            );

            $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CREATED->value,
                now()->format('Y-m-d H:i:s'),
                null,
                $member->created_location_id
            );

            $memberQueries = resolve(MemberQueries::class);
            $memberQueries->updateWelcomeMemberVoucherDetails($member, $voucher->id);

            Log::channel('voucher_generation')->info('welcome_member_voucher_generation', [
                'Welcome! A voucher has been generated for Member:' . $member->id . '.',
            ]);
        } catch (Throwable $throwable) {
            Log::error('welcome Member voucher job error:', [
                'member_id' => 'Member Id: ' . $member->id,
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('voucher_generation')->info('welcome_member_voucher_generation', [
            'Generate a welcome member voucher upon job completion: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
