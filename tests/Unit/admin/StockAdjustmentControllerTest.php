<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\StockAdjustment\DataObjects\StockAdjustmentData;
use App\Domains\StockAdjustment\DataObjects\StockAdjustmentFileData;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Models\Admin;
use App\Models\ImportRecord;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the stock adjustment queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'stock_adjustment_id' => null,
        ];

        $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);

        $response = $stockAdjustmentController->fetchStockAdjustments(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the addNew method of the stock adjustment queries and returns proper response', function (): void {
    Queue::fake();
    setCompanyIdInSession();

    $stockAdjustment = [
        'reason' => 'reason',
        'approved_by_employee_id' => 1,
        'adjustment_date' => now()->format('Y-m-d'),
        'type_id' => 1,
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            false
        ),
    ];

    $stockAdjustmentData = new StockAdjustmentData(...$stockAdjustment);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => new Admin());

    $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
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

    $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);
    $redirectResponse = $stockAdjustmentController->store($stockAdjustmentData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Stock Adjustment added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/stock-adjustment', $redirectResponse->getTargetUrl());

    Queue::assertPushed(ImportRecordsJob::class);
});

test('It calls the create method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $employeeRecord = [[
        'id' => '1',
        'name' => 'ABC',
    ]];

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employeeRecord): void {
        $mock->shouldReceive('getFormattedEmployeesOf')
            ->once()
            ->with(1)
            ->andReturn(new Collection($employeeRecord));
    });

    $stockAdjustmentController = new StockAdjustmentController(new StockAdjustmentQueries());
    $response = $stockAdjustmentController->create($employeeQueries);
    $response->rootView('super_admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'stockAdjustmentTypes',
            fn (Assert $stockAdjustmentTypes): Assert => $stockAdjustmentTypes
            ->has('0', fn (Assert $stockAdjustmentType): Assert => $stockAdjustmentType->where('name', 'STI')->etc())
            ->etc()
        )
        ->has(
            'employees',
            fn (Assert $employees): Assert => $employees
            ->has('0', fn (Assert $employee): Assert => $employee->where('id', '1')->where('name', 'ABC'))
        ),
    );
});

test('It calls the getStockAdjustmentItems method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $this->mock(StockAdjustmentItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getItemsByStockAdjustmentId')
            ->once()
            ->with(1, 1)
            ->andReturn(new Collection([]));
    });

    $stockAdjustmentController = new StockAdjustmentController(new StockAdjustmentQueries());
    $response = $stockAdjustmentController->getStockAdjustmentItems(1);
    $this->assertEquals(new Collection([]), $response['data']->resource);
});

test('It calls the exportItems method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithItems')
            ->once()
            ->with(1, 1)
            ->andReturn(new StockAdjustment());
    });

    $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);

    $response = $stockAdjustmentController->exportItems(1, 'filename.csv');

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportStockAdjustments method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'stock_adjustment_id' => null,
    ];

    $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getStockAdjustmentsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new StockAdjustment()));
    });

    $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);

    $response = $stockAdjustmentController->exportStockAdjustments('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the reUploadFailedRecord method and returns a proper response', function (): void {
    Queue::fake();
    $companyId = 1;

    setCompanyIdInSession($companyId);

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

    $request->setUserResolver(fn (): Admin => new Admin());

    $stockAdjustment = StockAdjustment::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_by_admin_id' => 1,
        'approved_by_employee_id' => 1,
    ]);

    $stockAdjustmentFileData = new StockAdjustmentFileData(...[
        'uploaded_file' => new UploadedFile(
            public_path('files/stock-adjustments-sample-file-stock-in.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            false
        ),
    ]);

    $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock) use (
        $stockAdjustment
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($stockAdjustment);
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

    $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);

    $stockAdjustmentController->reUploadFailedRecord($stockAdjustmentFileData, $stockAdjustment->id, $request);

    Queue::assertPushed(ImportRecordsJob::class);
});
