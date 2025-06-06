<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\StoreManager\LayawaySaleController;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated pending layaway sales with relations for store manager method of the sale queries class and returns proper response',
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
            'member_id' => 'null',
            'employee_id' => null,
            'status_id' => null,
            'offline_sale_id' => null,
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use (
            $requestParameter,
            $locationId,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedPendingLayawaySalesWithRelationsForStoreManager')
            ->once()
            ->with($requestParameter, $locationId, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $layawaySaleController = new LayawaySaleController($saleQueries);

        $response = $layawaySaleController->fetchPendingLayawaySales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportLayawaySales method and returns a proper response', function (): void {
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
        'employee_id' => null,
        'status_id' => null,
        'offline_sale_id' => null,
        'export_columns' => null,
    ];

    $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getPendingLayawaySalesWithRelationsForExportInStoreManagerPanel')
            ->once()
            ->with($requestParameter, 1, 1)
            ->andReturn(collect(new Sale()));
    });

    $layawaySaleController = new LayawaySaleController($saleQueries);

    $response = $layawaySaleController->exportLayawaySales('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchLayawaySaleItemsBySaleId method of the saleQueries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
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
            $mock->shouldReceive('getLayawaySaleItemsForStoreManager')
                ->once()
                ->with($sale->id, $locationId, $companyId)
                ->andReturn($sale);
        });

        $layawaySaleController = new LayawaySaleController($saleQueries);

        $response = $layawaySaleController->fetchLayawaySaleItemsBySaleId(1);

        expect($response['layaway_sale_details']->resource->toArray())
           ->toHaveKeys(['id', 'offline_sale_id', 'total_amount_paid']);
    }
);
