<?php

declare(strict_types=1);

use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Models\InventoryUnit;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemUnit;

test('stock transfer item units can be added', function (): void {
    $stockTransferItemUnitQueries = new StockTransferItemUnitQueries();

    $stockTransferItem = StockTransferItem::factory()->create();
    $inventoryUnit = InventoryUnit::factory()->create();

    $stockTransferItemUnitQueries->addNew(
        $inventoryUnit,
        $stockTransferItem->id,
        $inventoryUnit->inventory_id,
        $stockTransferItem->quantity
    );

    $this->assertDatabaseHas('stock_transfer_item_units', [
        'stock_transfer_item_id' => $stockTransferItem->id,
        'inventory_id' => $inventoryUnit->inventory_id,
        'purchase_amount_id' => $inventoryUnit->purchase_amount_id,
        'batch_id' => $inventoryUnit->batch_id,
        'quantity' => $stockTransferItem->quantity,
    ]);
});

test('it deletes the stock transfer items unit', function (): void {
    $stockTransferItemUnitQueries = new StockTransferItemUnitQueries();

    $stockTransferItemUnit = StockTransferItemUnit::factory()->create();

    $stockTransferItemUnitQueries->delete(collect([$stockTransferItemUnit]));

    $this->assertDatabaseMissing('stock_transfer_item_units', $stockTransferItemUnit->toArray());
});
