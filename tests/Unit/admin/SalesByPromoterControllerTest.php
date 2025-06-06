<?php

declare(strict_types=1);

use App\Domains\Promoter\PromoterQueries;
use App\Http\Controllers\Admin\SalesByPromoterController;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated sales by promoters method of the promoter queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => 'null',
            'promoter_id' => 'null',
            'location_ids' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'group_ids' => null,
            'sales_filter_types' => [1],
        ];

        $totals = [
            'total_net_sales' => 0,
            'total_amount_sold' => 0,
            'total_units_sold' => 0,
            'total_units_returned' => 0,
            'total_returned_amount' => 0,
        ];

        $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedSalesByPromoters')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getSalesByPromotersTotals')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new Promoter());
        });

        $salesByPromoterController = new SalesByPromoterController($promoterQueries);

        $response = $salesByPromoterController->fetchSalesByPromoters(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
        $this->assertEquals($totals['total_net_sales'], $response['total_net_sales']);
        $this->assertEquals($totals['total_amount_sold'], $response['total_sales']);
        $this->assertEquals($totals['total_units_sold'], $response['total_units_sold']);
        $this->assertEquals($totals['total_units_returned'], $response['total_units_returned']);
        $this->assertEquals($totals['total_returned_amount'], $response['total_returned_amount']);
    }
);

test('It calls the exportSalesByPromoters method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'promoter_id' => 'null',
        'location_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'group_ids' => null,
        'sales_filter_types' => [1],
        'export_columns' => null,
    ];

    $totals = [
        'total_net_sales' => 0,
        'total_amount_sold' => 0,
        'total_units_sold' => 0,
        'total_units_returned' => 0,
        'total_returned_amount' => 0,
    ];

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getSalesByPromotersExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Promoter()));
        $mock->shouldReceive('getSalesByPromotersTotals')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new Promoter());
    });

    $salesByPromoterController = new SalesByPromoterController($promoterQueries);

    $response = $salesByPromoterController->exportSalesByPromoters('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
