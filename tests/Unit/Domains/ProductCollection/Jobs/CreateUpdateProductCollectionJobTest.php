<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Models\Company;
use App\Models\ImportRecord;
use App\Models\ProductCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

test(
    'CreateUpdateProductCollectionJob Calls then update the product collection product table',
    function (): void {
        Queue::fake();

        $companyId = 1;
        $productCollectionId = 1;
        $startIndex = 0;
        $endIndex = 4;

        $importRecord = ImportRecord::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => ImportTypes::PRODUCT_COLLECTION->value,
            'created_by_id' => 1,
            'created_by_type' => ModelMapping::ADMIN->name,
        ]);

        $products = [(object) [
            'id' => 1,
        ], (object) [
            'id' => 2,
        ], (object) [
            'id' => 3,
        ], (object) [
            'id' => 4,
        ], (object) [
            'id' => 5,
        ]];

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithAutoIncludeInCollectionsById')
                ->andReturn(new Company());
        });

        $this->mock(ProductCollectionQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('edit')
                ->andReturn(new ProductCollection());

            $mock->shouldReceive('getMatchProducts')
                ->andReturn(collect($products));
        });

        $this->mock(ImportRecordService::class, function ($mock): void {
            $mock->shouldReceive('getJobRestartTime')
                ->andReturn(new Carbon());
            $mock->shouldReceive('isThisFirstImportCycle')
                ->andReturn(true);
            $mock->shouldReceive('jobIsReadyToExpire')
                ->andReturn(false);
            $mock->shouldReceive('hasMoreRecords')
                ->andReturn(false);
        });

        $this->mock(ImportRecordQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsInProgress');
            $mock->shouldReceive('markAsCompleted');
        });

        $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        CreateUpdateProductCollectionJob::dispatch(
            $productCollectionId,
            $companyId,
            $importRecord->id,
            $startIndex,
            $endIndex
        )->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(CreateUpdateProductCollectionJob::class);
    }
);
