<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\PurchaseOrderFulfillmentItemUnit\PurchaseOrderFulfillmentItemUnitQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\InventoryUpdate;
use App\Models\OrderItemUnit;
use App\Models\PurchaseOrderFulfillmentItemUnit;
use App\Models\ReservedStock;
use App\Models\SaleItemUnit;
use App\Models\StockTransferItemUnit;
use App\Models\TransitStock;

test(
    'getInventoryUnits method calls method of the InventoryUnitQueries class as expected',
    function ($hasBatch, $batchId): void {
        $this->mock(InventoryUnitQueries::class, function ($mock) use ($hasBatch, $batchId): void {
            $mock->shouldReceive('getByInventoryId')
                ->times($hasBatch ? 0 : 1);
            $mock->shouldReceive('getByInventoryBatchId')
                ->times($hasBatch && $batchId ? 1 : 0);
            $mock->shouldReceive('getByInventoryIdOrderByBatchExpiryDate')
                ->times($hasBatch && ! $batchId ? 1 : 0);
        });
        $inventoryService = new InventoryService();
        $inventoryService->getInventoryUnits($hasBatch, 1, $batchId, null);
    }
)->with([[false, null], [true, 1], [true, null]]);

test(
    'getInventoryUnits method calls method of the InventoryUnitQueries class when serial number id as expected',
    function (): void {
        $serialNumberId = 1;

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('getByInventoryId')
            ->times(1);
        });

        $inventoryService = new InventoryService();
        $inventoryService->getInventoryUnits(false, 1, null, $serialNumberId);
    }
);

test(
    'updateInventoryAsPerStockTransfer method calls addNew method of  InventoryUnitQueries class as expected',
    function (): void {
        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('getByInventoryIdBatchIdAndPurchaseAmountId')
                ->once();
            $mock->shouldReceive('addNew')
                ->once();
        });

        $inventoryService = new InventoryService();
        $inventoryService->updateInventoryUnit(10.10, 1, 1, 1, null);
    }
);

test(
    'updateInventoryAsPerStockTransfer method calls increaseStock method of  InventoryUnitQueries class as expected',
    function (): void {
        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 1,
        ]);

        $this->mock(InventoryUnitQueries::class, function ($mock) use ($inventoryUnit): void {
            $mock->shouldReceive('getByInventoryIdBatchIdAndPurchaseAmountId')
                ->once()
                ->andReturn($inventoryUnit);
            $mock->shouldReceive('increaseStock')
                ->once();
        });

        $inventoryService = new InventoryService();
        $inventoryService->updateInventoryUnit(10.10, 1, 1, 1, null);
    }
);

