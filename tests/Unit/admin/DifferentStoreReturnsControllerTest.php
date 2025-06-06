<?php

declare(strict_types=1);

use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Admin\DifferentStoreReturnsController;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated sale returns with relations and different stores method of the sale return queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => 'null',
            'location_ids' => 'null',
            'counter_ids' => 'null',
            'cashier_id' => 'null',
            'original_sale_location_ids' => [],
            'original_sale_counter_ids' => [],
            'original_sale_cashier_id' => 'null',
            'member_id' => 'null',
            'employee_id' => null,
            'e_invoice_submitted' => null,
        ];

        $saleQueries = $this->mock(SaleReturnQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedDifferentStoreReturnsWithRelation')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getFilteredTotalsDifferentStoreForReport')
                ->once()
                ->with($requestParameter, 1)
                ->andReturn(new SaleReturn());
        });

        $differentStoreReturnsController = new DifferentStoreReturnsController($saleQueries);

        $response = $differentStoreReturnsController->fetchDifferentStoreReturns(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportSaleReturns method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'per_page' => '10',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'location_ids' => 'null',
        'counter_ids' => 'null',
        'cashier_id' => 'null',
        'original_sale_location_ids' => [],
        'original_sale_counter_ids' => [],
        'original_sale_cashier_id' => 'null',
        'member_id' => 'null',
        'employee_id' => null,
        'e_invoice_submitted' => null,
        'export_columns' => null,
    ];

    $saleReturnQueries = $this->mock(SaleReturnQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getDifferentStoreReturnWithRelationForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new SaleReturn()));
    });

    $differentStoreReturnsController = new DifferentStoreReturnsController($saleReturnQueries);

    $response = $differentStoreReturnsController->exportDifferentStoreReturns(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchSaleReturnItems method of the saleReturnQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $saleReturnQueries = $this->mock(SaleReturnQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getSaleReturnItemsBy')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new SaleReturn());
        });
        $differentStoreReturnsController = new DifferentStoreReturnsController($saleReturnQueries);
        $response = $differentStoreReturnsController->fetchSaleReturnItemsForDifferentStore(1);
        expect($response)
            ->toHaveKey('sale_return_details');
    }
);
