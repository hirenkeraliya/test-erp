<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Http\Controllers\Admin\ImportRecordController;
use App\Models\Admin;
use App\Models\Company;
use App\Models\ImportRecord;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the importRecord queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => '100',
        'sort_by' => 'records_imported',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'import_record_id' => null,
        'status' => null,
        'import_type' => null,
        'date_range' => null,
    ];

    $importRecordQueries = $this->mock(ImportRecordQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $importRecordController = new ImportRecordController($importRecordQueries);
    $response = $importRecordController->fetchImportRecords(new Request($requestParameter));
    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test('It calls the addNew method of the importRecord queries class and returns proper response', function (): void {
    Queue::fake();

    $companyId = 1;
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $importRecord = [
        'type_id' => 1,
        'upload_file' => $uploadedFile,
    ];

    $importRecordData = new ImportRecordData(...$importRecord);

    setCompanyIdInSession($companyId);

    $this->mock(ImportRecordService::class, function ($mock): void {
        $mock->shouldReceive('validateColumns')
            ->once();
    });

    $admin = new Admin([
        'employee_id' => 1,
    ]);

    $admin->roles = collect([]);

    $importRecordQueries = $this->mock(ImportRecordQueries::class, function ($mock) use (
        $importRecordData,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($importRecordData, $admin, 1, null)
            ->andReturn(new ImportRecord());
    });

    $importRecordController = new ImportRecordController($importRecordQueries);
    $request = new Request();
    $admin->id = 1;
    $request->setUserResolver(fn (): Admin => $admin);

    $redirectResponse = $importRecordController->store($importRecordData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'File uploaded successfully. The import process will occur in the background. We will notify you by email once the import is complete.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/import-records', $redirectResponse->getTargetUrl());
    Queue::assertPushed(ImportRecordsJob::class);
});

test(
    'It calls the create method and returns proper response',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
            'default_country_id' => 1,
        ]);

        setCompanyIdInSession($company->id);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->with($company->id)
                ->once()
                ->andReturn($company);
        });

        $importRecordController = new ImportRecordController(new ImportRecordQueries());
        $response = $importRecordController->create();
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
            ->has(
                'groupImportTypes',
                fn (Assert $importTypes): Assert => $importTypes
                ->has('0', fn (Assert $importType): Assert => $importType->where('id', 1)->where('name', 'Products'))
                ->etc()
            )
            ->has(
                'importTypeDetails',
                fn (Assert $importTypeDetails): Assert => $importTypeDetails->where('products', 1)
                ->etc()
            )
        );
    }
);

test('It calls the exportImportRecords method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => '100',
        'sort_by' => 'records_imported',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'import_record_id' => null,
        'status' => null,
        'import_type' => null,
        'date_range' => null,
    ];

    $importRecordQueries = $this->mock(ImportRecordQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getImportRecordExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new ImportRecord()));
    });

    $importRecordController = new ImportRecordController($importRecordQueries);

    $response = $importRecordController->exportImportRecords('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the getPendingImportRecordCount method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $moduleType = ModelMapping::STOCK_ADJUSTMENT->name;

    $importRecordQueries = $this->mock(ImportRecordQueries::class, function ($mock) use (
        $companyId,
        $moduleType
    ): void {
        $mock->shouldReceive('getPendingImportRecordCount')
            ->once()
            ->with($moduleType, $companyId)
            ->andReturn(1);
    });

    $importRecordController = new ImportRecordController($importRecordQueries);

    $response = $importRecordController->getPendingImportRecordCount($moduleType);

    $this->assertEquals(1, $response['pending_counts']);
});

test('It calls the exportProductPriceUpdate method and returns a proper response', function (): void {
    $companyId = 1;

    $admin = new Admin([
        'employee_id' => 1,
    ]);
    $admin->roles = collect([]);
    $request = new Request();
    $request->setUserResolver(fn (): Admin => $admin);

    setCompanyIdInSession($companyId);

    $importRecordController = new ImportRecordController(new ImportRecordQueries());

    $response = $importRecordController->exportProductPriceUpdate($request, 'filename.csv');

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
