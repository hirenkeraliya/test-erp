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
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateMemberBirthdayVouchersJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('voucher_generation')->info('birthday_voucher_generation', [
            'Generate job start time birthday vouchers: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        try {
            $today = Carbon::now();
            $currentYearMonth = now()->format('Y-m');

            $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
            $birthdayVoucherConfigurations = $voucherConfigurationQueries->getBirthdayVoucherConfiguration($today);

            if ($birthdayVoucherConfigurations->isNotEmpty()) {
                $memberQueries = resolve(MemberQueries::class);
                $companyIds = $birthdayVoucherConfigurations->pluck('company_id')->toArray();
                $members = $memberQueries->getMembersByBirthDate($today, $companyIds);

                $voucherQueries = resolve(VoucherQueries::class);
                $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);

                $totalVouchers = 0;
                foreach ($birthdayVoucherConfigurations as $birthdayVoucherConfiguration) {
                    $expiryDate = null;
                    if ($birthdayVoucherConfiguration->validity_days) {
                        $expiryDate = now()->addDays($birthdayVoucherConfiguration->validity_days);
                    }

                    foreach ($members->where('company_id', $birthdayVoucherConfiguration->company_id) as $member) {
                        if (null !== $member->birthday_voucher_last_generated_at) {
                            /** @var Carbon $lastBirthdayVoucherGeneratedAt */
                            $lastBirthdayVoucherGeneratedAt = Carbon::createFromFormat(
                                'Y-m-d',
                                $member->birthday_voucher_last_generated_at
                            );

                            if ($lastBirthdayVoucherGeneratedAt->format('Y-m') === $currentYearMonth) {
                                continue;
                            }
                        }

                        $voucher = $voucherQueries->addNew(
                            $birthdayVoucherConfiguration,
                            (float) $birthdayVoucherConfiguration->get_value,
                            $birthdayVoucherConfiguration->discount_type,
                            $expiryDate,
                            $member->id,
                        );

                        $voucherTransactionQueries->addNew(
                            $voucher->id,
                            VoucherTransactionActionTypes::CREATED->value,
                            now()->format('Y-m-d H:i:s'),
                            null,
                            null
                        );

                        $memberQueries->updateBirthdayVoucherDetails($member, $voucher->id);
                        $totalVouchers++;
                    }
                }

                Log::channel('voucher_generation')->info('birthday_voucher_generation', [
                    $totalVouchers . ' birthday vouchers generated.',
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error('Birthday voucher job error:', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('voucher_generation')->info('birthday_voucher_generation', [
            'Generate birthday vouchers job end time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
