<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Admin\CancelLayawaySaleController;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated cancel layaway sales with relations method of the sale queries class and returns proper response',
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
            'e_invoice_submitted' => null,
            'offline_sale_id' => null,
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('getPaginatedCancelLayawaySalesWithRelations')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $cancelLayawaySaleController = new CancelLayawaySaleController($saleQueries);

        $response = $cancelLayawaySaleController->fetchCancelLayawaySales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportCancelLayawaySales method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'location_ids' => 'null',
        'counter_ids' => 'null',
        'cashier_id' => 'null',
        'member_id' => 'null',
        'employee_id' => null,
        'e_invoice_submitted' => null,
        'offline_sale_id' => null,
        'export_columns' => null,
    ];

    $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getCancelLayawaySalesWithRelationsForExport')
            ->once()
            ->with($requestParameter, 1)
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
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100,
        ]);

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($companyId, $sale): void {
            $mock->shouldReceive('getCancelLayawaySaleItemsBy')
                ->once()
                ->with($sale->id, $companyId)
                ->andReturn($sale);
        });

        $cancelLayawaySaleController = new CancelLayawaySaleController($saleQueries);

        $response = $cancelLayawaySaleController->fetchCancelLayawaySaleItemsBySaleId($sale->id);

        expect($response['cancel_layaway_sale_details']->resource->toArray())
            ->toHaveKeys(['id', 'offline_sale_id', 'total_amount_paid']);
    }
);
