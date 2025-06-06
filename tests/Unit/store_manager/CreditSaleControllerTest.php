<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintCreditSaleReportService;
use App\Http\Controllers\StoreManager\CreditSaleController;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated pending credit sales with relations for store manager method of the sale queries class and returns proper response',
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
            $mock->shouldReceive('getPaginatedPendingCreditSalesWithRelationsForStoreManager')
            ->once()
            ->with($requestParameter, $locationId, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $creditSaleController = new CreditSaleController($saleQueries);

        $response = $creditSaleController->fetchPendingCreditSales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportCreditSales method and returns a proper response', function (): void {
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
        $mock->shouldReceive('getPendingCreditSalesWithRelationsForExportInStoreManagerPanel')
            ->once()
            ->with($requestParameter, 1, 1)
            ->andReturn(collect(new Sale()));
    });

    $creditSaleController = new CreditSaleController($saleQueries);

    $response = $creditSaleController->exportCreditSales('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchCreditSaleItemsBySaleId method of the saleQueries class and returns proper response',
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
            $mock->shouldReceive('getCreditSaleItemsForStoreManager')
                ->once()
                ->with($sale->id, $locationId, $companyId)
                ->andReturn($sale);
        });

        $creditSaleController = new CreditSaleController($saleQueries);

        $response = $creditSaleController->fetchCreditSaleItemsBySaleId(1);

        expect($response['credit_sale_details']->resource->toArray())
           ->toHaveKeys(['id', 'offline_sale_id', 'total_amount_paid']);
    }
);

test(
    'the printCreditSale method and returns the string',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $this->mock(PrintCreditSaleReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });
        $purchaseOrderController = new CreditSaleController(new SaleQueries());
        $response = $purchaseOrderController->printCreditSale(1);
        expect($response)->toBeString();
    }
);
