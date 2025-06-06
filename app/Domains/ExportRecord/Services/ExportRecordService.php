<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Services;

use App\Domains\ExportRecord\Enums\ExportRecordJobStatus;
use Carbon\Carbon;

class ExportRecordService
{
    public function getJobRestartTime(): \Illuminate\Support\Carbon
    {
        $expTime = config('horizon.environments.' . config('app.env') . '.supervisor-1.timeout', 60);

        $jobCoveragePercentage = (int) config('app.excel.export.job_coverage_percentage');

        return now()->addSeconds((int) ($expTime * ($jobCoveragePercentage / 100)));
    }

    public function isJobReadyToExpire(Carbon $jobRestartTime): bool
    {
        return now()->greaterThanOrEqualTo($jobRestartTime);
    }

    public function getNewEndRowNumber(
        int $insertedRowsCount,
        int $totalQueryRecords,
        int $currentStartRowNumber,
        ExportRecordJobStatus $jobStatus
    ): int {
        $difference = $insertedRowsCount - $currentStartRowNumber;

        if (ExportRecordJobStatus::JOB_TIME_OUT === $jobStatus) {
            $percentage = config('app.excel.export.decrement_percentage');
            $difference = (int) ($difference * ($percentage / 100));
        } elseif (ExportRecordJobStatus::RECORD_COMPLETION == $jobStatus) {
            $percentage = config('app.excel.export.increment_percentage');
            $difference += (int) ($difference * ($percentage / 100));
        }

        $newEndRowNumber = $insertedRowsCount + $difference;

        if ($newEndRowNumber > $totalQueryRecords) {
            return $totalQueryRecords;
        }

        return $newEndRowNumber;
    }

    public function hasMoreRecords(int $currentRowIndex, int $endRowNumber, int $totalRecords): bool
    {
        return $currentRowIndex === $endRowNumber && $endRowNumber < $totalRecords;
    }
}
