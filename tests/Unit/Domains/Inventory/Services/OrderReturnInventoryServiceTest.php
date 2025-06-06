<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\OrderReturnInventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Models\Inventory;
use App\Models\OrderReturnItem;
use App\Models\StoreManager;

test(
    'addInventory method calls the fetchOrCreate method of the InventoryQueries class as expected',
    function (): void {
        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('fetchOrCreate')
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('increaseStock')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
            $mock->shouldReceive('getByInventoryIdBatchIdAndPurchaseAmountId')
                ->once();
        });

        $orderReturnInventoryService = new OrderReturnInventoryService();
        $orderReturnInventoryService->addInventory(
            new OrderReturnItem(),
            new StoreManager(),
            10.10,
            1,
            1,
            1,
            1,
            now()->format('Y-m-d H:i:s')
        );
    }
);
