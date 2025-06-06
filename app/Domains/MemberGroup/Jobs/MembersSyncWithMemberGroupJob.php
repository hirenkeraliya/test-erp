<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Jobs;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use App\Models\MemberGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class MembersSyncWithMemberGroupJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    private int $totalRecords;

    public function __construct(
        protected int $memberGroupId,
        protected int $companyId,
        protected int $importRecordId,
        private readonly ?int $startIndex = null,
        private readonly ?int $endIndex = null,
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

        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecordService = resolve(ImportRecordService::class);
        $importRecord = $importRecordQueries->getById($this->importRecordId, $this->companyId);
        $isSynced = $companyQueries->getByIdWithAutoIncludeMemberGroup($this->companyId)->auto_include_in_member_group;

        try {
            $memberGroup = $memberGroupQueries->getById($this->memberGroupId, $this->companyId);
            $members = $memberGroupQueries->getMatchMembersOfMemberGroup($memberGroup, null);

            $this->totalRecords = $members->count();

            $importRecordQueries->markAsInProgress($importRecord, $members->count());

            $highestRow = $this->totalRecords;
            $jobRestartTime = $importRecordService->getJobRestartTime();

            if ($importRecordService->isThisFirstImportCycle($this->startIndex, $this->endIndex)) {
                $this->totalRecords = $highestRow - 1;
            }

            for ($rowIndex = $this->startIndex ?: 0; $rowIndex <= $highestRow - 1; $rowIndex++) {
                if ($importRecordService->jobIsReadyToExpire($jobRestartTime)) {
                    $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex, $memberGroup);

                    return;
                }

                $data = [
                    'member_id' => $members[$rowIndex]->id,
                    'member_group_id' => $memberGroup->id,
                    'is_synced' => $isSynced,
                ];
                $memberGroupMemberQueries->addNew($data);

                if ($importRecordService->hasMoreRecords($highestRow, $rowIndex, $this->totalRecords)) {
                    $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex, $memberGroup);

                    return;
                }
            }

            $importRecordQueries->markAsCompletedFromMemberGroup($importRecord);
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Members sync with member group job error.');

            $this->fail($throwable);
        }
    }

    private function restartJobWithFetchRecordLimit(
        ImportRecordService $importRecordService,
        int $rowIndex,
        MemberGroup $memberGroup
    ): void {
        $newEndRowNumber = $importRecordService->getNewEndRowNumber(
            $rowIndex,
            $this->endIndex,
            $this->startIndex,
            $this->totalRecords
        );

        self::dispatch(
            $memberGroup->id,
            $this->companyId,
            $this->importRecordId,
            $rowIndex,
            $newEndRowNumber
        )->onQueue('medium');
    }
}
