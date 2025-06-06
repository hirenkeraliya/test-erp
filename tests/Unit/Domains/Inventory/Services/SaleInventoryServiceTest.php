<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Inventory\Services\SaleInventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Models\Cashier;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\SaleItem;

test(
    'updateInventoryUnits method calls the updateInventoryUnitsForSaleItem method of the same class twice',
    function (): void {
        $mock = $this->createPartialMock(SaleInventoryService::class, ['updateInventoryUnitsForSaleItem']);

        $quantity = 10;
        $inventoryUnitA = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
            'serial_number_id' => null,
        ]);

        $inventoryUnitB = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
            'serial_number_id' => null,
        ]);

        $mock->expects($this->any())
            ->method('updateInventoryUnitsForSaleItem');

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(InventoryService::class, function ($mock) use ($inventoryUnitA, $inventoryUnitB): void {
            $mock->shouldReceive('getInventoryUnits')
                ->once()
                ->andReturn(collect([$inventoryUnitA, $inventoryUnitB]));
        });

        $product = commonGetProductDetails();

        $mock->updateInventoryUnits(
            $inventory,
            $product,
            1,
            new SaleItem(),
            new Cashier(),
            $quantity,
            now()->format('Y-m-d H:i:s'),
            null,
            null,
        );
    }
);

test(
    'updateInventoryUnitsForSaleItem method calls the addNew method of the SaleItemUnitQueries class',
    function (): void {
        $quantity = 10;
        $inventoryUnitA = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(SaleItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseStock')
                ->once();
        });

        $saleInventoryService = resolve(SaleInventoryService::class);
        $saleInventoryService->updateInventoryUnitsForSaleItem(
            $inventory,
            $inventoryUnitA,
            new SaleItem(),
            new Cashier(),
            1,
            1,
            $quantity,
            10.10,
            now()->format('Y-m-d H:i:s'),
        );
    }
);

test(
    'updateNegativeInventoryUnitsForSaleItem method calls the addBlankRecord method of the PurchaseAmountQueries class',
    function (): void {
        $quantity = 10;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(SaleItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
            $mock->shouldReceive('decreaseStock')
                ->once();
        });

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addBlankRecord')
                ->once();
        });

        $saleInventoryService = resolve(SaleInventoryService::class);
        $saleInventoryService->updateNegativeInventoryUnitsForSaleItem(
            $inventory,
            new SaleItem(),
            new Cashier(),
            1,
            1,
            $quantity,
            10.10,
            now()->format('Y-m-d H:i:s'),
            null,
            null
        );
    }
);

test(
    'addInventory method calls the fetchOrCreate method of the InventoryQueries class as expected',
    function (): void {
        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
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

        $saleInventoryService = new SaleInventoryService();
        $saleInventoryService->addInventory($saleItem, new Cashier(), 10.10, 1, 1, 1, now()->format('Y-m-d H:i:s'));
    }
);
