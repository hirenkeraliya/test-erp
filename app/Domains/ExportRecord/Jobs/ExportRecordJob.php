<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Jobs;

use App\Domains\Barcode\Exports\ExportBarcode;
use App\Domains\ExportRecord\ExportRecordQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExportRecordJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly int $exportRecordId,
        protected readonly int $companyId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('export_report_job')->info('export_report_job', [
            'Export record job start time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $exportBarcode = resolve(ExportBarcode::class);
        DB::beginTransaction();

        try {
            /** @var Job $job */
            $job = $this->job;

            $jobId = $job->getJobId();

            $exportRecordQueries = resolve(ExportRecordQueries::class);

            $exportRecordQueries->updateStartedAtAndJobId($this->exportRecordId, $this->companyId, $jobId);

            $exportBarcode->export($this->exportRecordId, $this->companyId);

            Log::channel('export_report_job')->info('export_report_job', [
                'Export record job finish time:' . Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Export record job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('export_report_job')->info('export_report_job', [
            'Export record job end time: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
