<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Jobs;

use App\CommonFunctions;
use App\Domains\MemberGroup\MemberGroupQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateUpdateMemberSyncWithMemberGroupJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $memberId,
        protected int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroups = $memberGroupQueries->geSmartMemberGroupsByCompanyId($this->companyId);

        if ($memberGroups->isEmpty()) {
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($memberGroups as $memberGroup) {
                MemberGroupUpdateJob::dispatch($memberGroup->id, $this->memberId, $this->companyId)->onQueue(
                    config('horizon.default_queue_name')
                );
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            CommonFunctions::logErrorDetails($throwable, 'Create & Update member sync with member group job error.');

            $this->fail($throwable);
        }
    }
}
