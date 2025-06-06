<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Http\Controllers\Api\Integration\InventoryController;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the getAllByCompanyId method of the InventoryUpdateQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $inventoryUpdateData = [
        [
            'id' => 1,
            'product_id' => 101,
            'location_id' => 201,
            'stock' => 50,
            'happened_at_date' => '2023-10-01',
        ],
    ];

    $this->mock(InventoryUpdateQueries::class, function ($mock) use ($inventoryUpdateData): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(new LengthAwarePaginator($inventoryUpdateData, 10, 5));
    });

    $inventoryController = new InventoryController();
    $response = $inventoryController->getProductsClosingStocksPerDay($request);

    expect($response['inventory_updates']->first())->toHaveKeys([
        'id',
        'product_id',
        'location_id',
        'stock',
        'happened_at_date',
    ]);
});

test('It calls the getAllByCompanyId method of the InventoryQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $inventoryData = [
        [
            'id' => 1,
            'product_id' => 101,
            'location_id' => 201,
            'stock' => 50,
        ],
    ];

    $this->mock(InventoryQueries::class, function ($mock) use ($inventoryData): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(new LengthAwarePaginator($inventoryData, 10, 5));
    });

    $inventoryController = new InventoryController();
    $response = $inventoryController->getProductsCurrentStock($request);

    expect($response['inventories']->first())->toHaveKeys(['id', 'product_id', 'location_id', 'stock']);
});
