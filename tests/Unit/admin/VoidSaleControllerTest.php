<?php

declare(strict_types=1);

use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Admin\VoidSaleController;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list void sales query method of the sale queries class and returns proper response',
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
            'member_id' => 'null',
            'employee_id' => null,
            'void_sale_number' => null,
            'e_invoice_submitted' => null,
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedVoidSalesWithRelations')
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
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => '1',
        'date_range' => 'null',
        'location_ids' => 'null',
        'counter_ids' => 'null',
        'cashier_id' => 'null',
        'member_id' => 'null',
        'employee_id' => null,
        'void_sale_number' => null,
        'e_invoice_submitted' => null,
        'export_columns' => null,
    ];

    $saleQueries = $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoidSalesWithRelationForExport')
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
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100,
            'status' => SaleStatus::VOID_SALE->value,
        ]);

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($companyId, $sale): void {
            $mock->shouldReceive('getVoidSaleItemsBy')
                ->once()
                ->with(1, $companyId)
                ->andReturn($sale);
        });

        $voidSaleController = new VoidSaleController($saleQueries);

        $response = $voidSaleController->fetchVoidSaleItemsBySaleId(1);

        expect($response['void_sale_details']->resource->toArray())
            ->toHaveKeys(['id', 'offline_sale_id', 'total_amount_paid']);
    }
);
