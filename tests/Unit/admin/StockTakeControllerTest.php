<?php

declare(strict_types=1);

use App\Domains\StockTake\StockTakeQueries;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Http\Controllers\Admin\StockTakeController;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getAdminListQuery method of the stockTakeQueries class and returns proper response',
    function (): void {
        setCompanyIdInSession();
        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
        ];

        $request = new Request($requestParameter);

        $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock): void {
            $mock->shouldReceive('getAdminListQuery')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $stockTakeController = new StockTakeController($stockTakeQueries);

        $response = $stockTakeController->fetchStockTakes($request);

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the getSubmittedStockTakeProductsByStockTakeId method of the StockTakeProductQueries class and returns proper response',
    function (): void {
        setCompanyIdInSession();
        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getSubmittedStockTakeProductsByStockTakeId')
                ->once()
                ->andReturn(new Collection([]));
        });

        $stockTakeController = new StockTakeController(new StockTakeQueries());

        $response = $stockTakeController->exportStockTakeProducts(1, 'file.csv');

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test('It calls the exportStockTakes method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'export_columns' => null,
    ];

    $stockTakeQueries = $this->mock(StockTakeQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getStockTakesExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect([]));
    });

    $stockTakeController = new StockTakeController($stockTakeQueries);

    $response = $stockTakeController->exportStockTakes('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
