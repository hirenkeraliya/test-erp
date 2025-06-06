<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\Jobs;

use App\Domains\StoreManager\StoreManagerQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForgotPasswordEmailJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $storeManagerId,
        private readonly int $companyId,
        private readonly string $forgotPasswordToken,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $storeManagerQueries = resolve(StoreManagerQueries::class);
            $storeManager = $storeManagerQueries->getByStoreManagerCompanyId($this->storeManagerId, $this->companyId);
            $storeManager->sendPasswordResetNotification($this->forgotPasswordToken);
        } catch (Throwable $throwable) {
            Log::error('Forgot Password Email Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
