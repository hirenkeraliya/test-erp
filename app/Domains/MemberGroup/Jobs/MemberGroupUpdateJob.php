<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Jobs;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberGroupUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $memberGroupId,
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
        $companyQueries = resolve(CompanyQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);

        DB::beginTransaction();
        try {
            $memberGroup = $memberGroupQueries->getById($this->memberGroupId, $this->companyId);
            $isSynced = $companyQueries->getByIdWithAutoIncludeMemberGroup(
                $this->companyId
            )->auto_include_in_member_group;

            $member = $memberGroupQueries->getMatchMemberOfMemberGroup($memberGroup, $this->memberId);

            if ($member) {
                $data = [
                    'member_id' => $member->id,
                    'member_group_id' => $memberGroup->id,
                    'is_synced' => $isSynced,
                ];
                $memberGroupMemberQueries->addNew($data);
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            CommonFunctions::logErrorDetails($throwable, 'Member group update job error.');

            $this->fail($throwable);
        }
    }
}
