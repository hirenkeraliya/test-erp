<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\StockTransferInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StockTransfer\DataObjects\StockTransferData;
use App\Domains\StockTransfer\DataObjects\StockTransferRequestOrderData;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Services\StockTransferService;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferAverageLeadDays\StockTransferAverageLeadDaysQueries;
use App\Domains\StockTransferItem\Enums\StockTransferDiscrepancyTypes;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\StockTransferItemBatch\StockTransferItemBatchQueries;
use App\Domains\StockTransferItemTransaction\StockTransferItemTransactionQueries;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\StockTransferTransaction\StockTransferTransactionQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Sequence;
use App\Models\StockTransfer;
use App\Models\StockTransferAverageLeadDays;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemBatch;
use App\Models\StockTransferItemUnit;
use App\Models\UnitOfMeasureDerivative;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

test(
    'the moveSourceLocationStockInTransit method calls and decrease inventories from source location',
    function (): void {
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '5',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => 1,
            'quantity' => '5',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        [$admin, $request] = setRequestUserForAdmin();

        $product = commonGetProductDetails($hasBatch = false);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('removeReservationStock')
                ->once();

            $mock->shouldReceive('addTransitStock')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->moveSourceLocationStockInTransit(
            collect([$product]),
            collect([$inventory]),
            $stockTransfer,
            $request->user()
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
        'quantity' => null,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);
    $stockTransferItem->units = collect([$stockTransferItemUnit]);
    [$admin, $request] = setRequestUserForAdmin();

    $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
        $mock->shouldReceive('delete')
            ->once();
    });

    $this->mock(TransitStockQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteAffectedBy')
            ->once();
    });

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('updateStatus')
            ->once();
        $mock->shouldReceive('loadSourceLocationStoreAndStoreManagers')
            ->once()
            ->andReturn($stockTransfer);
    });

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferService = new StockTransferService();

    $stockTransferService->closeTransfer($stockTransfer, $request->user(), 1, 1);
});

test(
    'the updateDiscrepancySourceInventory method reverts inventory of the batch products when discrepancy type is shortage .',
    function (): void {
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A',
                        'quantity' => 5,
                    ],
                ],
            ]],
        ];

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 5,
            'received_quantity' => 4,
            'discrepancy_type' => null,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'A',
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '5',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->units = collect([$stockTransferItemUnit]);
        $stockTransferItemUnit->batch = $batch;
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);
        $stockTransferItemBatch->batch = $batch;

        [$admin, $request] = setRequestUserForAdmin();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 10,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $product = commonGetProductDetails();

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('revertInventoryAsPerStockTransfer')
                ->once();
        });

        $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseQuantity')
                ->once();
        });

        $this->mock(StockTransferItemBatchQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseQuantity')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->updateDiscrepancySourceInventory(
            $stockTransfer,
            $validatedData,
            $request->user(),
            1,
            collect([$product]),
            collect([$batch])
        );
    }
);

test(
    'the updateDiscrepancySourceInventory method move source location inventory in transit for batch products when discrepancy type is exceed',
    function (): void {
        $batchOne = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'A',
        ]);

        $batchSecond = Batch::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'A123456789',
        ]);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => $batchOne->number,
                        'quantity' => 2,
                    ],
                    [
                        'batch_number' => $batchSecond->number,
                        'quantity' => 3,
                    ],
                ],
            ]],
        ];

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 5,
            'received_quantity' => 10,
            'discrepancy_type' => StockTransferDiscrepancyTypes::POSITIVE->value,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => $batchOne->id,
            'quantity' => 5,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batchOne->id,
            'quantity' => '5',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->units = collect([$stockTransferItemUnit]);
        $stockTransferItemUnit->batch = $batchOne;
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);
        $stockTransferItemBatch->batch = $batchOne;

        [$admin, $request] = setRequestUserForAdmin();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 10,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batchOne->id,
            'quantity' => 5,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $product = commonGetProductDetails();

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getInventoriesByProductIds')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnits')
                ->times(2);
        });

        $this->mock(StockTransferItemBatchQueries::class, function ($mock): void {
            $mock->shouldReceive('increaseQuantity')
                ->once();
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batchOne, $batchSecond): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with([$batchOne->number, $batchSecond->number], 1)
                ->andReturn(new Collection([$batchOne, $batchSecond]));
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->updateDiscrepancySourceInventory(
            $stockTransfer,
            $validatedData,
            $request->user(),
            1,
            collect([$product]),
            collect([$batchOne, $batchSecond])
        );
    }
);

test(
    'the updateDiscrepancySourceInventory method reverts inventory of the normal products when discrepancy type is shortage',
    function (): void {
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => null,
                        'quantity' => null,
                    ],
                ],
            ]],
        ];

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 5,
            'received_quantity' => 4,
            'discrepancy_type' => null,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->units = collect([$stockTransferItemUnit]);

        [$admin, $request] = setRequestUserForAdmin();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 10,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $product = commonGetProductDetails(false);

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('revertInventoryAsPerStockTransfer')
                ->once();
        });

        $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseQuantity')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->updateDiscrepancySourceInventory(
            $stockTransfer,
            $validatedData,
            $request->user(),
            1,
            collect([$product]),
            collect([])
        );
    }
);

test(
    'the updateDiscrepancySourceInventory method move source location inventory in transit for normal products when discrepancy type is exceed',
    function (): void {
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => null,
                        'quantity' => null,
                    ],
                ],
            ]],
        ];

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 5,
            'received_quantity' => 10,
            'discrepancy_type' => StockTransferDiscrepancyTypes::POSITIVE->value,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->units = collect([$stockTransferItemUnit]);

        [$admin, $request] = setRequestUserForAdmin();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 10,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $product = commonGetProductDetails(false);

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getInventoriesByProductIds')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByNumbers')
                ->once();
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnits')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->updateDiscrepancySourceInventory(
            $stockTransfer,
            $validatedData,
            $request->user(),
            1,
            collect([$product]),
            collect([])
        );
    }
);

