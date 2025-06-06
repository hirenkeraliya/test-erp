<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductCollectionsSyncJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    protected int $totalRecords;

    public function __construct(
        protected int $productCollectionId,
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

        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

        $productCollectionProducts = $productCollectionProductQueries->syncByProductCollectionId(
            $this->productCollectionId
        );

        $this->totalRecords = $productCollectionProducts->count();

        try {
            $importRecordQueries->markAsInProgress($importRecord, $this->totalRecords);

            foreach ($productCollectionProducts as $productCollectionProduct) {
                $productCollectionProduct->is_synced = true;
                $productCollectionProduct->save();
            }

            $productCollectionQueries = resolve(ProductCollectionQueries::class);
            $productCollectionQueries->updateLastSyncById($this->productCollectionId);

            $importRecordQueries->markAsCompleted($importRecord);
        } catch (Throwable $throwable) {
            Log::error('Product Collection Sync Job Error', [
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
