<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalCompanyWiseProductJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $draftProductId,
    ) {
    }

    public function handle(): void
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getApprovedExternalCompaniesWithBasicColumns();

        try {
            foreach ($externalCompanies as $externalCompany) {
                if (! $externalCompany->external_company_id) {
                    continue;
                }

                ExternalProductCreateJob::dispatch($externalCompany->id, $this->draftProductId)->onQueue('medium');
            }
        } catch (Throwable $throwable) {
            Log::error('External Company Wise Product Job Error', [
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
