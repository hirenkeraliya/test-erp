<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class OldMemberDataMigrateMainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $oldMembers = DB::connection('old_data_mysql')
            ->table('tblmember')
            ->select('MemberCode')
            ->orderBy('MemberCode', 'asc')
            ->get();

        foreach ($oldMembers->chunk(100) as $chunkMembers) {
            OldMemberDataMigrateJob::dispatch($chunkMembers->pluck('MemberCode')->toArray())->onQueue(
                config('horizon.default_queue_name')
            );
        }
    }
}
