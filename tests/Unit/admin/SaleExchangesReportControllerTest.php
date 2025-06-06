<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Admin\SaleExchangesReportController;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated exchanges with relations method of the sale queries class and returns proper response',
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
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('getPaginatedSaleExchangesWithRelations')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $saleExchangesReportController = new SaleExchangesReportController($saleQueries);

        $response = $saleExchangesReportController->fetchSaleExchanges(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the export method and returns a proper response', function (): void {
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
        'export_columns' => null,
    ];

    $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getSaleExchangesWithRelationsForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Sale()));
    });

    $saleExchangesReportController = new SaleExchangesReportController($saleQueries);

    $response = $saleExchangesReportController->export('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
