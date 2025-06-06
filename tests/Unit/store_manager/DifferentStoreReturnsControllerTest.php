<?php

declare(strict_types=1);

use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\StoreManager\DifferentStoreReturnsController;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated sale returns store manager with relations and different location method of the sale return queries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => 'null',
            'counter_ids' => 'null',
            'cashier_id' => 'null',
            'original_sale_location_ids' => null,
            'original_sale_counter_ids' => null,
            'original_sale_cashier_id' => 'null',
            'member_id' => 'null',
            'employee_id' => null,
            'location_ids' => [$locationId],
            'e_invoice_submitted' => null,
        ];

        $saleQueries = $this->mock(SaleReturnQueries::class, function ($mock) use (
            $requestParameter,
            $locationId,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedDifferentStoresReturnsForStoreManager')
                ->once()
                ->with($requestParameter, [$locationId], $companyId)
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
    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'date_range' => 'null',
        'counter_ids' => 'null',
        'cashier_id' => 'null',
        'member_id' => 'null',
        'employee_id' => null,
        'original_sale_location_ids' => null,
        'original_sale_counter_ids' => null,
        'original_sale_cashier_id' => 'null',
        'location_ids' => [1],
        'e_invoice_submitted' => null,
        'export_columns' => null,
    ];

    $saleReturnQueries = $this->mock(SaleReturnQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getDifferentStoresReturnsForStoreManagerExport')
            ->once()
            ->with($requestParameter, [1], 1)
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
        $locationId = 1;
        setStoreIdInSession($locationId);
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession();

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => $sale->id,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $saleReturnQueries = $this->mock(SaleReturnQueries::class, function ($mock) use (
            $locationId,
            $companyId,
            $saleReturn
        ): void {
            $mock->shouldReceive('getSaleReturnItemsForStoreManager')
                ->once()
                ->with($saleReturn->id, $locationId, $companyId)
                ->andReturn($saleReturn);
        });

        $differentStoreReturnsController = new DifferentStoreReturnsController($saleReturnQueries);

        $response = $differentStoreReturnsController->fetchSaleReturnItemsForDifferentStore($saleReturn->id);

        expect($response['sale_return_details']->resource->toArray())
            ->toHaveKeys(['offline_sale_return_id', 'original_sale_id', 'total_price_paid']);
    }
);
