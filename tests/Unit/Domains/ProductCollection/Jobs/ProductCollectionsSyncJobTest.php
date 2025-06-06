<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Models\ImportRecord;
use App\Models\ProductCollection;
use Illuminate\Support\Facades\Queue;

test(
    'ProductCollectionsSyncJob Calls then update the product collection product table',
    function (): void {
        Queue::fake();

        $companyId = 1;

        $importRecord = ImportRecord::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => ImportTypes::PRODUCT_COLLECTION->value,
            'created_by_id' => 1,
            'created_by_type' => ModelMapping::ADMIN->name,
        ]);

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

        $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
            $mock->shouldReceive('syncByProductCollectionId')
                ->andReturn(collect([]));
        });

        $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLastSyncById')
                ->andReturn(collect([]));
        });

        $this->mock(ImportRecordQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsInProgress');
            $mock->shouldReceive('markAsCompleted');
            $mock->shouldReceive('getById');
        });

        ProductCollectionsSyncJob::dispatch(
            $productCollection->id,
            $importRecord->company_id,
            $importRecord->id
        )->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(ProductCollectionsSyncJob::class);
    }
);
