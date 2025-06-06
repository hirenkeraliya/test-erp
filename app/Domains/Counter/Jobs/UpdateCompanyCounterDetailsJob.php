<?php

declare(strict_types=1);

namespace App\Domains\Counter\Jobs;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Models\Location;
use App\Services\PosAdminService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UpdateCompanyCounterDetailsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $counterQueries = resolve(CounterQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $posAdminService = resolve(PosAdminService::class);

        $companies = $companyQueries->getAll();

        $records = [];

        if (! $posAdminService->getToken()) {
            return;
        }

        foreach ($companies as $company) {
            $counters = $counterQueries->getAllByCompanyId($company->id);
            foreach ($counters as $counter) {
                /** @var Location $location */
                $location = $counter->location;

                $records[] = [
                    'uuid' => $company->uuid,
                    'name' => $counter->name,
                    'app_version' => $counter->app_version,
                    'store_name' => $location->name,
                    'last_updated_at' => $counter->app_version_updated_at,
                ];
            }

            try {
                $posAdminService->updateAllCounterDetails($records);
            } catch (Throwable $throwable) {
                CommonFunctions::logErrorDetails($throwable, 'Counter Details job to POS Admin error');
                $this->fail($throwable);
            }
        }
    }
}
