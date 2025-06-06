<?php

declare(strict_types=1);

namespace App\Domains\StoreManagerAuthorizationCode\Jobs;

use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreManagerAuthorizationCodeJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function handle(): void
    {
        Log::channel('store_manager_authorization_code')->info('store-manager-authorization-code', [
            'The job for store manager authorization code has started. Date: ' . now()->format('Y-m-d'),
        ]);

        $storeManagerAuthorizationCodeQueries = resolve(StoreManagerAuthorizationCodeQueries::class);
        $storeManagerAuthorizationCodes = $storeManagerAuthorizationCodeQueries->getOnlyActiveStoreManagerAuthorizationCodes();

        if ($storeManagerAuthorizationCodes->isEmpty()) {
            return;
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');

        try {
            foreach ($storeManagerAuthorizationCodes as $storeManagerAuthorizationCode) {
                if ($now > $storeManagerAuthorizationCode->getExpiryDate()) {
                    $storeManagerAuthorizationCodeQueries->markStatusAsExpired(
                        $storeManagerAuthorizationCode->getKey()
                    );
                }
            }
        } catch (Throwable $throwable) {
            Log::error('Automated Notification Deadline Request Stock Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('store_manager_authorization_code')->info('store-manager-authorization-code', [
            'The job for store manager authorization code has ended. Date: ' . now()->format('Y-m-d'),
        ]);
    }
}
