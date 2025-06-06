<?php

declare(strict_types=1);

namespace App\Domains\MemberGroupMember\Jobs;

use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberGroupSyncJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    protected int $totalRecords;

    public function __construct(
        protected int $memberGroupId,
        protected int $companyId,
        protected int $importRecordId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecord = $importRecordQueries->getById($this->importRecordId, $this->companyId);

        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);

        $memberGroupMembers = $memberGroupMemberQueries->getByMemberGroupId($this->memberGroupId);

        $this->totalRecords = $memberGroupMembers->count();

        try {
            $importRecordQueries->markAsInProgress($importRecord, $this->totalRecords);

            foreach ($memberGroupMembers as $memberGroupMember) {
                $memberGroupMember->is_synced = true;
                $memberGroupMember->save();
            }

            $importRecordQueries->markAsCompletedFromMemberGroup($importRecord);
        } catch (Throwable $throwable) {
            Log::error('Member Group Sync Job Error', [
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
