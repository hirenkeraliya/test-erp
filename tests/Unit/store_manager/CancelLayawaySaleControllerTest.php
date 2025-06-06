<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\StoreManager\CancelLayawaySaleController;
use App\Models\Sale;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated cancel layaway sales for store manager method of the sale queries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => '',
            'per_page' => '',
            'date_range' => 'null',
            'counter_ids' => 'null',
            'cashier_id' => 'null',
            'member_id' => 'null',
            'employee_id' => null,
            'offline_sale_id' => null,
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use (
            $requestParameter,
            $locationId,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedCancelLayawaySalesForStoreManager')
            ->once()
            ->with($requestParameter, $locationId, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $cancelLayawaySaleController = new CancelLayawaySaleController($saleQueries);

        $response = $cancelLayawaySaleController->fetchCancelLayawaySales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportCancelLayawaySales method and returns a proper response', function (): void {
    $locationId = 1;
    setStoreIdInSession();
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
        'date_range' => 'null',
        'counter_ids' => 'null',
        'cashier_id' => 'null',
        'member_id' => 'null',
        'employee_id' => null,
        'offline_sale_id' => null,
        'export_columns' => null,
    ];

    $saleQueries = $this->mock(SaleQueries::class, function ($mock) use (
        $requestParameter,
        $locationId,
        $companyId
    ): void {
        $mock->shouldReceive('getCancelLayawaySalesExportForStoreManager')
            ->once()
            ->with($requestParameter, $locationId, $companyId)
            ->andReturn(collect(new Sale()));
    });

    $cancelLayawaySaleController = new CancelLayawaySaleController($saleQueries);

    $response = $cancelLayawaySaleController->exportCancelLayawaySales('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchCancelLayawaySaleItemsBySaleId method of the saleQueries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $request = new Request();

        $storeManager = StoreManager::factory()->make([
            'employee_id' => 1,
        ]);
        $request->setUserResolver(fn (): StoreManager => $storeManager);

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
            $mock->shouldReceive('getCancelLayawaySaleItemsByForStoreManager')
                ->once()
                ->with($sale->id, $locationId, $companyId)
                ->andReturn($sale);
        });

        $cancelLayawaySaleController = new CancelLayawaySaleController($saleQueries);

        $response = $cancelLayawaySaleController->fetchCancelLayawaySaleItemsBySaleId($request, $sale->id);

        expect($response['cancel_layaway_sale_details']->resource->toArray())
            ->toHaveKeys(['id', 'offline_sale_id', 'total_amount_paid']);
    }
);
