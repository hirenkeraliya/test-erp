<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\ProductCollection\ProductCollectionQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductCollectionUpdateByProductJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $productId,
        protected int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($this->companyId);

        if ($productCollections->isEmpty()) {
            return;
        }

        DB::beginTransaction();

        try {
            foreach ($productCollections as $productCollection) {
                ProductCollectionUpdateJob::dispatch(
                    $productCollection->id,
                    $this->productId,
                    $this->companyId
                )->onQueue('medium');
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Approved Product/Update approved product collection job error', [
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
