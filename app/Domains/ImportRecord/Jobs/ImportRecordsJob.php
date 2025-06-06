<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Events\DreamPriceUpdateEvent;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Readers\FileReaderFilters;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\ImportRecordFailedRow\ImportRecordFailedRowQueries;
use App\Domains\Notification\NotificationQueries;
use App\Models\ImportRecord;
use App\Services\SpreadsheetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

class ImportRecordsJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $totalRecords;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly ImportRecord $importRecord,
        private readonly ?int $startRowNumber = null,
        private readonly ?int $endRowNumber = null,
    ) {
        $this->totalRecords = $importRecord->records_in_file ?: 0;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecordService = resolve(ImportRecordService::class);
        $spreadsheetService = resolve(SpreadsheetService::class);

        $headerColumns = $this->importRecord->header_columns ?: [];
        $jobRestartTime = $importRecordService->getJobRestartTime();
        $media = $importRecordQueries->getUploadedMedia($this->importRecord);
        $fullFilePath = $importRecordQueries->getFilePath($this->importRecord);
        $readerType = Str::title(pathinfo($media->file_name, PATHINFO_EXTENSION));

        if (! $importRecordService->isThisFirstImportCycle($this->startRowNumber, $this->endRowNumber)) {
            $spreadsheetService->setRowFilters(
                resolve(FileReaderFilters::class, [
                    'startRow' => $this->startRowNumber,
                    'endRow' => $this->endRowNumber,
                ])
            );
        }

        $spreadsheetService->loadFileDetails($readerType, $fullFilePath);
        $highestRow = $spreadsheetService->getHighestRow();
        $highestColumn = $spreadsheetService->getHighestColumn();
        $highestColumnIndex = $spreadsheetService->columnIndexFromString($highestColumn);

        if ($importRecordService->isThisFirstImportCycle($this->startRowNumber, $this->endRowNumber)) {
            $this->totalRecords = $highestRow - 1;

            $importRecordQueries->markAsInProgress($this->importRecord, $this->totalRecords);
        }

        for ($rowIndex = $this->startRowNumber ?: 1; $rowIndex <= $highestRow; $rowIndex++) {
            // We restart the job once we reach 80% of the job expiration time.
            // This way, we try to cover maximum number of import records within each job execution.
            if ($importRecordService->jobIsReadyToExpire($jobRestartTime)) {
                $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex);

                return;
            }

            if ($importRecordService->headerColumnsAlreadySet($rowIndex, $headerColumns)) {
                continue;
            }

            $recordDetails = [];
            $validationErrors = [];

            for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                $columnValue = $spreadsheetService->getColumnValueFor($rowIndex, $columnIndex);

                if (1 === $rowIndex && $columnValue) {
                    $headerColumns[] = Str::of($columnValue)->lower()->replace(' ', '_')->snake()->value();

                    continue;
                }

                try {
                    if (array_key_exists($columnIndex - 1, $headerColumns)) {
                        if (
                            $columnValue && $this->isColumnValueHasDateFormat(
                                (string) $headerColumns[$columnIndex - 1]
                            ) &&
                            (float) $columnValue == $columnValue
                        ) {
                            $columnValue = Date::excelToDateTimeObject($columnValue)->format('Y-m-d H:i:s');
                        }

                        $recordDetails[$headerColumns[$columnIndex - 1]] = $columnValue;
                    }
                } catch (Throwable) {
                    $validationErrors[] = 'Specified date format is invalid. Please use the same format as mentioned';
                }
            }

            if (1 === $rowIndex) {
                $importRecordQueries->saveHeaderColumns($this->importRecord, $headerColumns);

                continue;
            }

            $importRecordClassObject = ImportTypes::getClassFor($this->importRecord->type_id);

            $validationErrors = array_merge(
                $validationErrors,
                $importRecordClassObject->validate($recordDetails, $this->importRecord)
            );

            try {
                if ([] === $validationErrors) {
                    $importRecordClassObject->save($recordDetails, $this->importRecord);

                    $importRecordQueries->incrementImportedRecordsCount($this->importRecord);
                } else {
                    $importRecordQueries->incrementFailedRecordsCount($this->importRecord);

                    $this->saveFailedRecordDetails($recordDetails, $validationErrors);
                }
            } catch (Throwable $throwable) {
                Log::error('Import Record Job Error', [
                    'error_message' => 'Error message: ' . $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);

                $this->fail($throwable);
            }

            if ($importRecordService->hasMoreRecords($highestRow, $rowIndex, $this->totalRecords)) {
                $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex);

                return;
            }
        }

        $this->updateImportRecordCompleted($importRecordQueries);
    }

    private function restartJobWithFetchRecordLimit(ImportRecordService $importRecordService, int $rowIndex): void
    {
        $newEndRowNumber = $importRecordService->getNewEndRowNumber(
            $rowIndex,
            $this->endRowNumber,
            $this->startRowNumber,
            $this->totalRecords
        );

        self::dispatch($this->importRecord, $rowIndex, $newEndRowNumber)->onQueue('high');
    }

    private function saveFailedRecordDetails(array $recordDetails, array $validationErrors): void
    {
        $importRecordFailedRowQueries = resolve(ImportRecordFailedRowQueries::class);
        $importRecordFailedRowQueries->addNew($recordDetails, $validationErrors, $this->importRecord->id);
    }

    private function updateImportRecordCompleted(ImportRecordQueries $importRecordQueries): void
    {
        $importRecordQueries->markAsCompleted($this->importRecord);
        $this->callToModuleEvent($this->importRecord);

        Bus::chain([
            new GenerateFailedRecordsFileJob($this->importRecord->id, $this->importRecord->company_id),
            new SendImportRecordsCompletionEmailJob($this->importRecord->id, $this->importRecord->company_id),
        ])->onQueue(config('horizon.default_queue_name'))->dispatch();

        $this->addNotification($this->importRecord);
    }

    private function callToModuleEvent(ImportRecord $importRecord): void
    {
        $dreamPriceQueries = resolve(DreamPriceQueries::class);

        if ($importRecord->module_type === ModelMapping::DREAM_PRICE->name) {
            $dreamPrice = $dreamPriceQueries->getByDreamPrice(
                (int) $importRecord->module_id,
                $importRecord->company_id
            );
            if ($dreamPrice) {
                event(new DreamPriceUpdateEvent($dreamPrice));
            }
        }
    }

    private function addNotification(ImportRecord $importRecord): void
    {
        $notificationQueries = resolve(NotificationQueries::class);
        $notificationQueries->addNew(
            companyId: $importRecord->company_id,
            sourceUser: null,
            fromUserId: null,
            destinationUser: $importRecord->created_by_type,
            toUserId: $importRecord->created_by_id,
            message: ImportTypes::getFormattedCaseName($importRecord->type_id) . ' import completed.',
            textMessage: ImportTypes::getFormattedCaseName($importRecord->type_id) . ' import completed.'
        );
    }

    private function isColumnValueHasDateFormat(string $columnName): bool
    {
        $matchingWords = ['created_at', 'updated_at', 'original_created_at'];

        return in_array($columnName, $matchingWords);
    }
}
