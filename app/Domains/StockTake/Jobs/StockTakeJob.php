<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Jobs;

use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockTake\StockTakeQueries;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Models\ImportRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class StockTakeJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    private int $totalRecords;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $stockTakeId,
        private readonly int $importRecordId,
        private readonly int $companyId,
        private readonly ?int $startIndex = null,
        private readonly ?int $endIndex = null,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecordService = resolve(ImportRecordService::class);
        $productQueries = resolve(ProductQueries::class);
        $stockTakeQueries = resolve(StockTakeQueries::class);

        $importRecord = $importRecordQueries->getById($this->importRecordId, $this->companyId);
        $stockTake = $stockTakeQueries->getLocationColumnsByIdAndCompanyId($this->stockTakeId, $this->companyId);

        /** @var int $locationId */
        $locationId = $stockTake->location_id;
        $productIds = $productQueries->getActiveProductIds($importRecord->company_id, $locationId);

        try {
            $this->totalRecords = $productIds->count();

            $importRecordQueries->markAsInProgress($importRecord, $productIds->count());

            $highestRow = $this->totalRecords;
            $jobRestartTime = $importRecordService->getJobRestartTime();

            if ($importRecordService->isThisFirstImportCycle($this->startIndex, $this->endIndex)) {
                $this->totalRecords = $highestRow - 1;
            }

            for ($rowIndex = $this->startIndex ?: 0; $rowIndex <= $highestRow - 1; $rowIndex++) {
                if ($importRecordService->jobIsReadyToExpire($jobRestartTime)) {
                    $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex);

                    return;
                }

                $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
                $stockTakeProductQueries->addNewWithoutActualStock($productIds[$rowIndex], $stockTake);

                if ($importRecordService->hasMoreRecords($highestRow, $rowIndex, $this->totalRecords)) {
                    $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex);

                    return;
                }
            }

            $stockTakeQueries = resolve(StockTakeQueries::class);
            $stockTakeQueries->updateStockTakeStatus($stockTake->id);

            $importRecordQueries->markAsCompleted($importRecord);

            $this->addNotification($importRecord);
        } catch (Throwable $throwable) {
            Log::error('Stock Take Job Error', [
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

    private function restartJobWithFetchRecordLimit(ImportRecordService $importRecordService, int $rowIndex): void
    {
        $newEndRowNumber = $importRecordService->getNewEndRowNumber(
            $rowIndex,
            $this->endIndex,
            $this->startIndex,
            $this->totalRecords
        );

        self::dispatch(
            $this->stockTakeId,
            $this->importRecordId,
            $this->companyId,
            $rowIndex,
            $newEndRowNumber
        )->onQueue('high');
    }

    private function addNotification(ImportRecord $importRecord): void
    {
        $notificationQueries = resolve(NotificationQueries::class);
        $message = 'Stock Take created successfully.';
        $textMessage = 'Stock Take created successfully.';
        $notificationQueries->addNew(
            companyId: $importRecord->company_id,
            sourceUser: null,
            fromUserId: null,
            destinationUser: $importRecord->created_by_type,
            toUserId: $importRecord->created_by_id,
            message: $message,
            textMessage: $textMessage,
        );
    }
}
