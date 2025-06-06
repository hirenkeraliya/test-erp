<?php

declare(strict_types=1);

use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Http\Controllers\Admin\StockMovementLedgerReportController;
use App\Models\InventoryUpdate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated stock movements of a product for a location method of the inventory update queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'location_ids' => ['test'],
            'product_id' => 'test',
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $saleQueries = $this->mock(InventoryUpdateQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedStockMovementsOfAProductForALocation')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $stockMovementLedgerReportController = new StockMovementLedgerReportController($saleQueries);

        $response = $stockMovementLedgerReportController->fetchStockMovementLedgerReport(
            new Request($requestParameter)
        );

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportStockMovementLedger method and returns a proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'location_ids' => ['test'],
            'product_id' => 'test',
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'export_columns' => null,
        ];

        $saleQueries = $this->mock(InventoryUpdateQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getStockMovementsOfAProductForALocationForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new InventoryUpdate()));
        });

        $stockMovementLedgerReportController = new StockMovementLedgerReportController($saleQueries);

        $response = $stockMovementLedgerReportController->exportStockMovementLedger(
            'filename.csv',
            new Request($requestParameter)
        );

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
