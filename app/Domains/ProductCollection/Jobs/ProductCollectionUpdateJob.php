<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\Company\CompanyQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductCollectionUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $productCollectionId,
        protected int $productId,
        protected int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::beginTransaction();

        try {
            $productCollectionQueries = resolve(ProductCollectionQueries::class);
            $productCollection = $productCollectionQueries->edit($this->productCollectionId, $this->companyId);

            $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

            $product = $productCollectionQueries->getProductByProductCollectionAndCompany(
                $this->productId,
                $productCollection,
                $this->companyId
            );

            if ($product) {
                $companyQueries = resolve(CompanyQueries::class);
                $isSynced = $companyQueries->getWithAutoIncludeInCollectionsById(
                    $this->companyId
                )->auto_include_in_collections;

                $productCollectionProductData = [
                    'product_id' => $product->id,
                    'product_collection_id' => $productCollection->id,
                    'is_synced' => $isSynced,
                ];

                $productCollectionProductQueries->addNew($productCollectionProductData);
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Product collection update job error', [
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
