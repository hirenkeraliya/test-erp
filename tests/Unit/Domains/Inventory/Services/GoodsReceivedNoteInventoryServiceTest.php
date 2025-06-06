<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\GoodsReceivedNoteInventoryService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Models\Admin;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Inventory;

test('addInventory method calls respective queries methods as expected', function (): void {
    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);
    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
    ]);

    $goodsReceivedNoteProduct = seedProductForInventoryService();

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

    $goodsReceivedNoteInventoryService = new GoodsReceivedNoteInventoryService();
    $goodsReceivedNoteInventoryService->addInventory($goodsReceivedNoteProduct, $admin, 1, 1, 1, 1);
});

function seedProductForInventoryService(): GoodsReceivedNoteProduct
{
    return GoodsReceivedNoteProduct::factory()->make([
        'id' => 1,
        'goods_received_note_id' => 1,
        'product_id' => 1,
        'batch_id' => 1,
        'purchase_amount_id' => 1,
        'location_id' => 1,
    ]);
}

test('rollbackInventoryForGRNCancellation method calls respective queries methods as expected', function (): void {
    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
    ]);

    $goodsReceivedNoteProduct = seedProductForInventoryService();

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getInventoryBy')
            ->with(1, 1)
            ->once()
            ->andReturn($inventory);
        $mock->shouldReceive('decreaseStock')
            ->once();
    });

    $this->mock(InventoryService::class, function ($mock): void {
        $mock->shouldReceive('updateInventoryUnit')
        ->once();
    });

    $this->mock(InventoryUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $goodsReceivedNoteInventoryService = new GoodsReceivedNoteInventoryService();
    $goodsReceivedNoteInventoryService->rollbackInventoryForGRNCancellation($goodsReceivedNoteProduct, $admin, 1);
});
