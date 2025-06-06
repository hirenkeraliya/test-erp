<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\Domains\SmsHistory\SmsHistoryQueries;
use App\Services\CelcomSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendConfirmationSmsJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $mobileNumber,
        private readonly string $message,
        private readonly int $smsHistoryId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('member_app')->info('member_app', ['Member Send SMS job has started.']);

        $smsHistoryQueries = resolve(SmsHistoryQueries::class);
        $celcomService = resolve(CelcomSmsService::class);

        if (! $celcomService->isEnabled()) {
            abort(417, 'Apologies, but the SMS service is currently unavailable. Please try again later.');
        }

        try {
            $response = $celcomService->sendSms($this->mobileNumber, $this->message);

            if (! $response['status']) {
                Log::channel('member_app')->error('member_app', [
                    'message' => 'Something Went Wrong.',
                    'response_data' => $response,
                ]);

                return;
            }

            $smsHistoryQueries->updateById($response, $this->smsHistoryId);

            Log::channel('member_app')->info('member_app', ['Member Send SMS Job completed.']);
        } catch (Throwable $throwable) {
            Log::error('The Member Send SMS job failed', [
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