test(
    'the updateDiscrepancySourceInventory method move source location inventory in transit for additional items received when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [],
        ];

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem1 = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 5,
            'received_quantity' => 5,
        ]);

        $stockTransferItem2 = StockTransferItem::factory()->make([
            'id' => 2,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 2,
            'package_type_id' => null,
            'is_extra_item' => true,
            'quantity' => 5,
            'received_quantity' => 5,
        ]);

        $stockTransferItemUnit1 = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem1->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransferItemUnit2 = StockTransferItemUnit::factory()->make([
            'id' => 2,
            'stock_transfer_item_id' => $stockTransferItem2->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransfer->items = collect([$stockTransferItem1, $stockTransferItem2]);
        $stockTransferItem1->units = collect([$stockTransferItemUnit1]);
        $stockTransferItem2->units = collect([$stockTransferItemUnit2]);

        [$admin, $request] = setRequestUserForAdmin();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 2,
            'location_id' => 1,
            'stock' => 10,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $product1 = commonGetProductDetails(false);
        $product2 = commonGetProductDetails(false);
        $product2->id = 2;

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getInventoriesByProductIds')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnits')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->updateDiscrepancySourceInventory(
            $stockTransfer,
            $validatedData,
            $request->user(),
            1,
            collect([$product1, $product2]),
            collect([])
        );
    }
);

test(
    'the updateDiscrepancySourceInventory method move source location inventory in transit for additional items received when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [],
        ];

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem1 = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 5,
            'received_quantity' => 5,
        ]);

        $stockTransferItem2 = StockTransferItem::factory()->make([
            'id' => 2,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 2,
            'package_type_id' => null,
            'is_extra_item' => true,
            'quantity' => 5,
            'received_quantity' => 5,
        ]);

        $stockTransferItemUnit1 = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem1->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransferItemUnit2 = StockTransferItemUnit::factory()->make([
            'id' => 2,
            'stock_transfer_item_id' => $stockTransferItem2->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransfer->items = collect([$stockTransferItem1, $stockTransferItem2]);
        $stockTransferItem1->units = collect([$stockTransferItemUnit1]);
        $stockTransferItem2->units = collect([$stockTransferItemUnit2]);

        [$admin, $request] = setRequestUserForAdmin();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 2,
            'location_id' => 1,
            'stock' => 10,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);

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

        $product1 = commonGetProductDetails(true);
        $product2 = commonGetProductDetails(true);
        $product2->id = 2;

        $product1->masterProduct = $masterProduct;
        $product2->masterProduct = $masterProduct;

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getInventoriesByProductIds')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnits')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->updateDiscrepancySourceInventory(
            $stockTransfer,
            $validatedData,
            $request->user(),
            1,
            collect([$product1, $product2]),
            collect([])
        );
    }
);

test('the getStoresAndWarehouses method returns stores and warehouses', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location1 = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);
    $locations = collect([$location]);
    $locations1 = collect([$location1]);

    $this->mock(LocationQueries::class, function ($mock) use ($locations, $locations1): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->andReturn($locations);
        $mock->shouldReceive('getWithBasicColumnsOfWarehouse')
            ->once()
            ->andReturn($locations1);
    });

    $stockTransferService = new StockTransferService();

    $response = $stockTransferService->getStoresAndWarehouses(1);

    $this->assertEquals([$locations, $locations1], $response);
});

test(
    'the prepareActiveBatchesProductsAndInventories method returns proper response as expected when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $transferItems = [
            'transfer_items' => [
                'product_id' => 1,
                'transfer_stock' => 10,
                'batch_details' => [
                    [
                        'batch_number' => 'a123',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];

        $product = commonGetProductDetails(true);
        $product->is_non_inventory = false;

        $stockTransferData = new StockTransferData(1, 1, null, null, null, null, 'test', null, $transferItems);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'a123',
        ]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
        ]);

        $products = collect([$product->toArray()]);
        $derivatives = collect([$derivative]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn($products);
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivatives): void {
            $mock->shouldReceive('getByUnitOfMeasureIds')
                ->once()
                ->andReturn($derivatives);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventory): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn(collect([$sourceInventory]));
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->prepareActiveBatchesProductsAndInventories(
            [1],
            1,
            $stockTransferData->source_location_id
        );

        $this->assertEquals(
            [$products, new Collection([$batch]), collect([$sourceInventory]), $derivatives],
            $response
        );
    }
);

test(
    'the prepareActiveBatchesProductsAndInventories method returns proper response as expected when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $transferItems = [
            'transfer_items' => [
                'product_id' => 1,
                'transfer_stock' => 10,
                'batch_details' => [
                    [
                        'batch_number' => 'a123',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];

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

        $product = commonGetProductDetails();

        $product->masterProduct = $masterProduct;

        $stockTransferData = new StockTransferData(1, 1, null, null, null, null, 'test', null, $transferItems);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'a123',
        ]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
        ]);

        $products = collect([$product]);
        $derivatives = collect([$derivative]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn($products);
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivatives): void {
            $mock->shouldReceive('getByUnitOfMeasureIds')
                ->once()
                ->andReturn($derivatives);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventory): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn(collect([$sourceInventory]));
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->prepareActiveBatchesProductsAndInventories(
            [1],
            1,
            $stockTransferData->source_location_id
        );

        $this->assertEquals(
            [$products, new Collection([$batch]), collect([$sourceInventory]), $derivatives],
            $response
        );
    }
);

test('the prepareStockTransferDetails method returns proper response as expected', function (): void {
    $transferItems = [
        'transfer_items' => [
            'product_id' => 1,
            'transfer_stock' => 10,
            'batch_details' => [
                [
                    'batch_number' => 'a123',
                    'quantity' => 10,
                ],
            ],
        ],
    ];
    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);
    $sequence = Sequence::factory()->make([
        'location_id' => 1,
    ]);
    $sequence->location = new Location();
    $stockTransferData = new StockTransferData(
        1,
        1,
        '2022-01-01',
        null,
        null,
        null,
        'test',
        null,
        $transferItems['transfer_items']
    );
    $this->mock(StockTransferAverageLeadDaysQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByLocation')
            ->once()
            ->andReturn(1);
    });

    $stockTransferService = new StockTransferService();
    $response = $stockTransferService->prepareStockTransferDetails(
        $stockTransferData,
        1,
        $admin,
        SequenceTypes::TO->value,
        $sequence,
        null
    );
    expect($response)
        ->toHaveKeys([
            'company_id', 'stock_transfer_average_lead_day_id', 'source_location_id',  'destination_location_id', 'transfer_date', 'requested_by_type', 'requested_by_id',  'reference_number', 'stock_transfer_reason_id', 'status', 'created_by_location_id', 'transfer_type', 'request_order_number', 'transfer_order_number',
        ]);
});

