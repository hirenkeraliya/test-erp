<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\Company\CompanyQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollection\Services\ProductCollectionEcommerceService;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Models\ProductCollection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateUpdateProductCollectionJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    private int $totalRecords;

    public function __construct(
        protected int $productCollectionId,
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
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecordService = resolve(ImportRecordService::class);
        $importRecord = $importRecordQueries->getById($this->importRecordId, $this->companyId);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $productCollectionsEcommerceService = resolve(ProductCollectionEcommerceService::class);

        $companyQueries = resolve(CompanyQueries::class);
        $isSynced = $companyQueries->getWithAutoIncludeInCollectionsById($this->companyId)->auto_include_in_collections;
        $productIds = [];
        try {
            $productCollection = $productCollectionQueries->edit($this->productCollectionId, $this->companyId);
            $products = $productCollectionQueries->getMatchProducts($productCollection, $this->companyId);
            $this->totalRecords = $products->count();

            $importRecordQueries->markAsInProgress($importRecord, $products->count());

            $highestRow = $this->totalRecords;
            $jobRestartTime = $importRecordService->getJobRestartTime();

            if ($importRecordService->isThisFirstImportCycle($this->startIndex, $this->endIndex)) {
                $this->totalRecords = $highestRow - 1;
            }

            for ($rowIndex = $this->startIndex ?: 0; $rowIndex <= $highestRow - 1; $rowIndex++) {
                if ($importRecordService->jobIsReadyToExpire($jobRestartTime)) {
                    $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex, $productCollection);

                    return;
                }

                $productCollectionProductData = [
                    'product_id' => $products[$rowIndex]->id,
                    'product_collection_id' => $productCollection->id,
                    'is_synced' => $isSynced,
                ];

                $productIds[] = $productCollectionProductData['product_id'];

                $productCollectionProductQueries->addNew($productCollectionProductData);

                if ($importRecordService->hasMoreRecords($highestRow, $rowIndex, $this->totalRecords)) {
                    $this->restartJobWithFetchRecordLimit($importRecordService, $rowIndex, $productCollection);

                    return;
                }
            }

            $importRecordQueries->markAsCompleted($importRecord);

            $productCollectionsEcommerceService->productCollectionCreateUpdateEcommerceService(
                $productCollection,
                $productIds
            );
        } catch (Throwable $throwable) {
            Log::error('Product Collection Update Job Error', [
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

    private function restartJobWithFetchRecordLimit(
        ImportRecordService $importRecordService,
        int $rowIndex,
        ProductCollection $productCollection
    ): void {
        $newEndRowNumber = $importRecordService->getNewEndRowNumber(
            $rowIndex,
            $this->endIndex,
            $this->startIndex,
            $this->totalRecords
        );

        self::dispatch(
            $productCollection->id,
            $this->companyId,
            $this->importRecordId,
            $rowIndex,
            $newEndRowNumber
        )->onQueue('medium');
    }
}
