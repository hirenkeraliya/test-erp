<?php

declare(strict_types=1);

namespace App\Domains\StockTakeProduct\Jobs;

use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class StockTakeProductsUpdateActualStockJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $compareStockDate = '',
        private readonly int $stockTakeId = 0,
        private readonly array $productIds = [],
        private readonly int $locationId = 0,
        private readonly string $createdByType = '',
        private readonly int $companyId = 0,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $productQueries = resolve(ProductQueries::class);

        $products = $productQueries->getStockByStoreIdProductIdsAndDate(
            $this->locationId,
            $this->productIds,
            $this->compareStockDate,
        );

        $storeInventories = $products->pluck('latestInventoryUpdate');

        try {
            foreach ($this->productIds as $productId) {
                $inventoryUpdate = $storeInventories->firstWhere('product_id', $productId);
                $closingStock = $inventoryUpdate->closing_stock ?? 0;

                $stockTakeProductQueries->updateProductActualStock(
                    $productId,
                    (float) $closingStock,
                    $this->stockTakeId
                );
            }

            $notificationQueries = resolve(NotificationQueries::class);

            $message = 'Stock Take actual stock submitted successfully.';
            $textMessage = 'Stock Take actual stock submitted successfully.';

            $notificationQueries->addNew(
                companyId: $this->companyId,
                sourceUser: null,
                fromUserId: null,
                destinationUser: $this->createdByType,
                toUserId: $this->locationId,
                message: $message,
                textMessage: $textMessage,
            );
        } catch (Throwable $throwable) {
            Log::error('Stock Take Products Update Actual Stock Job Error', [
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
