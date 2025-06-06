<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\StoreManager\SaleController;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated regular sales with relations for store manager method of the sale queries class and returns proper response',
    function (): void {
        $locationId = 1;
        $companyId = 1;
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
            'employee_id' => 'null',
            'location_ids' => [$locationId],
            'offline_sale_id' => null,
            'e_invoice_submitted' => null,
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use (
            $requestParameter,
            $locationId,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedRegularSalesAndCompleteWithRelationsForStoreManager')
                ->once()
                ->with($requestParameter, [$locationId], $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getFilteredTotalsForReport')
                ->once()
                ->with($requestParameter, 1)
                ->andReturn(new Sale());
        });

        $saleController = new SaleController($saleQueries);

        $response = $saleController->fetchRegularSales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportSales method and returns a proper response', function (): void {
    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'counter_ids' => 'null',
        'cashier_id' => 'null',
        'member_id' => 'null',
        'employee_id' => 'null',
        'offline_sale_id' => null,
        'e_invoice_submitted' => null,
        'export_columns' => null,
    ];

    $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getRegularAndLayawaySalesWithRelationsForExportInStoreManagerPanel')
            ->once()
            ->with($requestParameter, [1])
            ->andReturn(collect(new Sale()));
    });

    $saleController = new SaleController($saleQueries);

    $response = $saleController->exportSales('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchSaleItemsBySaleId method of the saleQueries class and returns proper response',
    function (): void {
        $locationId = 1;
        $companyId = 1;
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100,
        ]);

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use (
            $locationId,
            $companyId,
            $sale
        ): void {
            $mock->shouldReceive('getSaleItemsForStoreManager')
                ->once()
                ->with(1, $locationId, $companyId)
                ->andReturn($sale);
        });

        $saleController = new SaleController($saleQueries);

        $response = $saleController->fetchSaleItemsBySaleId(1);

        expect($response['sale_details']->resource->toArray())
            ->toHaveKeys(['id', 'total_price_paid']);
    }
);
