<?php

declare(strict_types=1);

use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\PurchaseAmount;
use App\Models\SerialNumber;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->inventoryUnitQueries = new InventoryUnitQueries();
});

test('Inventory Unit can be added', function (): void {
    $inventory = Inventory::factory()->create();
    $purchaseAmount = PurchaseAmount::factory()->create();
    $goodsReceivedNoteProduct = [
        'quantity' => 10,
    ];

    $this->inventoryUnitQueries->addNew(
        (string) $goodsReceivedNoteProduct['quantity'],
        $inventory->id,
        $purchaseAmount->id,
        null
    );

    $this->assertDatabaseHas('inventory_units', [
        'inventory_id' => $inventory->id,
        'purchase_amount_id' => $purchaseAmount->id,
        'batch_id' => null,
        'quantity' => $goodsReceivedNoteProduct['quantity'],
    ]);
});

test('Inventory unit can be fetched by id', function (): void {
    $inventoryId = Inventory::factory()->create()->id;

    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $inventoryId,
        'quantity' => 20,
    ]);

    $response = $this->inventoryUnitQueries->getById($inventoryUnit->id);

    expect($response->toArray())
        ->toHaveKey('inventory_id', $inventoryUnit->inventory_id)
        ->toHaveKey('quantity', $inventoryUnit->quantity)
        ->toHaveKey('reserved_stock', $inventoryUnit->reserved_stock);
});

test('Inventory units can be fetched by inventory id', function (): void {
    $inventoryId = Inventory::factory()->create()->id;

    InventoryUnit::factory()->create([
        'inventory_id' => $inventoryId,
        'quantity' => 10,
    ]);

    InventoryUnit::factory()->create([
        'inventory_id' => $inventoryId,
        'quantity' => 20,
    ]);

    $response = $this->inventoryUnitQueries->getByInventoryId($inventoryId);

    expect($response->first()->toArray())
        ->toHaveKey('inventory_id', $inventoryId)
        ->toHaveKey('quantity', 10.00);
});

test('Inventory units can be fetched by inventory id and batch id', function (): void {
    $inventoryId = Inventory::factory()->create()->id;
    $batchId = Batch::factory()->create()->id;

    InventoryUnit::factory()->create([
        'inventory_id' => $inventoryId,
        'batch_id' => $batchId,
        'quantity' => 20,
    ]);

    InventoryUnit::factory()->create([
        'inventory_id' => $inventoryId,
        'batch_id' => $batchId,
        'quantity' => 10,
    ]);

    $response = $this->inventoryUnitQueries->getByInventoryBatchId($inventoryId, $batchId);

    expect($response->first()->toArray())
        ->toHaveKey('inventory_id', $inventoryId)
        ->toHaveKey('batch_id', $batchId)
        ->toHaveKey('quantity', 20.00);
});

test(
    'Inventory units can be fetched by inventory id and order by Batch expiry date',
    function (Carbon $batchBExpiryDate, Carbon $batchAExpiryDate): void {
        $inventoryId = Inventory::factory()->create()->id;

        $batchA = Batch::factory()->create([
            'expiry_date' => $batchAExpiryDate,
        ]);

        $batchB = Batch::factory()->create([
            'expiry_date' => $batchBExpiryDate,
        ]);

        InventoryUnit::factory()->create([
            'inventory_id' => $inventoryId,
            'batch_id' => $batchA->id,
            'quantity' => 10,
        ]);

        InventoryUnit::factory()->create([
            'inventory_id' => $inventoryId,
            'batch_id' => $batchB->id,
            'quantity' => 20,
        ]);

        $response = $this->inventoryUnitQueries->getByInventoryIdOrderByBatchExpiryDate($inventoryId);

        expect($response->first()->toArray())
            ->toHaveKey('inventory_id', $inventoryId)
            ->toHaveKey('batch_id', $batchB->id)
            ->toHaveKey('quantity', 20.00);
    }
)->with([
    [Carbon::now(), Carbon::now()->addYear()],
    [Carbon::now()->addYear(), Carbon::now()->addYears(2)],
    [Carbon::now()->subYears(2), Carbon::now()->subYear()],
]);

