<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Jobs;

use App\Domains\ExportRecord\Enums\ExportRecordJobStatus;
use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Services\ExportRecordService;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Storage\Services\StorageService;
use App\Models\ExportRecord;
use App\Services\SpreadsheetService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportToExcelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected ?Collection $items = null;

    protected Carbon $jobStartTime;

    protected string $exportFilePath;

    protected string $exportFileName;

    protected ?string $tempFilePath = null;

    protected array $recordsToBeSaved;

    public function __construct(
        protected readonly ExportRecord $exportRecord,
        protected int $startRowNumber = 0,
        protected int $endRowNumber = 0,
    ) {
        $this->setFile();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->exportRecord->status == ExportRecordStatuses::FAILED->value) {
            return;
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        try {
            /** @var Job $job */
            $job = $this->job;
            $jobId = $job->getJobId();

            $exportRecordService = resolve(ExportRecordService::class);
            $spreadsheetService = resolve(SpreadsheetService::class);
            $storageService = resolve(StorageService::class);

            $jobExpirationTime = $exportRecordService->getJobRestartTime();

            if (0 === $this->startRowNumber) {
                $spreadsheetService = $spreadsheetService->createEmptyExcelFile($this->exportFilePath);

                $this->setHeadersIfNotSet($spreadsheetService);

                $exportFilePath = $storageService->getLocalFilePath($this->exportFilePath);

                $this->exportRecord->addMediaFromStream(Storage::get($exportFilePath))
                    ->usingName($this->exportFileName)
                    ->usingFileName($this->exportFileName)
                    ->toMediaCollection('export_file');

                $exportRecordQueries->updateStartedAtAndJobId(
                    $this->exportRecord->id,
                    $this->exportRecord->company_id,
                    $jobId
                );
            } else {
                $this->tempFilePath = $this->exportRecord->getLocalFilePath('export_file');

                $spreadsheetService = $spreadsheetService->loadFileDetails('Xlsx', $this->tempFilePath);
            }

            $exportRecordClassObject = ExportRecordTypes::getClassFor($this->exportRecord->type_id);

            $this->items = $exportRecordClassObject->fetch(
                $this->exportRecord,
                $this->startRowNumber - 1,
                $this->endRowNumber
            );

            for ($currentRowIndex = $this->startRowNumber; $currentRowIndex <= $this->endRowNumber; $currentRowIndex++) {
                if ($exportRecordService->isJobReadyToExpire($jobExpirationTime)) {
                    $this->writeRecordsToFile($spreadsheetService);

                    $this->restartWithNewJobRange(
                        $exportRecordService,
                        $currentRowIndex - 1,
                        ExportRecordJobStatus::JOB_TIME_OUT
                    );

                    return;
                }

                $itemArrayIndex = $currentRowIndex - $this->startRowNumber;

                $this->recordsToBeSaved[] = $this->items[$itemArrayIndex];

                if ($exportRecordService->hasMoreRecords(
                    $currentRowIndex,
                    $this->endRowNumber,
                    $this->exportRecord->total_records
                )) {
                    $this->writeRecordsToFile($spreadsheetService);

                    $this->restartWithNewJobRange(
                        $exportRecordService,
                        $currentRowIndex,
                        ExportRecordJobStatus::RECORD_COMPLETION
                    );

                    return;
                }
            }

            $this->writeRecordsToFile($spreadsheetService);

            $this->updateExportRecordCompleted($exportRecordQueries);

            $this->notifyUser();

            $this->removeStorageFile();
        } catch (Throwable $throwable) {
            Log::error('Export record job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
            $exportRecordQueries->markStatusAsFailed(
                $this->exportRecord->getKey(),
                $this->exportRecord->company_id,
                now()->format('Y-m-d H:i:s')
            );
        }
    }

    private function setHeadersIfNotSet(SpreadsheetService $spreadsheetService): void
    {
        $spreadsheetService->writeFromArray(
            data: $this->exportRecord->headers ?: [],
            originalFilePath: $this->exportFilePath,
            tempFilePath: $this->tempFilePath,
            startCell: 'A1',
        );

        $this->startRowNumber = 1;

        $initialRowLimit = (int) config('app.excel.export.initial_row_limit');

        $this->endRowNumber = $this->exportRecord->total_records < $initialRowLimit ? $this->exportRecord->total_records : $initialRowLimit;
    }

    private function restartWithNewJobRange(
        ExportRecordService $exportRecordService,
        int $currentRowIndex,
        ExportRecordJobStatus $jobStatus
    ): void {
        $newEndRowNumber = $exportRecordService->getNewEndRowNumber(
            $currentRowIndex,
            $this->exportRecord->total_records,
            $this->startRowNumber - 1,
            $jobStatus
        );

        self::dispatch($this->exportRecord, $currentRowIndex + 1, $newEndRowNumber)->onQueue('medium');
    }

    private function setFile(): void
    {
        $directoryPath = Storage::path('excel_exports');

        $created_at = $this->exportRecord->created_at ?: Carbon::now();

        $this->exportFileName = 'exported_excel_' . $this->exportRecord->id . '_' . $created_at->format(
            'Y-m-d_H-i-s'
        ) . '.xlsx';

        $this->exportFilePath = $directoryPath. '/' . $this->exportFileName;
    }

    private function updateExportRecordCompleted(ExportRecordQueries $exportRecordQueries): void
    {
        $exportRecordQueries->markAsCompletedAndJobEndedAt(
            $this->exportRecord->id,
            $this->exportRecord->company_id,
            Carbon::now()->format('Y-m-d H:i:s')
        );
    }

    private function notifyUser(): void
    {
        SendExportExcelCompletionEmailJob::dispatch($this->exportRecord, $this->exportFilePath)->onQueue('medium');

        $this->addNotification($this->exportRecord);
    }

    private function addNotification(ExportRecord $exportRecord): void
    {
        $notificationQueries = resolve(NotificationQueries::class);
        $notificationQueries->addNew(
            companyId: $exportRecord->company_id,
            sourceUser: null,
            fromUserId: null,
            destinationUser: $exportRecord->created_by_type,
            toUserId: $exportRecord->created_by_id,
            message: 'Your Requested Excel Report will be sent to your e-mail'
        );
    }

    private function writeRecordsToFile(SpreadsheetService $spreadsheetService): void
    {
        if ([] !== $this->recordsToBeSaved) {
            $startCell = 'A' . ($this->startRowNumber + 1);

            $spreadsheetService->writeFromArray(
                data: $this->recordsToBeSaved,
                originalFilePath: $this->exportFilePath,
                tempFilePath: $this->tempFilePath,
                startCell: $startCell,
            );

            $exportedRecordsCount = count($this->recordsToBeSaved);

            $exportRecordQueries = resolve(ExportRecordQueries::class);

            $exportRecordQueries->incrementExportedRecordsCount($this->exportRecord, $exportedRecordsCount);

            $fileContents = Storage::get($this->exportFilePath);

            $this->exportRecord->addMediaFromStream($fileContents)
                ->usingName($this->exportFileName)
                ->usingFileName($this->exportFileName)
                ->toMediaCollection('export_file');

            $this->recordsToBeSaved = [];
        }
    }

    private function removeStorageFile(): void
    {
        Storage::delete($this->exportFilePath);
    }
}