test('the prepareStockTransferDetailsForUpdate method returns proper response as expected', function (): void {
    $transferItems = [
        'transfer_items' => [
            'product_id' => 1,
            'transfer_stock' => 10,
            'batch_details' => [
                [
                    'batch_number' => 'a123',
                    'quantity' => 10,
                ],
            ],
        ],
    ];
    $stockTransferData = new StockTransferData(
        1,
        1,
        '2022-01-01',
        null,
        null,
        null,
        'test',
        null,
        $transferItems['transfer_items']
    );
    $stockTransferService = new StockTransferService();
    $response = $stockTransferService->prepareStockTransferDetailsForUpdate($stockTransferData);
    expect($response)
        ->toHaveKeys([
            'source_location_id',  'destination_location_id', 'transfer_date', 'require_date', 'attention', 'reference_number', 'remarks', 'stock_transfer_reason_id',
        ]);
});

test('It call addNew query method of the stock transfer item query class', function (): void {
    $transferItems = [
        'transfer_items' => [
            [
                'product_id' => 1,
                'transfer_stock' => 10,
                'remarks' => null,
                'batch_details' => [
                    [
                        'batch_number' => 'a123',
                        'quantity' => 10,
                    ],
                ],
            ],
        ],
    ];

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::DRAFT->value,
    ]);

    [$admin, $request] = setRequestUserForAdmin();

    $this->mock(StockTransferItemQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferData = new StockTransferData(
        1,
        1,
        null,
        null,
        null,
        null,
        'test',
        null,
        $transferItems['transfer_items']
    );

    $stockTransferService = new StockTransferService();

    $stockTransferService->saveStockTransferItems(
        $stockTransferData,
        $stockTransfer->id,
        $admin,
        StatusTypes::DRAFT->value,
        collect([])
    );
});

test('the getStocks method returns source & destination inventory of the product', function (): void {
    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::DRAFT->value,
    ]);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => '5',
        'received_quantity' => null,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);

    $sourceInventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 5,
    ]);

    $destinationInventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 5,
    ]);

    $sourceInventories = collect([$sourceInventory->toArray()]);
    $destinationInventories = collect([$destinationInventory->toArray()]);

    $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories, $destinationInventories): void {
        $mock->shouldReceive('getInventoriesByProductIds')
            ->once()
            ->andReturn($sourceInventories);
        $mock->shouldReceive('getInventoriesByProductIds')
            ->once()
            ->andReturn($destinationInventories);
    });

    $stockTransferService = new StockTransferService();

    $response = $stockTransferService->getStocks($stockTransfer);
    $this->assertEquals([$sourceInventories, $destinationInventories], $response);
});

test(
    'the fetchProductsAndSourceInventories method returns proper response as expected when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '5',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory->toArray()]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
        ]);

        $product = commonGetProductDetails(true);
        $product->is_non_inventory = false;
        $products = collect([$product->toArray()]);
        $derivatives = collect([$derivative]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'a123',
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn($products);
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivatives): void {
            $mock->shouldReceive('getByUnitOfMeasureIds')
                ->once()
                ->andReturn($derivatives);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->fetchProductsAndSourceInventories($stockTransfer, 1);
        $this->assertEquals([$products, $sourceInventories, new Collection([$batch])], $response);
    }
);

test(
    'the fetchProductsAndSourceInventories method returns proper response as expected when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '5',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory->toArray()]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
        ]);

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

        $product = commonGetProductDetails();

        $product->masterProduct = $masterProduct;

        $products = collect([$product]);
        $derivatives = collect([$derivative]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'a123',
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn($products);
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivatives): void {
            $mock->shouldReceive('getByUnitOfMeasureIds')
                ->once()
                ->andReturn($derivatives);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->fetchProductsAndSourceInventories($stockTransfer, 1);
        $this->assertEquals([$products, $sourceInventories, new Collection([$batch])], $response);
    }
);

test(
    'the fetchProductsWithArchivedAndSourceInventories method returns proper response as expected when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '5',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory->toArray()]);

        $product = commonGetProductDetails(true);
        $product->status = false;
        $product->is_non_inventory = false;
        $products = collect([$product->toArray()]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'a123',
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getProductsWithArchivedByIds')
                ->once()
                ->andReturn($products);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->fetchProductsWithArchivedAndSourceInventories($stockTransfer, 1);
        $this->assertEquals([$products, $sourceInventories, new Collection([$batch])], $response);
    }
);

test(
    'the fetchProductsWithArchivedAndSourceInventories method returns proper response as expected when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '5',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory->toArray()]);

        $product = commonGetProductDetails();
        $product->status = false;

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

        $products = collect([$product]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'a123',
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getProductsWithArchivedByIds')
                ->once()
                ->andReturn($products);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->fetchProductsWithArchivedAndSourceInventories($stockTransfer, 1);
        $this->assertEquals([$products, $sourceInventories, new Collection([$batch])], $response);
    }
);

test('the prepareLocationIdAndTransferType method returns store id and transfer type', function (): void {
    $stockTransferData = new StockTransferData(1, 1, null, null, null, null, 'test', null, [], 'request_order');

    $stockTransferService = new StockTransferService();

    $response = $stockTransferService->prepareLocationIdAndTransferType($stockTransferData);
    $this->assertEquals([SequenceTypes::RO->value, 1], $response);
});

