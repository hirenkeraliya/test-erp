<?php

declare(strict_types=1);

use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\StoreManager\VoidSaleController;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated void sales with relations for store manager method of the sale queries class and returns proper response',
    function (): void {
        $locationId = 1;
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
            'void_sale_offline_number' => null,
            'void_sale_number' => null,
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedVoidSalesWithRelationsForStoreManager')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $voidSaleController = new VoidSaleController($saleQueries);

        $response = $voidSaleController->fetchVoidSales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportVoidSale method and returns a proper response', function (): void {
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
        'void_sale_number' => null,
        'export_columns' => null,
    ];

    $saleQueries = $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoidSalesWithRelationsForExportInStoreManagerPanel')
            ->once()
            ->andReturn(collect(new Sale()));
    });

    $voidSaleController = new VoidSaleController($saleQueries);

    $response = $voidSaleController->exportVoidSale('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchVoidSaleItemsBySaleId method of the saleQueries class and returns proper response',
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
            'status' => SaleStatus::VOID_SALE->value,
        ]);

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use (
            $locationId,
            $companyId,
            $sale
        ): void {
            $mock->shouldReceive('getVoidSaleItemsForStoreManager')
                ->once()
                ->with(1, $locationId, $companyId)
                ->andReturn($sale);
        });

        $voidSaleController = new VoidSaleController($saleQueries);

        $response = $voidSaleController->fetchVoidSaleItemsBySaleId(1);

        expect($response['void_sale_details']->resource->toArray())
            ->toHaveKeys(['id', 'offline_sale_id', 'total_amount_paid']);
    }
);
