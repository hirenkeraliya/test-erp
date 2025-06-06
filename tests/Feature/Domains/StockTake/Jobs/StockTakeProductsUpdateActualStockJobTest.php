<?php

declare(strict_types=1);

use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockTakeProduct\Jobs\StockTakeProductsUpdateActualStockJob;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Models\Product;
use Carbon\Carbon;

test(
    'StockTakeProductsUpdateActualStockJob job calls respective methods and update closing stock as expected',
    function (): void {
        $product = Product::factory()->make([
            'id' => 1,
        ]);

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductActualStock')
                ->once();
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getStockByStoreIdProductIdsAndDate')
                ->once()
                ->andReturn(collect([$product]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        StockTakeProductsUpdateActualStockJob::dispatch(Carbon::now()->format('Y-m-d'), 1, [$product->id], 1)->onQueue(
            config('horizon.default_queue_name')
        );
    }
);
