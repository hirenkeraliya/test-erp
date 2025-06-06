<?php

declare(strict_types=1);

namespace App\Domains\PosAdmin\Jobs;

use App\Services\PosAdminService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PosAppReleasesJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $maximumAttempts = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $tries = 3
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('pos_admin')->info('POS Admin App releases', [
            'POS Admin App releases job start time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $posAdminService = resolve(PosAdminService::class);

        if (! $posAdminService->isEnabled()) {
            Log::error('Pos Admin', ['Credentials are not set.']);

            return;
        }

        if (! $posAdminService->getToken()) {
            $this->logAndRetry('Token generation unsuccessful.', []);

            return;
        }

        try {
            $response = $posAdminService->getAppReleases();

            if (! $response['status']) {
                if (401 === $response['status_code']) {
                    $posAdminService->removeTokenFromCache();
                }

                $this->logAndRetry('Unable to retrieve app releases.', $response);

                return;
            }

            Cache::put('pos_admin_app_releases', $response['response_data']->data, 0o6 * 60 * 60);
        } catch (Throwable $throwable) {
            Log::error('Pos App Releases Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('pos_admin')->info('POS Admin App releases', [
            'Pos Admin App Releases Job Finished: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function logAndRetry(string $message, ?array $response): void
    {
        Log::channel('pos_admin')->error(
            'Pos Admin',
            [
                $message . ($this->tries < $this->maximumAttempts) !== '' ? ' Retrying in 5 seconds' : ' The maximum number of attempts has been reached. No further attempts will be made.',
                'response' => $response,
            ]
        );

        if ($this->tries < $this->maximumAttempts) {
            self::dispatch($this->tries - 1)->delay(now()->addSeconds(5))->onQueue(
                config('horizon.default_queue_name')
            );
        }
    }
}
