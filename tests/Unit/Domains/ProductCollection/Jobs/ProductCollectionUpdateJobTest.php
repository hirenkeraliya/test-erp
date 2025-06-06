<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Support\Facades\Queue;

test(
    'ProductCollectionUpdateJob Calls then update the product collection product table',
    function (): void {
        Queue::fake();

        $companyId = 1;
        $productId = 1;

        $productCollection = ProductCollection::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'test',
            'number_of_products' => 0,
            'pending_products' => 0,
            'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
            'status' => true,
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_by_id' => 1,
        ]);

        $this->mock(ProductCollectionQueries::class, function ($mock) use ($productCollection): void {
            $mock->shouldReceive('edit')
                ->andReturn($productCollection);
            $mock->shouldReceive('getProductByProductCollectionAndCompany')
                ->andReturn(new Product());
        });

        $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithAutoIncludeInCollectionsById')
                ->andReturn(true);
        });

        $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        ProductCollectionUpdateJob::dispatch($productCollection->id, $productId, $companyId)->onQueue(
            config('horizon.default_queue_name')
        );
        Queue::assertPushed(ProductCollectionUpdateJob::class);
    }
);
