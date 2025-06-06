<?php

declare(strict_types=1);

use App\Domains\SaleItem\SaleItemQueries;
use App\Http\Controllers\StoreManager\EmployeeSalesReportController;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated employee sales report list for store manager method of the sale item queries class and returns proper response',
    function (): void {
        $locationId = 1;

        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'employee_id' => null,
            'product_id' => null,
            'date_range' => null,
            'product_collection_id' => null,
        ];

        $saleQueries = $this->mock(SaleItemQueries::class, function ($mock) use (
            $requestParameter,
            $locationId,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedEmployeeSalesReportListForStoreManager')
            ->once()
            ->with($requestParameter, $locationId, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $employeeSalesReportController = new EmployeeSalesReportController($saleQueries);

        $response = $employeeSalesReportController->fetchEmployeeSales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportEmployeeSales method of the sale item queries class and returns proper response',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'employee_id' => null,
            'product_id' => null,
            'date_range' => null,
            'product_collection_id' => null,
            'export_columns' => null,
        ];

        $saleQueries = $this->mock(SaleItemQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('getPaginatedEmployeeSalesListForExportInStoreManagerPanel')
            ->once()
            ->with($requestParameter, 1, 1)
            ->andReturn(collect(new SaleItem()));
        });

        $employeeSalesReportController = new EmployeeSalesReportController($saleQueries);

        $response = $employeeSalesReportController->exportEmployeeSales('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
