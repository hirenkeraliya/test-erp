<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Http\Controllers\WarehouseManager\InventoryController;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'It calls the fetchOrCreate method of the inventory queries class and returns proper response',
    function (): void {
        $request = new Request([
            'source_location_id' => 1,
            'destination_location_id' => 1,
            'product_ids' => [1],
        ]);

        setWarehouseManagerWarehouseCompanyIdInSession(1);

        $this->mock(InventoryService::class, function ($mock): void {
            $mock->shouldReceive('getLocation')
                ->once()
                ->andReturn([1, 2]);
        });

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllActiveProductsExist')
                ->once()
                ->andReturn(true);
        });

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('fetchOrCreate')
                ->times(2)
                ->andReturn(new Inventory());
        });

        $inventoryController = new InventoryController();
        $response = $inventoryController->getStocks($request);

        expect($response)
        ->toHaveKeys(['source_inventories', 'destination_inventories']);
    }
);

test(
    'getStocks method thrown exception error if invalid source or destination location id passed.',
    function ($sourceLocationId, $destinationLocationId): void {
        $request = new Request([
            'source_location_type' => 'Store',
            'source_location_id' => 1,
            'destination_location_type' => 'Store',
            'destination_location_id' => 1,
            'product_ids' => [1],
        ]);

        setWarehouseManagerWarehouseCompanyIdInSession(1);

        $this->mock(InventoryService::class, function ($mock) use (
            $sourceLocationId,
            $destinationLocationId
        ): void {
            $mock->shouldReceive('getLocation')
            ->once()
            ->andReturn([$sourceLocationId, $destinationLocationId]);
        });

        $inventoryController = new InventoryController();
        $inventoryController->getStocks($request);
    }
)->with([[1, null], [null, 1]])->throws(HttpException::class, 'Invalid Source or Destination location selected');

test('getStocks method thrown exception error if invalid product specified.', function (): void {
    $request = new Request([
        'source_location_type' => 'Store',
        'source_location_id' => 1,
        'destination_location_type' => 'Store',
        'destination_location_id' => 1,
        'product_ids' => [1],
    ]);

    setWarehouseManagerWarehouseCompanyIdInSession(1);

    $this->mock(InventoryService::class, function ($mock): void {
        $mock->shouldReceive('getLocation')
            ->once()
            ->andReturn([1, 'STORE']);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('doAllActiveProductsExist')
            ->once()
            ->andReturn(false);
    });

    $inventoryController = new InventoryController();
    $inventoryController->getStocks($request);
})->throws(HttpException::class);

test(
    'getLocationStocksForPurchaseOrder method calls the getLocationStock method of the PurchaseOrderService class and returns proper response',
    function (): void {
        $request = new Request([
            'location_id' => 1,
            'external_location_id' => 1,
            'product_ids' => [1],
        ]);

        setCompanyIdInSession(1);

        $this->mock(PurchaseOrderService::class, function ($mock): void {
            $mock->shouldReceive('getLocationStock')
                ->once()
                ->andReturn([
                    'products' => [],
                ]);
        });

        $inventoryController = new InventoryController();
        $response = $inventoryController->getLocationStocksForPurchaseOrder($request);

        expect($response)
        ->toHaveKey('products');
    }
);