test(
    'When a product merge is executed, we update the related inventory tables, specifically when there is existing new product inventory data.',
    function (): void {
        $inventoryA = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 1,
            'reserved_stock' => 1,
        ]);

        $inventoryB = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 2,
            'location_id' => 1,
            'stock' => 1,
            'reserved_stock' => 1,
        ]);

        $inventoryUpdateA = InventoryUpdate::factory()->make([
            'id' => 1,
            'product_id' => 2,
            'batch_id' => 1,
            'purchase_amount_id' => 1,
            'location_id' => 1,
            'affected_by_id' => 1,
            'affected_by_type' => ModelMapping::MERGE_PRODUCT_TRANSACTION->name,
            'user_id' => 1,
            'user_type' => ModelMapping::ADMIN->name,
            'stock' => 1,
            'reserved_stock' => 1,
        ]);

        $inventoryUnitA = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 1,
        ]);

        $reservedStockA = ReservedStock::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'inventory_unit_id' => 1,
            'affected_by_id' => 1,
            'affected_by_type' => 1,
            'deleted_at' => now(),
        ]);

        $transitStockA = TransitStock::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'inventory_unit_id' => 1,
            'affected_by_id' => 1,
            'affected_by_type' => 1,
            'deleted_at' => now(),
        ]);

        $saleItemUnit = SaleItemUnit::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $orderItemUnit = OrderItemUnit::factory()->make([
            'id' => 1,
            'order_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $stockTransferItemUnits = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $purchaseOrderFulfillmentItemUnit = PurchaseOrderFulfillmentItemUnit::factory()->make([
            'id' => 1,
            'purchase_order_fulfillment_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $inventoryA->inventoryUnits = collect([$inventoryUnitA]);
        $inventoryA->reservedStocksWithDeleted = collect([$reservedStockA]);
        $inventoryA->transitStocksWithDeleted = collect([$transitStockA]);
        $inventoryA->saleItemUnits = collect([$saleItemUnit]);
        $inventoryA->orderItemUnits = collect([$orderItemUnit]);
        $inventoryA->purchaseOrderFulfillmentItemUnits = collect([$purchaseOrderFulfillmentItemUnit]);
        $inventoryA->stockTransferItemUnitsWithDeleted = collect([$stockTransferItemUnits]);

        $this->mock(InventoryQueries::class, function ($mock) use ($inventoryA, $inventoryB): void {
            $mock->shouldReceive('getByProductId')
                ->once()
                ->andReturn(collect([$inventoryA]));
            $mock->shouldReceive('getByProductId')
                ->once()
                ->andReturn(collect([$inventoryB]));
            $mock->shouldReceive('increaseStockAndReservedStockAndDeleteOldInventoryData')
                ->once();
            $mock->shouldReceive('getInventoriesByProductId')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getByProductIdAndLocationAndUpdateWithNewProductId')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryId')
                ->once();
        });

        $this->mock(ReservedStockQueries::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryId')
                ->once();
        });
        $this->mock(TransitStockQueries::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryIdDuringProductMerge')
                ->once();
        });
        $this->mock(SaleItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryId')
                ->once();
        });
        $this->mock(OrderItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryId')
                ->once();
        });
        $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryId')
                ->once();
        });

        $this->mock(PurchaseOrderFulfillmentItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryId')
                ->once();
        });

        $inventoryService = new InventoryService();
        $inventoryService->mergeInventory(1, 2);
    }
);

test(
    'When a product merge is executed, we update the related inventory tables, specifically when there is no existing new product inventory data.',
    function (): void {
        $inventoryA = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 1,
            'reserved_stock' => 1,
        ]);

        $inventoryB = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 2,
            'location_id' => 1,
            'stock' => 1,
            'reserved_stock' => 1,
        ]);

        $inventoryUpdateA = InventoryUpdate::factory()->make([
            'id' => 1,
            'product_id' => 2,
            'batch_id' => 1,
            'purchase_amount_id' => 1,
            'location_id' => 1,
            'affected_by_id' => 1,
            'affected_by_type' => ModelMapping::MERGE_PRODUCT_TRANSACTION->name,
            'user_id' => 1,
            'user_type' => ModelMapping::ADMIN->name,
            'stock' => 1,
            'reserved_stock' => 1,
        ]);

        $inventoryUnitA = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 1,
        ]);

        $reservedStockA = ReservedStock::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'inventory_unit_id' => 1,
            'affected_by_id' => 1,
            'affected_by_type' => 1,
            'deleted_at' => now(),
        ]);

        $saleItemUnit = SaleItemUnit::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $stockTransferItemUnits = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $inventoryA->inventoryUnits = collect([$inventoryUnitA]);
        $inventoryA->reservedStocksWithDeleted = collect([$reservedStockA]);
        $inventoryA->saleItemUnits = collect([$saleItemUnit]);
        $inventoryA->stockTransferItemUnits = collect([$stockTransferItemUnits]);

        $this->mock(InventoryQueries::class, function ($mock) use ($inventoryA): void {
            $mock->shouldReceive('getByProductId')
                ->once()
                ->andReturn(collect([$inventoryA]));
            $mock->shouldReceive('getByProductId')
                ->once()
                ->andReturn(collect([]));
            $mock->shouldReceive('updateProductId')
                ->once()
                ->andReturn(collect([]));
            $mock->shouldReceive('getInventoriesByProductId')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getByProductIdAndLocationAndUpdateWithNewProductId')
                ->once()
                ->andReturn(collect([]));
        });

        $inventoryService = new InventoryService();
        $inventoryService->mergeInventory(1, 2);
    }
);
