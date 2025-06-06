<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\StockTake\DataObjects\StockTakesBulkData;
use App\Domains\StockTake\Jobs\StockTakeJob;
use App\Domains\StockTake\StockTakeQueries;
use App\Domains\StockTakeProduct\Jobs\StockTakeProductsUpdateActualStockJob;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Http\Controllers\StoreManager\StockTakeController;
use App\Models\ImportRecord;
use App\Models\StockTake;
use App\Models\StockTakeProduct;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('It calls the listQuery method of the stockTakeQueries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
    ];

    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $request = new Request($requestParameter);

    $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $stockTakeController = new StockTakeController($stockTakeQueries);

    $response = $stockTakeController->fetchStockTake($request);

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test('It calls the addNew method of the StockTakeProductQueries class and returns proper response', function (): void {
    Queue::fake();

    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $request = new Request([
        'stock_record_date' => Carbon::now()->format('Y-m-d'),
        'notes' => 'test',
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $stockTake = StockTake::factory()->make([
        'id' => 1,
        'requested_by_id' => 1,
        'location_id' => 1,
        'company_id' => 1,
    ]);

    $importRecord = ImportRecord::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => ImportTypes::STOCK_TAKES->value,
        'created_by_id' => $storeManager->id,
        'created_by_type' => ModelMapping::getCaseName($storeManager::class),
        'module_id' => $stockTake->id,
        'module_type' => ModelMapping::getCaseName($stockTake::class),
    ]);

    $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
        $mock->shouldReceive('addNewForStockTake')
            ->once()
            ->andReturn($importRecord);
    });

    $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock) use ($stockTake): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($stockTake);
        $mock->shouldReceive('anyPendingStockTakeByManager')
            ->once()
            ->andReturn(false);
    });

    $stockTakeController = new StockTakeController($stockTakeQueries);

    $response = $stockTakeController->addStockTake($request);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals(
        'The process of adding stock products will be happening in the background. We will show it soon.',
        $response->getSession()->all()['success']
    );

    Queue::assertPushed(StockTakeJob::class);
});

test(
    'addStockTake method throw exception if pending to submit stock take by store manager id & location id',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $request = new Request([
            'stock_record_date' => Carbon::now()->format('Y-m-d'),
            'notes' => 'test',
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): StoreManager => $storeManager);

        StockTake::factory()->make([
            'id' => 1,
            'requested_by_id' => 1,
            'company_id' => 1,
            'requested_by_type' => ModelMapping::STORE_MANAGER->name,
            'location_id' => 1,
            'submitted_by_id' => 1,
            'submitted_by_type' => ModelMapping::STORE_MANAGER->name,
            'submitted_at' => Carbon::now(),
        ]);

        $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock): void {
            $mock->shouldReceive('anyPendingStockTakeByManager')
            ->once()
            ->andReturn(true);
        });

        $stockTakeController = new StockTakeController($stockTakeQueries);

        $stockTakeController->addStockTake($request);
    }
)->throws(HttpException::class, 'A stock take is already pending for the selected location. Please complete it first.');

test(
    'It calls the getLists method of the StockTakeProductQueries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 10,
            'page' => 1,
        ];

        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $request = new Request($requestParameter);

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getLists')
                ->once()
                ->andReturn(collect([]));
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $response = $stockTakeController->fetchStockTakeProducts($request, 1);

        $this->assertEquals([], $response['data']);
    }
);

test(
    'it calls getLists method in StockTakeProductQueries and returns response with grandTotal',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getLists')
                ->once()
                ->andReturn(collect([]));
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $response = $stockTakeController->grandTotalSubmittedStock(1);

        $this->assertArrayHasKey('grandTotal', $response);
    }
);

test(
    'It calls the updateSubmittedStock method of the StockTakeProductQueries class and returns proper response',
    function (): void {
        $stockTakeProduct = StockTakeProduct::factory()->make([
            'id' => 1,
            'stock_take_id' => 1,
            'product_id' => 1,
        ]);

        $requestParameter = [
            'stock_take_product_id' => $stockTakeProduct->id,
            'product_id' => $stockTakeProduct->product_id,
            'submitted_stock' => 1,
        ];

        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $request = new Request($requestParameter);

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateSubmittedStock')
                ->once();
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $stockTakeController->updateSubmittedStock($request, 1);
    }
);

