<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Http\Controllers\WarehouseManager\BatchExpiryController;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the batchExpiryReportList method of the batch queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setWarehouseManagerWarehouseCompanyIdInSession($companyId);
        setWarehouseManagerWarehouseIdInSession(1);

        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'category_id' => null,
            'brand_id' => null,
            'location_id' => 1,
            'tag_ids' => null,
            'date_range' => null,
        ];

        $this->mock(BatchQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('batchExpiryReportList')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $batchExpiryReportController = new BatchExpiryController();
        $response = $batchExpiryReportController->fetchBatchExpiry(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource->toArray())->toBeArray();
    }
);

test(
    'It calls the batchExpiryReportForExport method of the batch queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setWarehouseManagerWarehouseCompanyIdInSession();
        setWarehouseManagerWarehouseIdInSession(1);

        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'category_id' => null,
            'brand_id' => null,
            'location_id' => 1,
            'tag_ids' => null,
            'date_range' => null,
            'export_columns' => null,
        ];

        $this->mock(BatchQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('batchExpiryReportForExport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new Collection());
        });

        $batchExpiryReportController = new BatchExpiryController();
        $response = $batchExpiryReportController->exportBatchExpiry('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
