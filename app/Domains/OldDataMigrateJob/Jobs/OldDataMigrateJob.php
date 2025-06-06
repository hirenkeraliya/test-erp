<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OldDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        OldStyleDataMigrateJob::dispatch()->onQueue('high');
        OldSizeDataMigrateJob::dispatch()->onQueue('high');
        OldCategoryDataMigrateJob::dispatch()->onQueue('high');
        OldColorDataMigrateJob::dispatch()->onQueue('high');
        OldDepartmentDataMigrateJob::dispatch()->onQueue('high');
        OldUMODataMigrateJob::dispatch()->onQueue('high');
        OldBrandDataMigrateJob::dispatch()->onQueue('high');
        OldRegionDataMigrateJob::dispatch()->onQueue('high');
    }
}
