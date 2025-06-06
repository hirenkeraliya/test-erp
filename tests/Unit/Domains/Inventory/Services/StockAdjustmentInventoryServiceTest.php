<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Inventory\Services\StockAdjustmentInventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Models\Admin;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Collection;

test(
    'updateInventory method calls respective queries methods as expected with updateInventoryUnits',
    function (): void {
        $locationId = 1;
        $product = commonGetProductDetails();
        $batchId = 1;
        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $locationId,
            'location_id' => $product->id,
        ]);

        $stockAdjustmentProduct = [
            'location_name' => 'new_store',
            'upc' => 'abd123',
            'quantity' => 10,
            'landed_cost' => 1.11,
        ];

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('fetchOrCreate')
                ->with(1, 1)
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('increaseStock')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnit')
                ->once();
        });

        $stockAdjustmentInventoryService = new StockAdjustmentInventoryService();
        $stockAdjustmentInventoryService->updateInventory(
            new StockAdjustmentItem(),
            $stockAdjustmentProduct,
            new Admin(),
            $locationId,
            $product,
            1,
            $batchId
        );
    }
);

test(
    'updateInventory method calls respective queries methods as expected with removeInventoryUnits',
    function (): void {
        $locationId = 1;
        $product = commonGetProductDetails();
        $batchId = 1;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $locationId,
            'location_id' => $product->id,
        ]);

        $stockAdjustmentProduct = [
            'location_name' => 'new_store',
            'upc' => 'abd123',
            'quantity' => -10,
            'landed_cost' => 1.11,
        ];

        $inventoryUnits = [
            new InventoryUnit([
                'quantity' => 8,
                'purchase_amount_id' => 1,
            ]),
            new InventoryUnit([
                'quantity' => 10,
                'purchase_amount_id' => 1,
            ]),
        ];

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('fetchOrCreate')
                ->with(1, 1)
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('increaseStock')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock) use ($inventoryUnits): void {
            $mock->shouldReceive('addNew')
                ->times(0);
            $mock->shouldReceive('getByInventoryId')
                ->times(0);
            $mock->shouldReceive('getByInventoryBatchId')
                ->once()
                ->andReturn(new Collection($inventoryUnits));
            $mock->shouldReceive('decreaseStock')
                ->times(2);
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addBlankRecord')
                ->times(0);
        });

        $stockAdjustmentInventoryService = new StockAdjustmentInventoryService();
        $stockAdjustmentInventoryService->updateInventory(
            new StockAdjustmentItem(),
            $stockAdjustmentProduct,
            new Admin(),
            $locationId,
            $product,
            1,
            $batchId
        );
    }
);

test(
    'removeInventoryUnits method calls the getInventoryUnits method of the same class as expected',
    function (): void {
        $locationId = 1;
        $product = commonGetProductDetails(hasBatch: false);
        $batchId = null;
        $inventoryId = 1;
        $inventoryStock = 8;

        $stockAdjustmentProduct = [
            'quantity' => -8,
        ];

        $inventoryUnits = [
            new InventoryUnit([
                'quantity' => 8,
                'purchase_amount_id' => 1,
            ]),
        ];

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseStock')
                ->times(1);
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(1);
        });

        $this->mock(InventoryService::class, function ($mock) use ($inventoryUnits): void {
            $mock->shouldReceive('getInventoryUnits')
                ->times(1)
                ->andReturn(new Collection($inventoryUnits));
        });

        $stockAdjustmentInventoryService = new StockAdjustmentInventoryService();

        $stockAdjustmentInventoryService->removeInventoryUnits(
            $inventoryId,
            $product,
            $locationId,
            $batchId,
            new StockAdjustment(),
            new Admin(),
            $stockAdjustmentProduct,
            $inventoryStock
        );
    }
);