test(
    'the saveStockTransferItemAndBatchRecords method call the addNew method of stock transfer item query class when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
        ]);

        $transferItems = [
            'transfer_items' => [
                'product_id' => 1,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 10,
                'remarks' => 'test',
                'batch_details' => [
                    [
                        'batch_number' => 'a123',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];

        $stockTransferData = new StockTransferData(
            1,
            1,
            null,
            null,
            null,
            null,
            'test',
            null,
            $transferItems,
            'request_order'
        );

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '5',
            'received_quantity' => null,
        ]);

        $product = commonGetProductDetails(false);
        $product->is_non_inventory = true;

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($stockTransferItem);
        });

        $this->mock(StockTransferItemTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->saveStockTransferItemAndBatchRecords(
            $stockTransferData,
            1,
            collect([$product]),
            1,
            $admin,
            StatusTypes::DRAFT->value,
            collect([$derivative])
        );

        $this->assertTrue(true);
    }
);

test(
    'the saveStockTransferItemAndBatchRecords method call the addNew method of stock transfer item query class when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
        ]);

        $transferItems = [
            'transfer_items' => [
                'product_id' => 1,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 10,
                'remarks' => 'test',
                'batch_details' => [
                    [
                        'batch_number' => 'a123',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];

        $stockTransferData = new StockTransferData(
            1,
            1,
            null,
            null,
            null,
            null,
            'test',
            null,
            $transferItems,
            'request_order'
        );

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '5',
            'received_quantity' => null,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'a123',
        ]);

        $product = commonGetProductDetails(false);

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

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
            ->once()
                ->andReturn(new Collection([$batch]));
        });

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($stockTransferItem);
        });

        $this->mock(StockTransferItemTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->saveStockTransferItemAndBatchRecords(
            $stockTransferData,
            1,
            collect([$product]),
            1,
            $admin,
            StatusTypes::DRAFT->value,
            collect([$derivative])
        );

        $this->assertTrue(true);
    }
);

test(
    'the saveStockTransferItemAndBatchRecords method call the addNew method of stock transfer item and batch query class when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $transferItems = [
            'transfer_items' => [
                'product_id' => 1,
                'transfer_stock' => 10,
                'has_batch' => true,
                'remarks' => 'test',
                'batch_details' => [
                    [
                        'batch_number' => 'a123',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => 1,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'a123',
        ]);

        $stockTransferData = new StockTransferData(
            1,
            1,
            null,
            null,
            null,
            null,
            'test',
            null,
            $transferItems,
            'request_order'
        );

        $product = commonGetProductDetails(true);
        $product->is_non_inventory = false;

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($stockTransferItem);
        });

        $this->mock(StockTransferItemTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $this->mock(StockTransferItemBatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->saveStockTransferItemAndBatchRecords(
            $stockTransferData,
            1,
            collect([$product]),
            1,
            $admin,
            StatusTypes::DRAFT->value,
            collect([])
        );

        $this->assertTrue(true);
    }
);

test(
    'the saveStockTransferItemAndBatchRecords method call the addNew method of stock transfer item and batch query class when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $transferItems = [
            'transfer_items' => [
                'product_id' => 1,
                'transfer_stock' => 10,
                'has_batch' => true,
                'remarks' => 'test',
                'batch_details' => [
                    [
                        'batch_number' => 'a123',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => 1,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'a123',
        ]);

        $stockTransferData = new StockTransferData(
            1,
            1,
            null,
            null,
            null,
            null,
            'test',
            null,
            $transferItems,
            'request_order'
        );

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

        $product = commonGetProductDetails();

        $product->masterProduct = $masterProduct;

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($stockTransferItem);
        });

        $this->mock(StockTransferItemTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new Collection([$batch]));
        });

        $this->mock(StockTransferItemBatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $response = $stockTransferService->saveStockTransferItemAndBatchRecords(
            $stockTransferData,
            1,
            collect([$product]),
            1,
            $admin,
            StatusTypes::DRAFT->value,
            collect([])
        );

        $this->assertTrue(true);
    }
);

test(
    'the markAsOpen method call while transfer order and respective query class calls when product variant false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

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

        $product = commonGetProductDetails($batch = false);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
        ]);

        $derivatives = collect([$derivative]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('updateStatus')
                ->once();
            $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('removeReservationStock')
                ->once();

            $mock->shouldReceive('addTransitStock')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsOpen($stockTransfer->id, $companyId, StatusTypes::DRAFT->value, $admin);
    }
);

test(
    'the markAsOpen method call while transfer order and respective query class calls when product variant true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

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

        $product = commonGetProductDetails($batch = false);

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

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
        ]);

        $derivatives = collect([$derivative]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('updateStatus')
                ->once();
            $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('removeReservationStock')
                ->once();

            $mock->shouldReceive('addTransitStock')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsOpen($stockTransfer->id, $companyId, StatusTypes::DRAFT->value, $admin);
    }
);

test('the markAsOpen method call while request order and respective query class calls', function (): void {
    $companyId = 1;
    [$admin, $request] = setRequestUserForAdmin();

    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
        'source_location_id' => $storeOne->id,
        'destination_location_id' => $storeTwo,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::DRAFT->value,
        'request_order_number' => 'B123',
    ]);

    $product = commonGetProductDetails($batch = false);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => $product->id,
        'package_type_id' => null,
        'quantity' => '50',
        'received_quantity' => null,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);

    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    $sourceInventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'location_id' => 1,
        'stock' => 5,
    ]);

    $sourceInventories = collect([$sourceInventory]);

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getByIdWithItemsAndBatches')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('updateStatus')
            ->once();
        $mock->shouldReceive('loadSourceLocationStoreAndStoreManagers')
            ->once()
            ->andReturn($stockTransfer);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
        $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
            ->once()
            ->andReturn($sourceInventories);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getActiveInventoryProductsByIds')
            ->once()
            ->andReturn(collect([$product]));
    });

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferService = new StockTransferService();

    $stockTransferService->markAsOpen($stockTransfer->id, $companyId, StatusTypes::DRAFT->value, $admin);
});

