<?php

declare(strict_types=1);

use App\Domains\ExportRecord\ExportRecordQueries;
use App\Http\Controllers\StoreManager\ExportRecordController;
use App\Models\ExportRecord;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the exportRecord queries class and returns proper response', function (): void {
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession();

    $requestParameter = [
        'search_text' => '100',
        'sort_by' => 'records_imported',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'export_record_id' => null,
        'status' => null,
        'export_type' => null,
        'date_range' => null,
    ];

    $exportRecordQueries = $this->mock(ExportRecordQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQueryForStoreManager')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $exportRecordController = new ExportRecordController($exportRecordQueries);
    $response = $exportRecordController->fetchExportRecords(new Request($requestParameter));
    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test('It calls the exportRecords method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => '100',
        'sort_by' => 'records_imported',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'export_record_id' => null,
        'status' => null,
        'export_type' => null,
        'date_range' => null,
    ];

    $exportRecordQueries = $this->mock(ExportRecordQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('exportListQueryForStoreManager')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new ExportRecord()));
    });

    $exportRecordController = new ExportRecordController($exportRecordQueries);

    $response = $exportRecordController->exportRecords('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