test('It calls the submit method of the StockTakeQueries class and returns proper response', function (): void {
    Queue::fake();

    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $request = new Request([
        'compare_stock_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $product = commonGetProductDetails(false);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock): void {
        $mock->shouldReceive('submit')
            ->once();
    });

    $this->mock(StockTakeProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getProductIdsByStockTakeId')
            ->once()
            ->andReturn([$product->id]);
    });

    $stockTakeController = new StockTakeController($stockTakeQueries);

    $redirectResponse = $stockTakeController->submitStockTake($request, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The stock take has been submitted successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('store-manager/stock-takes', $redirectResponse->getTargetUrl());

    Queue::assertPushed(StockTakeProductsUpdateActualStockJob::class);
});

test(
    'It calls the getProductsOfSubmittedStockTake method of the StockTakeProductQueries class and returns proper response',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductsOfSubmittedStockTake')
                ->once()
                ->andReturn(new Collection([]));
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $response = $stockTakeController->exportStockTakeProducts(1, 'file.csv');

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the downloadStockTakeProducts method of the StockTakeProductQueries class and returns proper response',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $request = new Request();
        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('downloadStockTakeProducts')
                ->once()
                ->andReturn(new Collection([]));
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $response = $stockTakeController->downloadStockTakeProducts(1, 'file.csv', $request);

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the bulkUpdateSubmitStock method of the StockTakeProductQueries class and returns proper response',
    function (): void {
        Queue::fake();

        setStoreManagerStoreCompanyIdInSession();

        Storage::fake('public');

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $uploadedFile = UploadedFile::fake()->create('import.xlsx');

        $stockTake = StockTake::factory()->make([
            'id' => 1,
            'requested_by_id' => 1,
            'company_id' => 1,
            'requested_by_type' => ModelMapping::STORE_MANAGER->name,
            'location_id' => 1,
            'submitted_by_id' => 1,
            'submitted_by_type' => ModelMapping::STORE_MANAGER->name,
            'submitted_at' => Carbon::now(),
            'stock_record_date' => Carbon::now(),
        ]);

        $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock) use ($stockTake): void {
            $mock->shouldReceive('getById')
            ->once()
                ->andReturn($stockTake);
        });

        $bulkData = new StockTakesBulkData($uploadedFile);

        $this->mock(ImportRecordQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn(new ImportRecord());
        });

        $this->mock(ImportRecordService::class, function ($mock): void {
            $mock->shouldReceive('validateColumns')
                ->once();
        });

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $storeManager);

        $stockTakeController = new StockTakeController($stockTakeQueries);
        $response = $stockTakeController->bulkUpdateStocks($request, $bulkData, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Bulk update for submitted stock performed successfully.',
            $response->getSession()->all()['success']
        );

        Queue::assertPushed(ImportRecordsJob::class);
    }
);

test(
    'It calls the getPendingStockProductsSubmissionCount method of the StockTakeProductQueries class and returns count of pending submitted stock',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getPendingStockProductsSubmissionCount')
                ->once()
                ->andReturn(1);
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $response = $stockTakeController->getPendingStockProductsSubmissionCount(1);
        $this->expect($response)->toHaveKey('pending_stock_products_submission_count');
    }
);

test('It calls the exportStockTakes method and returns a proper response', function (): void {
    $companyId = 1;
    $locationId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);
    setStoreManagerStoreIdInSession($locationId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreAndWarehouseMangerStockTakesExport')
            ->once()
            ->andReturn(collect([]));
    });

    $stockTakeController = new StockTakeController($stockTakeQueries);

    $response = $stockTakeController->exportStockTakes('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the updateSubmittedStockByStockId method returns proper response',
    function (): void {
        $stockTakeProduct = StockTakeProduct::factory()->make([
            'id' => 1,
            'stock_take_id' => 1,
            'product_id' => 1,
        ]);

        $requestParameter = [
            'products' => [
                [
                    'product_id' => $stockTakeProduct->id,
                    'submitted_stock' => 1,
                ],
            ],
        ];

        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $request = new Request($requestParameter);

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateSubmittedStockByStockId')
                ->once();
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $stockTakeController->updateSubmittedStockByStockId($request, 1);
    }
);
