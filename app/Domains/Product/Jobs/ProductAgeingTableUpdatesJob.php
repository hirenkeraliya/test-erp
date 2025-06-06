<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

use App\Domains\AggregateProcessTracker\AggregateProcessTrackerQueries;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerModules;
use App\Domains\AggregateProcessTracker\Jobs\UpdateAggregateProcessJob;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Models\ProductAgeing;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductAgeingTableUpdatesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $date,
        private readonly int $companyId,
    ) {
    }

    public function handle(): void
    {
        Log::channel('product_ageing_table')->info('product-ageing-table-updates', [
            'The job for product ageing has started. Date: ' . now()->format('Y-m-d'),
        ]);

        try {
            $this->checkIfAnyStoreIsCreated();

            $this->checkIfAnyProductIsCreated();

            $this->updateTheProductsStock();
        } catch (Throwable $throwable) {
            Log::error('Product Ageing Table Updates Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $aggregateProcessTrackerQueries = resolve(AggregateProcessTrackerQueries::class);
            $aggregateProcessTrackerQueries->updateTheFailedStatus(
                $this->companyId,
                AggregateProcessTrackerModules::PRODUCT_AGEING->value
            );

            $this->fail($throwable);
        }

        UpdateAggregateProcessJob::dispatch($this->companyId, AggregateProcessTrackerModules::PRODUCT_AGEING->value)
                ->onQueue(config('horizon.default_queue_name'));

        Log::channel('product_ageing_table')->info('product-ageing-table-updates', [
            'The job for product ageing has ended. Date: ' . now()->format('Y-m-d'),
        ]);
    }

    public function updateTheProductsStock(): void
    {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdates = $inventoryUpdateQueries->getYesterdayInventoryUpdateWithInventory($this->date);

        $productAgeingQueries = resolve(ProductAgeingQueries::class);

        foreach ($inventoryUpdates as $inventoryUpdate) {
            $getUpdateProductAgeReportData = $this->getWithStockAndFirstTransferInOrGrn(
                $inventoryUpdate['product_id'],
                $inventoryUpdate['location_id']
            );

            $productAgeingQueries->update(
                $getUpdateProductAgeReportData,
                $inventoryUpdate['location_id'],
                $inventoryUpdate['product_id']
            );
        }
    }

    public function checkIfAnyProductIsCreated(): void
    {
        $productQueries = resolve(ProductQueries::class);
        $productIds = $productQueries->getYesterdayCreatedProductsIds();

        if ([] === $productIds) {
            return;
        }

        $locationQueries = resolve(LocationQueries::class);
        $locationIds = $locationQueries->getAllLocationsIds(LocationTypes::STORE->value);

        $productAgeingQueries = resolve(ProductAgeingQueries::class);

        foreach ($productIds as $product) {
            /** @var Carbon $productCreatedAt */
            $productCreatedAt = $product['created_at'];

            if (null !== $product['original_created_at']) {
                /** @var Carbon $productCreatedAt */
                $productCreatedAt = Carbon::createFromFormat('Y-m-d H:i:s', $product['original_created_at']);
            }

            foreach ($locationIds as $locationId) {
                $productDataWithProductAgeingRelatedColumns = $this->getWithStockAndFirstTransferInOrGrn(
                    $product['id'],
                    $locationId
                );

                $productAgeingQueries->addNew(
                    $productDataWithProductAgeingRelatedColumns,
                    $locationId,
                    $product['id'],
                    $productCreatedAt->format('Y-m-d H:i:s')
                );
            }
        }
    }

    public function checkIfAnyStoreIsCreated(): void
    {
        $locationQueries = resolve(LocationQueries::class);
        $locationIds = $locationQueries->getYesterdayCreatedLocationsIds(LocationTypes::STORE->value);

        if ([] === $locationIds) {
            return;
        }

        $productQueries = resolve(ProductQueries::class);
        $allActiveProducts = $productQueries->getAllActiveProductsIds();

        $productAgeingQueries = resolve(ProductAgeingQueries::class);

        foreach ($allActiveProducts as $allActiveProduct) {
            $productCreatedAt = $allActiveProduct['created_at'];

            if (null !== $allActiveProduct['original_created_at']) {
                /** @var Carbon $productCreatedAt */
                $productCreatedAt = Carbon::createFromFormat('Y-m-d H:i:s', $allActiveProduct['original_created_at']);
            }

            foreach ($locationIds as $locationId) {
                $productDataWithProductAgeingRelatedColumns = $this->getWithStockAndFirstTransferInOrGrn(
                    $allActiveProduct['id'],
                    $locationId
                );
                $productAgeingQueries->addNew(
                    $productDataWithProductAgeingRelatedColumns,
                    $locationId,
                    $allActiveProduct['id'],
                    $productCreatedAt->format('Y-m-d H:i:s')
                );
            }
        }
    }

    private function getWithStockAndFirstTransferInOrGrn(int $productId, int $locationId): array
    {
        $inventoryQueries = resolve(InventoryQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $product = $productQueries->getByIdWithOriginalCreatedAt($productId);
        $productAgeing = $productAgeingQueries->getDetailsByProductIdAndLocationId($productId, $locationId);

        $stock = $inventoryQueries->getInventoryByProductAndLocationWithReservedStock($productId, $locationId);

        $firstGoodsReceivedNote = null;
        $firstTransferIn = null;

        [$inventoryUpdateOfGoodsReceiveNote, $inventoryUpdateOfStockTransferItem] = $this->getFirstGoodsReceiveNoteAndFirstStockTransferInDate(
            $productAgeing,
            $productId,
            $locationId
        );

        if ($inventoryUpdateOfGoodsReceiveNote) {
            $firstGoodsReceivedNote = $inventoryUpdateOfGoodsReceiveNote;
        }

        if ($inventoryUpdateOfStockTransferItem) {
            $firstTransferIn = $inventoryUpdateOfStockTransferItem;
        }

        $saleItemQueries = resolve(SaleItemQueries::class);
        $results = $saleItemQueries->getSaleItemsForTheProductAgeingReport($productId, $locationId);

        $formattedData = [
            'last_selling_date' => $results->max('last_selling_date'),
            'quantity_sold' => $results->sum('quantity'),
            'quantity_remaining' => $stock,
            'first_month_sold' => $results->sum('first_month_quantity_sold'),
            'second_month_sold' => $results->sum('second_month_quantity_sold'),
            'third_month_sold' => $results->sum('third_month_quantity_sold'),
            'fourth_month_sold' => $results->sum('fourth_month_quantity_sold'),
            'fifth_month_sold' => $results->sum('fifth_month_quantity_sold'),
            'sixth_month_sold' => $results->sum('sixth_month_quantity_sold'),
            'seventh_month_sold' => $results->sum('seventh_month_quantity_sold'),
            'eighth_month_sold' => $results->sum('eighth_month_quantity_sold'),
            'ninth_month_sold' => $results->sum('ninth_month_quantity_sold'),
            'tenth_month_sold' => $results->sum('tenth_month_quantity_sold'),
            'eleventh_month_sold' => $results->sum('eleventh_month_quantity_sold'),
            'twelfth_month_sold' => $results->sum('twelfth_month_quantity_sold'),
            'first_transfer_in' => $firstTransferIn,
            'first_goods_received_note' => $firstGoodsReceivedNote,
        ];

        if ($product && $product->original_created_at) {
            $formattedData['product_created_at'] = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $product->original_created_at
            );
        }

        return $formattedData;
    }

    private function getFirstGoodsReceiveNoteAndFirstStockTransferInDate(
        ?ProductAgeing $productAgeing,
        int $productId,
        int $locationId
    ): array {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        if ($productAgeing instanceof ProductAgeing) {
            $firstGoodsReceiveNote = $productAgeing->first_goods_received_note;
            $firstStockTransferIn = $productAgeing->first_transfer_in;

            if (null !== $firstGoodsReceiveNote && null !== $firstStockTransferIn) {
                return [$firstGoodsReceiveNote, $firstStockTransferIn];
            }
        }

        $firstGoodsReceiveNote ??=
            $inventoryUpdateQueries->getByLocationAndProductId(
                $productId,
                $locationId,
                ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
            )?->happened_at;

        $firstStockTransferIn ??=
            $inventoryUpdateQueries->getByLocationAndProductId(
                $productId,
                $locationId,
                ModelMapping::STOCK_TRANSFER_ITEM->name
            )?->happened_at;

        return [$firstGoodsReceiveNote, $firstStockTransferIn];
    }
}
