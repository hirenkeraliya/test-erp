<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\TransitStock\TransitStockQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\StockTransferItem;
use App\Models\TransitStock;

beforeEach(function (): void {
    $this->transitStockQueries = new TransitStockQueries();
});

test('Transit stock can be added', function (): void {
    $inventory = Inventory::factory()->create();
    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $inventory->id,
    ]);
    $stockTransferItem = StockTransferItem::factory()->create();

    $records = [
        'inventory_id' => $inventory->id,
        'inventory_unit_id' => $inventoryUnit->id,
        'affected_by_id' => $stockTransferItem->id,
        'affected_by_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
        'quantity' => 10.0,
        'notes' => null,
    ];

    $this->transitStockQueries->addNew($records);

    $this->assertDatabaseHas('transit_stocks', $records);
});

test('delete method delete the transit stock', function (): void {
    $transitStock = TransitStock::factory()->create();
    $this->transitStockQueries->deleteAffectedBy($transitStock->affected_by_id, $transitStock->affected_by_type);

    $this->assertSoftDeleted('transit_stocks', [
        'id' => $transitStock->id,
    ]);
});

test(
    'updateInventoryIdDuringProductMerge method update inventory id and notes for the merge product process',
    function (): void {
        $transitStock = TransitStock::factory()->create([
            'notes' => null,
        ]);
        $oldTransitInventoryId = $transitStock->inventory_id;
        $inventory = Inventory::factory()->create();

        $this->assertDatabaseHas(TransitStock::class, [
            'id' => $transitStock->getKey(),
            'inventory_id' => $oldTransitInventoryId,
            'notes' => null,
        ]);

        $this->transitStockQueries->updateInventoryIdDuringProductMerge($transitStock, $inventory->getKey());

        $this->assertDatabaseHas(TransitStock::class, [
            'id' => $transitStock->getKey(),
            'inventory_id' => $inventory->getKey(),
            'notes' => ' | Product Merge. old Inventory Id: ' . $oldTransitInventoryId,
        ]);
    }
);
