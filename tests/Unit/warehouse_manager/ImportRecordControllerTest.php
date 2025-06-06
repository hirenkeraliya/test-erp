<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Http\Controllers\WarehouseManager\ImportRecordController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'It calls the listQueryForWarehouseManager method of the importRecord queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setWarehouseManagerWarehouseCompanyIdInSession();

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
            $mock->shouldReceive('listQueryForWarehouseManager')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $importRecordController = new ImportRecordController($importRecordQueries);
        $response = $importRecordController->fetchImportRecords(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the getPendingImportRecordCount method and returns a proper response', function (): void {
    $companyId = 1;

    setWarehouseManagerWarehouseCompanyIdInSession($companyId);

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
