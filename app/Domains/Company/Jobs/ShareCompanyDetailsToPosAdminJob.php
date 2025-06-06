<?php

declare(strict_types=1);

namespace App\Domains\Company\Jobs;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Services\PosAdminService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShareCompanyDetailsToPosAdminJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    protected int $tries = 3;

    public function __construct(
        private readonly int $companyId,
    ) {
    }

    public function handle(): void
    {
        Log::channel('pos_admin')->info('Share Company details add/update to POS Admin', [
            'Share Company details add/update job start time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $posAdminService = resolve(PosAdminService::class);

        if (! $posAdminService->isEnabled()) {
            return;
        }

        if (! $posAdminService->getToken()) {
            return;
        }

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdForPosAdmin($this->companyId);

        $records = [
            'reference_uuid' => $company->uuid,
            'name' => $company->name,
            'code' => $company->code,
            'email' => $company->email,
            'url' => config('app.url'),
            'site_identifier_key' => config('app.site_identifier_key'),
            'environment' => config('app.site_env'),
        ];

        try {
            $posAdminService->updateCompanyDetails($records);
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Company Details job to POS Admin error');

            $this->fail($throwable);
        }

        Log::channel('pos_admin')->info('Share Company details add/update to POS Admin', [
            'Share Company details add/update job Finished: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }
}
