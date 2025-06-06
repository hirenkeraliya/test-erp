<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class OldProductDataMigrateMainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $oldProduct = DB::connection('old_data_mysql')
            ->table('tblDistinctInventory')
            ->select(DB::raw('max(id) as max_id'))
            ->first();

        if (! $oldProduct) {
            return;
        }

        /* @phpstan-ignore-next-line */
        for ($startId = 0; $startId <= (int) $oldProduct->max_id; $startId += 100) {
            $endId = $startId + 99;
            OldProductDataMigrateJob::dispatch($startId, $endId)->onQueue('high');
        }
    }
}