test(
    'the markAsOpen method call while transfer order with batch and respective query class calls when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::TRANSFER_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
            'request_order_number' => 'B123',
        ]);

        $product = commonGetProductDetails();

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '500',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsOpen($stockTransfer->id, $companyId, StatusTypes::DRAFT->value, $admin);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'the markAsOpen method call while transfer order with batch and respective query class calls when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::TRANSFER_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
            'request_order_number' => 'B123',
        ]);

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

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '500',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsOpen($stockTransfer->id, $companyId, StatusTypes::DRAFT->value, $admin);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'the markAsOpen method call while transfer order with multiple item batches and respective query class calls when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::TRANSFER_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
            'request_order_number' => 'B123',
        ]);

        $product = commonGetProductDetails();

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 2,
            'batch_id' => $batch->id,
            'quantity' => 2,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsOpen($stockTransfer->id, $companyId, StatusTypes::DRAFT->value, $admin);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'the markAsOpen method call while transfer order with multiple item batches and respective query class calls when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::TRANSFER_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
            'request_order_number' => 'B123',
        ]);

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

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 2,
            'batch_id' => $batch->id,
            'quantity' => 2,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsOpen($stockTransfer->id, $companyId, StatusTypes::DRAFT->value, $admin);
    }
)->throws(RedirectBackWithErrorException::class);

test('the markAsCancelled method call when draft status and respective query class calls', function (): void {
    $companyId = 1;
    [$admin, $request] = setRequestUserForAdmin();

    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'source_location_id' => $storeOne->id,
        'destination_location_id' => $storeTwo,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::DRAFT->value,
    ]);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => '50',
        'received_quantity' => null,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);
    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StockTransferInventoryService::class, function ($mock): void {
        $mock->shouldReceive('revertReservedStock')
            ->once();
    });

    $stockTransferService = new StockTransferService();

    $stockTransferService->markAsCancelled(
        $stockTransfer->id,
        $companyId,
        StatusTypes::DRAFT->value,
        $admin,
        'remarks'
    );
});

test(
    'the markAsCancelled method call when open status when transfer order and respective query class calls',
    function (): void {
        [$admin, $request] = setRequestUserForAdmin();

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
            'status' => StatusTypes::OPEN->value,
            'transfer_order_number' => 'A23123',
        ]);

        $product = commonGetProductDetails($batch = false);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->units = collect([$stockTransferItemUnit]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('updateStatus')
                ->once();
            $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('revertInventoryAsPerStockTransfer')
                ->once();
        });

        $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseQuantity')
                ->once();
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(TransitStockQueries::class, function ($mock): void {
            $mock->shouldReceive('deleteAffectedBy')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsCancelled($stockTransfer->id, $companyId, StatusTypes::OPEN->value, $admin, null);
    }
);

test(
    'the markAsCancelled method call when open status where request order and respective query class calls',
    function (): void {
        $companyId = 1;
        [$admin, $request] = setRequestUserForAdmin();

        [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::OPEN->value,
            'request_order_number' => 'D12312',
        ]);

        $product = commonGetProductDetails($batch = false);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->units = collect([$stockTransferItemUnit]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('updateStatus')
                ->once();
            $mock->shouldReceive('loadSourceLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('revertReservedStock')
                ->once();
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsCancelled($stockTransfer->id, $companyId, StatusTypes::OPEN->value, $admin, null);
    }
);

test(
    'the markAsCancelled method call when shipped status where request/transfer order and respective query class calls',
    function (): void {
        [$admin, $request] = setRequestUserForAdmin();

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
            'status' => StatusTypes::SHIPPED->value,
            'transfer_order_number' => 'A23123',
        ]);

        $product = commonGetProductDetails($batch = false);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => null,
            'quantity' => 5,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->units = collect([$stockTransferItemUnit]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('updateStatus')
                ->once();
            $mock->shouldReceive('loadSourceLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('revertInventoryAsPerStockTransfer')
                ->once();
        });

        $this->mock(StockTransferItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseQuantity')
                ->once();
        });

        $this->mock(TransitStockQueries::class, function ($mock): void {
            $mock->shouldReceive('deleteAffectedBy')
                ->once();
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->markAsCancelled($stockTransfer->id, $companyId, StatusTypes::OPEN->value, $admin, null);
    }
);

test('the markAsDiscrepancy method call and respective query class calls', function (): void {
    $companyId = 1;
    [$admin, $request] = setRequestUserForAdmin();

    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'source_location_id' => $storeOne->id,
        'destination_location_id' => $storeTwo,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::RECEIVED->value,
        'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
        'request_order_number' => 'E123',
    ]);

    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getLocationAndStatusById')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('updateStatus')
            ->once();
        $mock->shouldReceive('loadSourceLocationStoreAndStoreManagers')
            ->once()
            ->andReturn($stockTransfer);
    });

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferService = new StockTransferService();

    $stockTransferService->markAsDiscrepancy($stockTransfer->id, $companyId, StatusTypes::RECEIVED->value, $admin);
});

test(
    'the markAsShippedOrTransit method call and respective query class calls when product variant is false',
    function ($transferType, $status): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: '1'
        );

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => $transferType,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => $status,
            'transit_location_id' => $stockTransferShippedData->location_id,
        ]);

        $product = commonGetProductDetails($batch = false);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $storeThree = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $storeThree->storeManagers = collect([$storeManagerOne]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;
        $stockTransfer->transitLocation = $storeThree;

        $sequence = setSequenceAndStoreForService($storeOne);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('loadTransitLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('updateShippedAndTransferNumber')
                ->once();
        });

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        if ($transferType === StockTransferTypes::REQUEST_ORDER->value) {
            $this->mock(StockTransferInventoryService::class, function ($mock): void {
                $mock->shouldReceive('removeReservationStock')
                    ->once();

                $mock->shouldReceive('addTransitStock')
                    ->once();
            });
        }

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->times(2)
                ->andReturn($sequence);
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->markAsShippedOrTransit(
            $stockTransferShippedData,
            $stockTransfer->id,
            $companyId,
            $admin
        );
    }
)->with([
    [StockTransferTypes::TRANSFER_ORDER->value, StatusTypes::OPEN->value],
    [StockTransferTypes::REQUEST_ORDER->value, StatusTypes::APPROVED->value],
    [StockTransferTypes::REQUEST_ORDER->value, StatusTypes::TRANSIT->value],
]);