test('Inventory unit stock can be decreased', function (): void {
    $inventoryUnit = InventoryUnit::factory()->create([
        'quantity' => 10,
    ]);

    $this->inventoryUnitQueries->decreaseStock($inventoryUnit, 10);

    $this->assertDatabaseHas('inventory_units', [
        'id' => $inventoryUnit->id,
        'quantity' => 00.00,
    ]);
});

test('Inventory Unit can be find or create', function (): void {
    $inventory = Inventory::factory()->create();
    $purchaseAmount = PurchaseAmount::factory()->create();

    $response = $this->inventoryUnitQueries->addNewAndGetId($inventory->id, $purchaseAmount->id, null);

    expect($response->first()->toArray())
        ->toHaveKey('inventory_id', $inventory->id)
        ->toHaveKey('batch_id', null)
        ->toHaveKey('purchase_amount_id', $purchaseAmount->id);

    $this->assertDatabaseHas('inventory_units', [
        'inventory_id' => $inventory->id,
        'purchase_amount_id' => $purchaseAmount->id,
        'batch_id' => null,
    ]);
});

test('getByInventoryIdBatchIdAndPurchaseAmountId method can be return InventoryUnit', function (): void {
    $inventoryId = Inventory::factory()->create()->id;
    $batchId = Batch::factory()->create()->id;

    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $inventoryId,
        'batch_id' => $batchId,
        'quantity' => 20,
    ]);

    $response = $this->inventoryUnitQueries->getByInventoryIdBatchIdAndPurchaseAmountId(
        $inventoryId,
        $inventoryUnit->purchase_amount_id,
        $batchId
    );

    expect($response->first()->toArray())
        ->toHaveKey('inventory_id', $inventoryId)
        ->toHaveKey('batch_id', $batchId)
        ->toHaveKey('purchase_amount_id', $inventoryUnit->purchase_amount_id)
        ->toHaveKey('quantity', 20.00);
});

test('Inventory unit stock can be increase', function (): void {
    $inventoryUnit = InventoryUnit::factory()->create([
        'quantity' => 10,
    ]);

    $this->inventoryUnitQueries->increaseStock($inventoryUnit, 10);

    $this->assertDatabaseHas('inventory_units', [
        'id' => $inventoryUnit->id,
        'quantity' => 20.00,
    ]);
});

test('Inventory unit reserved stock can be increase', function (): void {
    $inventoryUnit = InventoryUnit::factory()->create([
        'quantity' => 10,
        'reserved_stock' => 10,
    ]);

    $this->inventoryUnitQueries->increaseReservedStock($inventoryUnit, 5);

    $this->assertDatabaseHas('inventory_units', [
        'id' => $inventoryUnit->id,
        'quantity' => 5.00,
        'reserved_stock' => 15.00,
    ]);
});

test('Inventory unit reserved stock can be decrease', function (): void {
    $inventoryUnit = InventoryUnit::factory()->create([
        'quantity' => 10,
        'reserved_stock' => 10,
    ]);

    $this->inventoryUnitQueries->decreaseReservedStock($inventoryUnit, 5);

    $this->assertDatabaseHas('inventory_units', [
        'id' => $inventoryUnit->id,
        'quantity' => 10.00,
        'reserved_stock' => 5.00,
    ]);
});

test('Inventory unit reserved stock can be revert', function (): void {
    $inventoryUnit = InventoryUnit::factory()->create([
        'quantity' => 10,
        'reserved_stock' => 10,
    ]);

    $this->inventoryUnitQueries->revertReservedStock($inventoryUnit, 5);

    $this->assertDatabaseHas('inventory_units', [
        'id' => $inventoryUnit->id,
        'quantity' => 15.00,
        'reserved_stock' => 5.00,
    ]);
});

test('Inventory unit check serial number with quantity', function (): void {
    $companyId = Company::factory()->create()->id;
    $serialNumber = SerialNumber::factory()->create([
        'company_id' => $companyId,
        'serial_number' => '123456',
    ]);
    $inventoryUnit = InventoryUnit::factory()->create([
        'quantity' => -1,
        'reserved_stock' => 10,
        'serial_number_id' => $serialNumber->id,
    ]);

    $this->inventoryUnitQueries->existsBySerialNumberIdAndInventoryId($serialNumber->id);

    $this->assertDatabaseHas('inventory_units', [
        'id' => $inventoryUnit->id,
        'quantity' => -1.00,
        'reserved_stock' => 10.00,
    ]);
});
