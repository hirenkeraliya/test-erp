<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Inventory\Services\StockTransferInventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Models\Admin;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\InventoryUpdate;
use App\Models\MasterProduct;
use App\Models\ReservedStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

test(
    'updateInventoryAsPerStockTransfer method calls respective queries methods as expected',
    function (): void {
        $product = commonGetProductDetails();
        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => $product->id,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => new Admin([
            'employee_id' => 1,
        ]));

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'received_date' => Carbon::now()->format('Y-m-d'),
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 1,
            'received_quantity' => null,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 10,
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('fetchOrCreate')
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('increaseStock')
                ->once();
        });

        $this->mock(InventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnit')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
            $mock->shouldReceive('getRecordsAfterDateByLocationAndProduct')
                ->once()
                ->andReturn(collect([]));
        });

        $stockTransferInventoryService = new StockTransferInventoryService();
        $stockTransferInventoryService->updateInventoryAsPerStockTransfer(
            $stockTransferItem,
            $stockTransfer,
            $request->user(),
            $stockTransferItemUnit,
        );
    }
);

test(
    'updateInventoryAsPerStockTransfer method calls respective queries methods if specified received date later inventory records found',
    function (): void {
        $product = commonGetProductDetails();
        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => $product->id,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => new Admin([
            'employee_id' => 1,
        ]));

        $date = Carbon::now();

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'received_date' => $date->format('Y-m-d'),
            'status' => StatusTypes::RECEIVED->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '1',
            'received_quantity' => null,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 10,
        ]);

        $inventoryUpdate = InventoryUpdate::factory()->make([
            'location_id' => 2,
            'affected_by' => $stockTransferItem->id,
            'affected_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
            'product_id' => 1,
            'batch_id' => null,
            'purchase_amount_id' => null,
            'user_type' => ModelMapping::ADMIN->name,
            'happened_at' => $date->addDay(),
        ]);

        $latestInventoryUpdate = InventoryUpdate::factory()->make([
            'location_id' => 2,
            'affected_by' => $stockTransferItem->id,
            'affected_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
            'product_id' => 1,
            'batch_id' => null,
            'purchase_amount_id' => null,
            'user_type' => ModelMapping::ADMIN->name,
            'happened_at' => $date->subDay(),
        ]);

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('updateStockBy')
                ->once()
                ->andReturn(1);
        });

        $this->mock(InventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnit')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock) use (
            $inventoryUpdate,
            $latestInventoryUpdate
        ): void {
            $mock->shouldReceive('addNew')
                ->once();
            $mock->shouldReceive('getRecordsAfterDateByLocationAndProduct')
                ->once()
                ->andReturn(collect([$inventoryUpdate]));
            $mock->shouldReceive('getLatestClosingStockBy')
                ->once()
                ->andReturn($latestInventoryUpdate);
            $mock->shouldReceive('updateClosingStockOfPreviousRecord')
                ->once()
                ->andReturn(1);
        });

        $stockTransferInventoryService = new StockTransferInventoryService();
        $stockTransferInventoryService->updateInventoryAsPerStockTransfer(
            $stockTransferItem,
            $stockTransfer,
            $request->user(),
            $stockTransferItemUnit,
        );
    }
);

test(
    'updateInventoryUnitsForStockTransfer method calls the updateInventoryUnitsForStockTransferItem method of the same class twice when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $mock = $this->createPartialMock(
            StockTransferInventoryService::class,
            ['updateInventoryUnitsForStockTransferItem']
        );

        $quantity = 10;
        $inventoryUnitA = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventoryUnitB = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $mock->expects($this->any())
            ->method('updateInventoryUnitsForStockTransferItem');

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseStock')
                ->once();
        });

        $this->mock(InventoryService::class, function ($mock) use ($inventoryUnitA, $inventoryUnitB): void {
            $mock->shouldReceive('getInventoryUnits')
                ->once()
                ->andReturn(collect([$inventoryUnitA, $inventoryUnitB]));
        });

        $product = commonGetProductDetails();

        $mock->updateInventoryUnits($inventory, $product, 1, new StockTransferItem(), new Admin(), $quantity);
    }
);

test(
    'updateInventoryUnitsForStockTransfer method calls the updateInventoryUnitsForStockTransferItem method of the same class twice when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $mock = $this->createPartialMock(
            StockTransferInventoryService::class,
            ['updateInventoryUnitsForStockTransferItem']
        );

        $quantity = 10;
        $inventoryUnitA = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventoryUnitB = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $mock->expects($this->any())
            ->method('updateInventoryUnitsForStockTransferItem');

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseStock')
                ->once();
        });

        $this->mock(InventoryService::class, function ($mock) use ($inventoryUnitA, $inventoryUnitB): void {
            $mock->shouldReceive('getInventoryUnits')
                ->once()
                ->andReturn(collect([$inventoryUnitA, $inventoryUnitB]));
        });

        $product = commonGetProductDetails();

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => true,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $mock->updateInventoryUnits($inventory, $product, 1, new StockTransferItem(), new Admin(), $quantity);
    }
);

