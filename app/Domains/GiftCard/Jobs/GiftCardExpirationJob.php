<?php

declare(strict_types=1);

namespace App\Domains\GiftCard\Jobs;

use App\Domains\GiftCard\GiftCardQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GiftCardExpirationJob implements ShouldQueueAfterCommit
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
        Log::channel('gift_card_expiration')->info('gift_card_expiration', [
            'Start time of the Gift Card Expiration job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        DB::beginTransaction();

        try {
            $giftCardQueries = resolve(GiftCardQueries::class);

            $giftCardCounts = $giftCardQueries->markGiftCardsAsExpired();

            DB::commit();

            Log::channel('gift_card_expiration')->info('gift_card_expiration', [
                $giftCardCounts . ' gift cards have been marked as expired..',
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Gift card expiration job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('gift_card_expiration')->info('gift_card_expiration', [
            'End time of the Gift Card Expiration job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
