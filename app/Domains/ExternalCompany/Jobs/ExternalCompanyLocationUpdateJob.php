<?php

declare(strict_types=1);

namespace App\Domains\ExternalCompany\Jobs;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalLocation\Jobs\ExternalLocationUpdateJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalCompanyLocationUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAll();

        try {
            foreach ($externalCompanies as $externalCompany) {
                ExternalLocationUpdateJob::dispatch($externalCompany->id)->onQueue('medium');
            }
        } catch (Throwable $throwable) {
            Log::error('External Company Location Update Job Error', [
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
