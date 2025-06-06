<?php

declare(strict_types=1);

use App\Domains\ReservedStock\ReservedStockQueries;
use App\Http\Controllers\WarehouseManager\ReservedInventoryReportController;
use App\Models\ReservedStock;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated reserved inventory for a location method of the reserved stock queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $requestParameter = [
            'location_id' => 'test',
            'product_id' => 'test',
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_collection_id' => null,
        ];

        $saleQueries = $this->mock(ReservedStockQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedReservedInventoryForLocation')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));

            $mock->shouldReceive('getConsolidatedData')
            ->once();
        });

        $stockMovementLedgerReportController = new ReservedInventoryReportController($saleQueries);

        $response = $stockMovementLedgerReportController->fetchReservedInventoryReport(
            new Request($requestParameter)
        );

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportReservedInventory method and returns a proper response',
    function (): void {
        $companyId = 1;
        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $requestParameter = [
            'location_id' => 'test',
            'product_id' => 'test',
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'product_collection_id' => null,
            'export_columns' => null,
        ];

        $saleQueries = $this->mock(ReservedStockQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getReservedInventoryLocationForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new ReservedStock()));
        });

        $stockMovementLedgerReportController = new ReservedInventoryReportController($saleQueries);

        $response = $stockMovementLedgerReportController->exportReservedInventory(
            'filename.csv',
            new Request($requestParameter)
        );

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