test(
    'the markAsShippedOrTransit method call and respective query class calls when product variant is true',
    function ($transferType, $status): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: '1'
        );

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => $transferType,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => $status,
            'transit_location_id' => $stockTransferShippedData->location_id,
        ]);

        $product = commonGetProductDetails($batch = false);

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

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $storeThree = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $storeThree->storeManagers = collect([$storeManagerOne]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;
        $stockTransfer->transitLocation = $storeThree;

        $sequence = setSequenceAndStoreForService($storeOne);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('loadTransitLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('updateShippedAndTransferNumber')
                ->once();
        });

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        if ($transferType === StockTransferTypes::REQUEST_ORDER->value) {
            $this->mock(StockTransferInventoryService::class, function ($mock): void {
                $mock->shouldReceive('removeReservationStock')
                    ->once();

                $mock->shouldReceive('addTransitStock')
                    ->once();
            });
        }

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->times(2)
                ->andReturn($sequence);
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->markAsShippedOrTransit(
            $stockTransferShippedData,
            $stockTransfer->id,
            $companyId,
            $admin
        );
    }
)->with([
    [StockTransferTypes::TRANSFER_ORDER->value, StatusTypes::OPEN->value],
    [StockTransferTypes::REQUEST_ORDER->value, StatusTypes::APPROVED->value],
    [StockTransferTypes::REQUEST_ORDER->value, StatusTypes::TRANSIT->value],
]);

test(
    'the markAsShippedOrTransit method throws an exception when transfer batch quantity more than available batch unit quantity when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: '1'
        );

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::APPROVED->value,
            'transit_location_id' => $stockTransferShippedData->location_id,
        ]);

        $product = commonGetProductDetails();

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '500',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $storeThree = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $storeThree->storeManagers = collect([$storeManagerOne]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;
        $stockTransfer->transitLocation = $storeThree;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->markAsShippedOrTransit(
            $stockTransferShippedData,
            $stockTransfer->id,
            $companyId,
            $admin
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'the markAsShippedOrTransit method throws an exception when transfer batch quantity more than available batch unit quantity when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: '1'
        );

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::APPROVED->value,
            'transit_location_id' => $stockTransferShippedData->location_id,
        ]);

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

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '500',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $storeThree = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $storeThree->storeManagers = collect([$storeManagerOne]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;
        $stockTransfer->transitLocation = $storeThree;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->markAsShippedOrTransit(
            $stockTransferShippedData,
            $stockTransfer->id,
            $companyId,
            $admin
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'the markAsShippedOrTransit method throws an exception when multiple item batches quantity more than available batch unit quantity when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: '1'
        );

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::APPROVED->value,
            'transit_location_id' => $stockTransferShippedData->location_id,
        ]);

        $product = commonGetProductDetails();

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $storeThree = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $storeThree->storeManagers = collect([$storeManagerOne]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;
        $stockTransfer->transitLocation = $storeThree;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 2,
            'batch_id' => $batch->id,
            'quantity' => 2,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->markAsShippedOrTransit(
            $stockTransferShippedData,
            $stockTransfer->id,
            $companyId,
            $admin
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'the markAsShippedOrTransit method throws an exception when multiple item batches quantity more than available batch unit quantity when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        [$admin,
            $request] = setRequestUserForAdmin();

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: '1'
        );

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::APPROVED->value,
            'transit_location_id' => $stockTransferShippedData->location_id,
        ]);

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

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => 'A12345',
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => $batch->id,
            'quantity' => '250',
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $storeThree = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $storeThree->storeManagers = collect([$storeManagerOne]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;
        $stockTransfer->transitLocation = $storeThree;

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $sourceInventory->id,
            'purchase_amount_id' => 2,
            'batch_id' => $batch->id,
            'quantity' => 2,
        ]);

        $sourceInventory->inventoryUnits = collect([$inventoryUnit]);

        $sourceInventories = collect([$sourceInventory]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($sourceInventories): void {
            $mock->shouldReceive('getByProductIdsAndLocationWithInventoryUnits')
                ->once()
                ->andReturn($sourceInventories);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsByIds')
                ->once()
                ->andReturn(collect([$product]));
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->markAsShippedOrTransit(
            $stockTransferShippedData,
            $stockTransfer->id,
            $companyId,
            $admin
        );
    }
)->throws(RedirectBackWithErrorException::class);

test('the markAsReceived method call and respective query class calls', function (): void {
    $receiveDate = Carbon::now()->format('Y-m-d');
    [$admin, $request] = setRequestUserForAdmin();

    $companyId = 1;

    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
        'source_location_id' => $storeOne->id,
        'destination_location_id' => $storeTwo,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::SHIPPED->value,
        'request_order_number' => 'G12321',
    ]);

    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getLocationAndStatusById')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('updateReceivedDateAndStatus')
            ->once();
        $mock->shouldReceive('loadSourceLocationStoreAndStoreManagers')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('isTransitTargetAchieved')
            ->andReturn(true);
        $mock->shouldReceive('addIsTransitTargetAchieved');
    });

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferService = new StockTransferService();
    $stockTransferService->markAsReceived($companyId, $stockTransfer->id, $receiveDate, $admin);
});

test(
    'the updateShippingDetailsAndMarkAsApproved method call and respective query class calls when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        [$admin,
            $request] = setRequestUserForAdmin();

        $companyId = 1;

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::SHIPPED->value,
            'request_order_number' => 'e12321',
        ]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $product = commonGetProductDetails();

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $validatedData = collect([
            [
                'id' => $stockTransferItem->id,
                'batch_details' => [
                    [
                        'batch_number' => null,
                        'quantity' => null,
                    ],
                ],
            ],
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => 1,
            'quantity' => '5',
        ]);

        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateShippingDetailsRecordsById')
                ->once();
        });

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('updateStatus')
                ->once();
            $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->updateShippingDetailsAndMarkAsApproved(
            $stockTransfer,
            $validatedData,
            $admin,
            $companyId,
            StatusTypes::APPROVED->value,
            collect([$product]),
            new Collection([$batch])
        );
    }
);

