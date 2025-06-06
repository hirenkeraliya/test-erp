<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\ProductCollection\ProductCollectionQueries;
use Illuminate\Support\Facades\Queue;

test(
    'ProductCollectionsSyncJob Calls then update the product collection product table',
    function (): void {
        Queue::fake();

        $companyId = 1;
        $productId = 1;

        $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductCollections')
                ->andReturn(collect([]));
        });

        ProductCollectionUpdateByProductJob::dispatch($productId, $companyId)->onQueue(
            config('horizon.default_queue_name')
        );
        Queue::assertPushed(ProductCollectionUpdateByProductJob::class);
    }
);
