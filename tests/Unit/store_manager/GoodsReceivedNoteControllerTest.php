<?php

declare(strict_types=1);

use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteFileData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteUpdateStatusData;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteCheckRequestService;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Http\Controllers\StoreManager\GoodsReceivedNoteController;
use App\Models\GoodsReceivedNote;
use App\Models\ImportRecord;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the listQueryForStoreManager method of the goods received note queries class and returns proper response',
    function (): void {
        $locationId = 1;

        setStoreManagerStoreIdInSession($locationId);
        setStoreManagerStoreCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'grn_number' => null,
        ];

        $goodsReceivedNoteQueries = $this->mock(GoodsReceivedNoteQueries::class, function ($mock) use (
            $requestParameter
        ): void {
            $mock->shouldReceive('listQueryForStoreManager')
                ->once()
                ->with($requestParameter, 1, 1)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $goodsReceivedNoteController = new GoodsReceivedNoteController($goodsReceivedNoteQueries);

        $response = $goodsReceivedNoteController->fetchGoodsReceivedNotes(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals([], $response['data']->toArray());
    }
);

test('It calls the store method and returns a proper response', function (): void {
    Queue::fake();
    setStoreManagerStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $goodsReceivedNoteProducts = [
        'purchase_order_reference' => 'do',
        'delivery_order_reference' => 'po',
        'notes' => 'test_notes',
        'vendor_id' => 1,
        'location_id' => 1,
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            false
        ),
    ];

    $request = new Request();

    $user = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $user);

    $goodsReceivedNoteData = new GoodsReceivedNoteData(...$goodsReceivedNoteProducts);
    $goodsReceivedNoteQueries = new GoodsReceivedNoteQueries();

    $this->mock(GoodsReceivedNoteService::class, function ($mock) use ($goodsReceivedNoteQueries): void {
        $mock->shouldReceive('generateGrnReference')
            ->once()
            ->with($goodsReceivedNoteQueries, 1)
            ->andReturn(1);
    });

    $this->mock(GoodsReceivedNoteQueries::class, function ($mock) use ($goodsReceivedNoteData, $user): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($goodsReceivedNoteData, 1, 1, $user)
            ->andReturn(new GoodsReceivedNote());
    });

    $this->mock(GoodsReceivedNoteCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('validateGrnReference')
            ->once();
    });

    $this->mock(ImportRecordQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(new ImportRecord());
    });

    $goodsReceivedNoteController = new GoodsReceivedNoteController($goodsReceivedNoteQueries);
    $redirectResponse = $goodsReceivedNoteController->store($goodsReceivedNoteData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The Goods Received Note has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('store-manager/goods-received-notes', $redirectResponse->getTargetUrl());

    Queue::assertPushed(ImportRecordsJob::class);
});

test('It calls the getGoodsReceivedNoteProducts method and returns a proper response', function (): void {
    setStoreManagerStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

    $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getByGrnId')
            ->once()
            ->with(1, 1)
            ->andReturn(new Collection([]));
    });

    $goodsReceivedNoteController = new GoodsReceivedNoteController($goodsReceivedNoteQueries);

    $response = $goodsReceivedNoteController->getGoodsReceivedNoteProducts(1);
    $this->assertEquals(new Collection([]), $response['data']->resource);
});

test('It calls the getGoodeReceiveNotesExportForStoreManager method and returns a proper response', function (): void {
    $companyId = 1;
    $locationId = 1;

    setStoreManagerStoreIdInSession($locationId);
    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'grn_number' => null,
    ];

    $goodsReceivedNoteQueries = $this->mock(GoodsReceivedNoteQueries::class, function ($mock) use (
        $requestParameter,
        $companyId,
        $locationId
    ): void {
        $mock->shouldReceive('getGoodeReceiveNotesExportForStoreManager')
            ->once()
            ->with($requestParameter, $companyId, $locationId)
            ->andReturn(collect(new GoodsReceivedNote()));
    });

    $goodsReceivedNoteController = new GoodsReceivedNoteController($goodsReceivedNoteQueries);

    $response = $goodsReceivedNoteController->exportGoodReceivedNote('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportGoodReceivedNoteProducts method and returns a proper response', function (): void {
    setStoreManagerStoreCompanyIdInSession();

    $goodsReceivedNoteQueries = new GoodsReceivedNoteQueries();

    $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getByGrnId')
            ->once()
            ->with(1, 1)
            ->andReturn(new Collection([]));
    });

    $goodsReceivedNoteController = new GoodsReceivedNoteController($goodsReceivedNoteQueries);
    $response = $goodsReceivedNoteController->exportGoodReceivedNoteProducts(1, 'filename.csv');
    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the reUploadFailedRecord method and returns a proper response', function (): void {
    Queue::fake();
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
    ];

    $request = new Request($requestParameter);

    $request->setUserResolver(fn (): StoreManager => new StoreManager());

    $goodsReceivedNote = GoodsReceivedNote::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'vendor_id' => 1,
    ]);

    $goodsReceivedNoteFileData = new GoodsReceivedNoteFileData(...[
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            false
        ),
    ]);

    $goodsReceivedNoteQueries = $this->mock(GoodsReceivedNoteQueries::class, function ($mock) use (
        $goodsReceivedNote
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($goodsReceivedNote);
    });

    $this->mock(ImportRecordQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(new ImportRecord());
    });

    $this->mock(ImportRecordService::class, function ($mock): void {
        $mock->shouldReceive('validateColumns')
            ->once();
    });

    $goodsReceivedNoteController = new GoodsReceivedNoteController($goodsReceivedNoteQueries);

    $goodsReceivedNoteController->reUploadFailedRecord($goodsReceivedNoteFileData, $goodsReceivedNote->id, $request);

    Queue::assertPushed(ImportRecordsJob::class);
});

test('It calls the markAsCancel method and cancel the GRN and', function (): void {
    $companyId = 1;
    $locationId = 1;

    setStoreManagerStoreIdInSession($locationId);
    setStoreManagerStoreCompanyIdInSession($companyId);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $goodsReceiveNote = GoodsReceivedNote::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'vendor_id' => 1,
        'location_id' => $locationId,
    ]);

    $goodsReceiveNote->importRecord = new ImportRecord([
        'status' => Status::COMPLETED->value,
    ]);

    $goodsReceivedNoteUpdateStatusData = new GoodsReceivedNoteUpdateStatusData(...[
        'remarks' => 'remark added',
    ]);

    $goodsReceivedNoteQueries = $this->mock(GoodsReceivedNoteQueries::class, function ($mock) use (
        $goodsReceiveNote
    ): void {
        $mock->shouldReceive('getByIdWithSerialNumberRelation')
            ->once()
            ->andReturn($goodsReceiveNote);
    });

    $this->mock(GoodsReceivedNoteService::class, function ($mock): void {
        $mock->shouldReceive('rollbackInventory')
            ->once();
        $mock->shouldReceive('checkGoodReceivedNoteProduct')
            ->once();
        $mock->shouldReceive('markAsDeleteStatus')
        ->once();
    });

    $goodsReceivedNoteController = new GoodsReceivedNoteController($goodsReceivedNoteQueries);

    $goodsReceivedNoteController->markAsCancel($request, $goodsReceivedNoteUpdateStatusData, $goodsReceiveNote->id);
});