test(
    'the updateShippingDetailsAndMarkAsApproved method call and respective query class calls when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        [$admin,
            $request] = setRequestUserForAdmin();

        $companyId = 1;

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::SHIPPED->value,
            'request_order_number' => 'e12321',
        ]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

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

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $validatedData = collect([
            [
                'id' => $stockTransferItem->id,
                'batch_details' => [
                    [
                        'batch_number' => null,
                        'quantity' => null,
                    ],
                ],
            ],
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
        ]);

        $stockTransferItemBatch = StockTransferItemBatch::factory()->make([
            'id' => 1,
            'stock_transfer_item_id' => $stockTransferItem->id,
            'batch_id' => 1,
            'quantity' => '5',
        ]);

        $stockTransferItem->batches = collect([$stockTransferItemBatch]);

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateShippingDetailsRecordsById')
                ->once();
        });

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('updateStatus')
                ->once();
            $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->updateShippingDetailsAndMarkAsApproved(
            $stockTransfer,
            $validatedData,
            $admin,
            $companyId,
            StatusTypes::APPROVED->value,
            collect([$product]),
            new Collection([$batch])
        );
    }
);

test(
    'the updateRequestOrder method call and respective query class calls when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::SHIPPED->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 1,
            'is_extra_item' => true,
            'received_quantity' => 1,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $items = [
            'product_id' => 1,
            'transfer_stock' => 1,
            'remarks' => 'asas',
        ];

        $stockTransferRequestOrderData = new StockTransferRequestOrderData(
            1,
            1,
            'attention_test',
            'reference_test',
            'remark_test',
            [
                'transfer_items' => $items,
            ]
        );

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $product = commonGetProductDetails($hasBatch = false);
        $products = collect([$product]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventories = collect([$sourceInventory]);

        [$admin,
            $request] = setRequestUserForAdmin();

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('deleteItemAndBatches')
                ->once();
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($stockTransferItem);
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnitsWithReserved')
                ->once();
        });

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('update')
                ->once();
            $mock->shouldReceive('setUpdatedAt')
                ->once();
            $mock->shouldReceive('getWithItemsAndBatchDetailsById')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferItemTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->updateRequestOrder(
            $stockTransferRequestOrderData,
            $stockTransfer,
            $companyId,
            $admin,
            StatusTypes::OPEN->value,
            $products,
            $inventories,
            collect([])
        );
    }
);

test(
    'the updateRequestOrder method call and respective query class calls when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;

        [$storeOne,
            $storeTwo,
            $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::SHIPPED->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 1,
            'is_extra_item' => true,
            'received_quantity' => 1,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $items = [
            'product_id' => 1,
            'transfer_stock' => 1,
            'remarks' => 'asas',
        ];

        $stockTransferRequestOrderData = new StockTransferRequestOrderData(
            1,
            1,
            'attention_test',
            'reference_test',
            'remark_test',
            [
                'transfer_items' => $items,
            ]
        );

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

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

        $products = collect([$product]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $inventories = collect([$sourceInventory]);

        [$admin,
            $request] = setRequestUserForAdmin();

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('deleteItemAndBatches')
                ->once();
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($stockTransferItem);
        });

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnitsWithReserved')
                ->once();
        });

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('update')
                ->once();
            $mock->shouldReceive('setUpdatedAt')
                ->once();
            $mock->shouldReceive('getWithItemsAndBatchDetailsById')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferItemTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->updateRequestOrder(
            $stockTransferRequestOrderData,
            $stockTransfer,
            $companyId,
            $admin,
            StatusTypes::OPEN->value,
            $products,
            $inventories,
            collect([])
        );
    }
);

test(
    'the reserveStockTransferItemStocks method call and respective query class calls when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $product = commonGetProductDetails($hasBatch = false);
        $products = collect([$product]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory]);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => 1,
            'is_extra_item' => true,
            'received_quantity' => 1,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnitsWithReserved')
                ->once();
        });

        $this->mock(StockTransferQueries::class, function ($mock): void {
            $mock->shouldReceive('setUpdatedAt')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->reserveStockTransferItemStocks($products, $sourceInventories, $stockTransfer);
    }
);

test(
    'the reserveStockTransferItemStocks method call and respective query class calls when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

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

        $product = commonGetProductDetails($hasBatch = false);
        $product->masterProduct = $masterProduct;
        $products = collect([$product]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory]);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => 1,
            'is_extra_item' => true,
            'received_quantity' => 1,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $this->mock(StockTransferInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnitsWithReserved')
                ->once();
        });

        $this->mock(StockTransferQueries::class, function ($mock): void {
            $mock->shouldReceive('setUpdatedAt')
                ->once();
        });

        $stockTransferService = new StockTransferService();
        $stockTransferService->reserveStockTransferItemStocks($products, $sourceInventories, $stockTransfer);
    }
);

test('the closeDiscrepancy method call and respective query class calls', function (): void {
    $companyId = 1;

    $mock = $this->createPartialMock(
        StockTransferService::class,
        ['updateDiscrepancySourceInventory', 'closeTransfer', 'addNotification']
    );

    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
        'source_location_id' => $storeOne->id,
        'destination_location_id' => $storeTwo,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::SHIPPED->value,
        'request_order_number' => 'H123213',
    ]);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => '50',
        'received_quantity' => null,
    ]);

    $stockTransferItemUnit = StockTransferItemUnit::factory()->make([
        'id' => 1,
        'stock_transfer_item_id' => $stockTransferItem->id,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => null,
        'quantity' => 5,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);
    $stockTransferItem->units = collect([$stockTransferItemUnit]);

    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    $product = commonGetProductDetails($hasBatch = false);

    [$admin, $request] = setRequestUserForAdmin();

    $products = collect([$product]);

    $validatedData = [
        'stock_transfer_items' => [[
            'id' => 1,
            'batch_details' => [
                [
                    'batch_number' => null,
                    'quantity' => null,
                ],
            ],
        ]],
    ];

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('loadItemsUnitsAndBatches')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(TransitStockQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteAffectedBy')
            ->once();
    });

    $this->mock(StockTransferInventoryService::class, function ($mock): void {
        $mock->shouldReceive('updateInventoryAsPerStockTransfer')
            ->once();
    });

    $mock->closeDiscrepancy(
        $stockTransfer,
        $validatedData,
        $admin,
        $products,
        $companyId,
        StatusTypes::DISCREPANCY->value,
        collect([])
    );
});