test(
    'updateInventoryUnitsForStockTransferItem method calls method of the InventoryUnitQueries class as expected',
    function (): void {
        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 10,
        ]);

        $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
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

        $stockTransferInventoryService = new StockTransferInventoryService();
        $stockTransferInventoryService->updateInventoryUnitsForStockTransferItem(
            Inventory::factory()->make([
                'id' => 1,
                'product_id' => 1,
                'location_id' => 1,
            ]),
            new InventoryUnit(),
            $stockTransferItem,
            new Admin(),
            1,
            1,
            10,
            10
        );
    }
);

test('revertReservedStock method calls and respective query class calls', function (): void {
    [$stockTransferItem, $inventory, $reservedStock] = seedReservedInventories();

    $this->mock(InventoryUnitQueries::class, function ($mock) use ($reservedStock): void {
        $mock->shouldReceive('revertReservedStock')
            ->once();
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($reservedStock->inventoryUnit);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('revertReservedStock')
            ->once();
        $mock->shouldReceive('getInventoryById')
            ->once()
            ->andReturn($inventory);
    });

    $this->mock(ReservedStockQueries::class, function ($mock) use ($reservedStock): void {
        $mock->shouldReceive('getByAffectedBy')
            ->once()
            ->andReturn(collect([$reservedStock]));
        $mock->shouldReceive('delete')
            ->once();
    });

    $stockTransferInventoryService = new StockTransferInventoryService();
    $stockTransferInventoryService->revertReservedStock($stockTransferItem);
});

test('removeReservationStock method calls and respective query class calls', function (): void {
    [$stockTransferItem, $inventory, $reservedStock] = seedReservedInventories();

    $this->mock(InventoryUnitQueries::class, function ($mock): void {
        $mock->shouldReceive('decreaseReservedStock')
            ->once();
    });

    $this->mock(InventoryUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('decreaseReservedStock')
            ->once()
            ->andReturn($inventory);
    });

    $this->mock(ReservedStockQueries::class, function ($mock) use ($reservedStock): void {
        $mock->shouldReceive('getByAffectedBy')
            ->once()
            ->andReturn(collect([$reservedStock]));
        $mock->shouldReceive('delete')
            ->once();
    });

    $user = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $user);

    $stockTransferInventoryService = new StockTransferInventoryService();
    $stockTransferInventoryService->removeReservationStock($stockTransferItem, $user);
});

test(
    'updateInventoryUnitsWithReserved method calls and respective query class calls when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        [$stockTransferItem,
            $inventory,
            $reservedStock] = seedReservedInventories();
        $product = commonGetProductDetails($hasBatch = false);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $inventoryUnits = collect([$reservedStock->inventoryUnit]);

        $this->mock(InventoryService::class, function ($mock) use ($inventoryUnits): void {
            $mock->shouldReceive('getInventoryUnits')
                ->once()
                ->andReturn($inventoryUnits);
        });

        $this->mock(ReservedStockQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('increaseReservedStock')
                ->once();
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('increaseReservedStock')
                ->once();
            $mock->shouldReceive('getInventoryById')
                ->once()
                ->andReturn($inventory);
        });

        $stockTransferInventoryService = new StockTransferInventoryService();
        $stockTransferInventoryService->updateInventoryUnitsWithReserved(
            $inventory,
            $product,
            $stockTransferItem,
            1,
            null
        );
    }
);

test('the closeTransfer method calls and transfer inventories to destination location', function (): void {
    $companyId = 1;
    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'transfer_type' => StockTransferTypes::TRANSFER_ORDER->value,
        'source_location_id' => $storeOne->id,
        'destination_location_id' => $storeTwo,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::DRAFT->value,
        'transfer_order_number' => 'A123',
    ]);

    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => 5,
        'received_quantity' => null,
    ]);

    $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
        'stock_transfer_item_id' => $stockTransferItem->id,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => 1,
        'quantity' => 5,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);
    $stockTransferItem->units = collect([$stockTransferItemUnit]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
    ]);

    $inventoryUnit = InventoryUnit::factory()->make([
        'id' => 1,
        'inventory_id' => $inventory->id,
        'purchase_amount_id' => 1,
        'batch_id' => null,
    ]);

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('fetchOrCreate')
            ->once()
            ->andReturn($inventory);
    });

    $this->mock(InventoryUnitQueries::class, function ($mock) use ($inventoryUnit): void {
        $mock->shouldReceive('addNewAndGetId')
            ->once()
            ->andReturn($inventoryUnit);
    });

    $this->mock(TransitStockQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferInventoryService = new StockTransferInventoryService();

    $stockTransferInventoryService->addTransitStock($stockTransfer->destination_location_id, $stockTransferItem);
});

function seedReservedInventories(): array
{
    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => 10,
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
    ]);

    $inventoryUnit = InventoryUnit::factory()->make([
        'id' => 1,
        'inventory_id' => $inventory->id,
        'purchase_amount_id' => 1,
        'batch_id' => null,
    ]);

    $reservedStock = ReservedStock::factory()->make([
        'inventory_id' => $inventory->id,
        'inventory_unit_id' => $inventoryUnit->id,
        'affected_by_id' => $stockTransferItem->id,
        'affected_by_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
    ]);

    $reservedStock->inventory = $inventory;
    $reservedStock->inventoryUnit = $inventoryUnit;

    return [$stockTransferItem, $inventory, $reservedStock];
}
