<?php

declare(strict_types=1);

use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\SaleItem;
use App\Models\SaleItemUnit;

beforeEach(function (): void {
    $this->saleItemUnitQueries = new SaleItemUnitQueries();
});

test('new sale item unit can be added', function (): void {
    $saleItem = SaleItem::factory()->create();

    $inventory = Inventory::factory()->create();

    $inventoryUnit = InventoryUnit::factory()->create();

    $this->saleItemUnitQueries->addNew($saleItem, $inventory->id, $inventoryUnit, 10.00);

    $this->assertDatabaseHas('sale_item_units', [
        'sale_item_id' => $saleItem->id,
        'inventory_id' => $inventory->id,
        'purchase_amount_id' => $inventoryUnit->purchase_amount_id,
        'batch_id' => $inventoryUnit->batch_id,
        'quantity' => 10.00,
    ]);
});

test('incrementReturnedQuantity method updates the returned quantity', function (): void {
    $saleItem = SaleItemUnit::factory()->create([
        'returned_quantity' => 5,
    ]);

    $this->saleItemUnitQueries->incrementReturnedQuantity($saleItem, 10.00);

    $this->assertDatabaseHas('sale_item_units', [
        'id' => $saleItem->id,
        'returned_quantity' => 15.00,
    ]);
});
