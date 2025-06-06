<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Jobs;

use App\Domains\Voucher\VoucherQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VoucherExpirationJob implements ShouldQueue
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
        Log::channel('voucher_expiration')->info('voucher_expiration', [
            'The start time of the voucher expiration job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $voucherQueries = resolve(VoucherQueries::class);
        $voucherQueries->getVoucherWithExpiryDue();

        Log::channel('voucher_expiration')->info('voucher_expiration', [
            'End time of the voucher expiration job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
