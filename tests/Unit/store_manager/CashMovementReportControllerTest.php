<?php

declare(strict_types=1);

use App\Domains\CashMovement\CashMovementQueries;
use App\Http\Controllers\StoreManager\CashMovementReportController;
use App\Models\CashMovement;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedCashMovementListsForStoreManager method of the CashMovementQueries class and returns proper response',
    function (): void {
        $locationId = 1;
        $companyId = 1;
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'date_range' => null,
            'counter_ids' => null,
            'cash_movement_type' => null,
        ];

        $cashMovementQueries = $this->mock(CashMovementQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getPaginatedCashMovementListsForStoreManager')
                ->once()
                ->with($requestParameter, $companyId, $locationId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $cashMovementReportController = new CashMovementReportController($cashMovementQueries);
        $response = $cashMovementReportController->fetchCashMovements(new Request($requestParameter));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportCashMovements method and returns a proper response',
    function (): void {
        $companyId = 1;
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'date_range' => null,
            'counter_ids' => null,
            'cash_movement_type' => null,
            'export_columns' => null,
        ];

        $cashMovementQueries = $this->mock(CashMovementQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getCashMovementListsForExportInStoreManagerPanel')
                ->once()
                ->with($requestParameter, $companyId, 1)
                ->andReturn(collect(new CashMovement()));
        });

        $cashMovementReportController = new CashMovementReportController($cashMovementQueries);
        $response = $cashMovementReportController->exportCashMovements('filename.csv', new Request($requestParameter));
        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