test('the updateAdditionalItems method call and respective query class calls', function (): void {
    $product = commonGetProductDetails($hasBatch = false);
    $additionalItems = [
        [
            'stock_transfer_id' => 1,
            'product_id' => $product->id,
            'has_batch' => false,
            'package_type_id' => 1,
            'quantity' => 1,
            'received_quantity' => 1,
            'package_quantity' => 1,
            'package_total_quantity' => 1,
            'remarks' => 'abcd',
        ],
    ];

    [$admin, $request] = setRequestUserForAdmin();

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::DRAFT->value,
    ]);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => $product->id,
        'package_type_id' => null,
        'quantity' => 1,
        'is_extra_item' => true,
        'received_quantity' => 1,
    ]);

    $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($stockTransferItem);
    });

    $this->mock(StockTransferItemTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StockTransferQueries::class, function ($mock): void {
        $mock->shouldReceive('setUpdatedAtById')
            ->once();
    });

    $stockTransferService = new StockTransferService();
    $stockTransferService->updateAdditionalItems($additionalItems, $admin, collect([]));
});

test(
    'the removeAdditionalItem method call when open status and respective query class calls',
    function (): void {
        $companyId = 1;

        [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::OPEN->value,
        ]);

        $product = commonGetProductDetails($batch = false);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => null,
            'quantity' => '50',
            'received_quantity' => null,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('removeAdditionalItemAndRelations')
                ->once();
        });

        $stockTransferService = new StockTransferService();

        $stockTransferService->removeAdditionalItem($stockTransferItem->id);
    }
);

test('the requestOrderMarkAsRejected method call and respective query class calls', function (): void {
    [$admin, $request] = setRequestUserForAdmin();

    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers(1);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'source_location_id' => $storeOne->id,
        'destination_location_id' => $storeTwo,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::OPEN->value,
        'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
        'request_order_number' => 'D123213',
    ]);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => '50',
        'received_quantity' => null,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);
    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('updateStatus')
            ->once();
        $mock->shouldReceive('loadDestinationLocationStoreAndStoreManagers')
            ->once()
            ->andReturn($stockTransfer);
    });

    $this->mock(StockTransferInventoryService::class, function ($mock): void {
        $mock->shouldReceive('revertReservedStock')
            ->once();
    });

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferService = new StockTransferService();
    $stockTransferService->requestOrderMarkAsRejected($stockTransfer, 1, 1, 1, $admin);
});

test('markAsTransitIn method throw an exception if status is not transit.', function (): void {
    [$admin, $request] = setRequestUserForAdmin();
    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'stock_transfer_reason_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'status' => StatusTypes::OPEN->value,
    ]);

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getStatusById')
            ->once()
            ->andReturn($stockTransfer);
    });

    $stockTransferService = new StockTransferService();
    $stockTransferService->markAsTransitIn(1, 1, 1, $admin);
})->throws(RedirectBackWithErrorException::class);

test('markAsTransitIn method call respective query class.', function (): void {
    [$admin, $request] = setRequestUserForAdmin();
    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'stock_transfer_reason_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'status' => StatusTypes::TRANSIT->value,
    ]);

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getStatusById')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferService = new StockTransferService();
    $stockTransferService->markAsTransitIn(1, 1, 1, $admin);
});

test('markAsTransitOut method throw an exception if status is not transit.', function (): void {
    [$admin, $request] = setRequestUserForAdmin();
    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'stock_transfer_reason_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'status' => StatusTypes::TRANSIT->value,
    ]);

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getStatusById')
            ->once()
            ->andReturn($stockTransfer);
    });

    $stockTransferService = new StockTransferService();
    $stockTransferService->markAsTransitOut(1, 1, 1, $admin);
})->throws(RedirectBackWithErrorException::class);

test('markAsTransitOut method call respective query class.', function (): void {
    [$admin, $request] = setRequestUserForAdmin();
    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'stock_transfer_reason_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'status' => StatusTypes::TRANSIT_IN->value,
    ]);

    $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getStatusById')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    $this->mock(StockTransferTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stockTransferService = new StockTransferService();
    $stockTransferService->markAsTransitOut(1, 1, 1, $admin);
});

test('getAverageAggregateDays method call respective query class.', function (): void {
    $stockTransferAverageLeadDays = StockTransferAverageLeadDays::factory()->make([
        'from_location_id' => 1,
        'to_location_id' => 1,
        'average_days' => 2,
    ]);

    $validateData = [
        'source_location_id' => 1,
        'destination_location_id' => 1,
    ];

    $this->mock(StockTransferAverageLeadDaysQueries::class, function ($mock) use (
        $validateData,
        $stockTransferAverageLeadDays,
    ): void {
        $mock->shouldReceive('getAverageAggregateDays')
            ->once()
            ->with($validateData)
            ->andReturn($stockTransferAverageLeadDays->average_days);
    });

    $this->mock(StockTransferQueries::class, function ($mock): void {
        $mock->shouldReceive('getSuccessRatio')
            ->andReturn(0.00);
    });

    $stockTransferService = new StockTransferService();
    $response = $stockTransferService->getAverageAggregateDays($validateData);
    expect($response['aggregate_average_days'])->toBe($stockTransferAverageLeadDays->average_days);
});

function setSequenceAndStoreForService(Location $location): Sequence
{
    $sequence = Sequence::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $sequence->location = $location;

    return $sequence;
}
