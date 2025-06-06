<?php

declare(strict_types=1);

use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Http\Controllers\WarehouseManager\StockAdjustmentController;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the warehouseManagerListQuery method of the stockAdjustmentQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        $locationId = 1;

        setWarehouseManagerWarehouseIdInSession($locationId);
        setWarehouseManagerWarehouseCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'stock_adjustment_id' => null,
        ];

        $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('warehouseManagerListQuery')
            ->once()
            ->with($requestParameter, $companyId, $locationId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);

        $response = $stockAdjustmentController->fetchStockAdjustments(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'fetchItems method returns the stock adjustment items list',
    function (): void {
        $companyId = 1;
        $locationId = 1;

        setWarehouseManagerWarehouseIdInSession($locationId);
        setWarehouseManagerWarehouseCompanyIdInSession();

        $stockAdjustment = StockAdjustment::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'created_by_admin_id' => 1,
            'approved_by_employee_id' => 1,
        ]);

        $stockAdjustmentItem = StockAdjustmentItem::factory()->make([
            'id' => 1,
            'location_id' => 1,
            'stock_adjustment_id' => 1,
            'product_id' => 1,
        ]);

        $this->mock(StockAdjustmentItemQueries::class, function ($mock) use (
            $stockAdjustment,
            $stockAdjustmentItem,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getItemsByStockAdjustmentIdForWarehouseManager')
            ->once()
            ->with($stockAdjustment->id, $companyId, $locationId)
            ->andReturn(new Collection([$stockAdjustmentItem]));
        });

        $stockAdjustmentQueries = resolve(StockAdjustmentQueries::class);

        $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);
        $response = $stockAdjustmentController->fetchItems($stockAdjustment->id);

        expect($response['data']->resource)->toBeCollection();
    }
);

test('exportItems method returns the stock adjustment items list in binary file response', function (): void {
    $locationId = 1;

    setWarehouseManagerWarehouseIdInSession($locationId);
    setWarehouseManagerWarehouseCompanyIdInSession();

    $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithItemsForManagerPanel')
            ->once()
            ->with(1, 1, 1)
            ->andReturn(new StockAdjustment());
    });

    $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);

    $response = $stockAdjustmentController->exportItems(1, 'filename.csv');

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportStockAdjustments method and returns a proper response', function (): void {
    $companyId = 1;
    $locationId = 1;

    setWarehouseManagerWarehouseIdInSession($locationId);
    setWarehouseManagerWarehouseCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'stock_adjustment_id' => null,
    ];

    $stockAdjustmentQueries = $this->mock(StockAdjustmentQueries::class, function ($mock) use (
        $requestParameter,
        $companyId,
        $locationId
    ): void {
        $mock->shouldReceive('getWarehouseManagerStockAdjustmentsExport')
        ->once()
            ->with($requestParameter, $companyId, $locationId)
            ->andReturn(collect(new StockAdjustment()));
    });

    $stockAdjustmentController = new StockAdjustmentController($stockAdjustmentQueries);

    $response = $stockAdjustmentController->exportStockAdjustments('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
