<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\OrderInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Domains\Order\Services\SaveOrderDetailsService;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreManager;

beforeEach(function (): void {
    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'has_batch' => true,
    ]);

    $this->product2 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'has_batch' => false,
    ]);

    $this->cartOrderItems = [
        'id' => $this->product->id,
        'price' => '10.00',
        'quantity' => '10',
        'promotion_id' => 1,
        'batch_details' => [
            [
                'batch_number' => 'test',
                'batch_expiry_date' => '2024-12-01',
                'quantity' => '10',
            ],
        ],
    ];

    $this->batch = Batch::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'product_id' => 1,
        'number' => 'test',
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => 1,
        'product_id' => 1,
        'exchange_item_id' => null,
        'complimentary_item_reason_id' => 1,
        'product_box_units' => 2,
    ]);

    $this->companyId = 1;

    $this->storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $this->checkOrderDetailsService = new CheckOrderDetailsService();
    $this->saveOrderDetailsService = new SaveOrderDetailsService();
});

test('updateInventory When Item Having The Bundle Products', function (): void {
    $inventory = Inventory::factory()->make([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'stock' => 25,
    ]);

    $this->checkOrderDetailsService->products = collect([$this->product2]);
    $this->checkOrderDetailsService->location = $this->location;

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('fetchOrCreate')
            ->once()
            ->andReturn($inventory);
        $mock->shouldReceive('decreaseStock')
            ->once();
    });

    $this->mock(OrderInventoryService::class, function ($mock): void {
        $mock->shouldReceive('updateInventoryUnits')
            ->once();
    });

    $this->saveOrderDetailsService->updateInventory(
        $this->orderItem,
        $this->cartOrderItems,
        $this->storeManager,
        $this->checkOrderDetailsService
    );
});

test('updateInventory When Item Having The Batch Products', function (): void {
    $inventory = Inventory::factory()->make([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'stock' => 25,
    ]);

    $this->checkOrderDetailsService->products = collect([$this->product]);
    $this->checkOrderDetailsService->batches = collect([$this->batch]);
    $this->checkOrderDetailsService->location = $this->location;

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('fetchOrCreate')
            ->once()
            ->andReturn($inventory);
        $mock->shouldReceive('decreaseStock')
            ->once();
    });

    $this->mock(OrderInventoryService::class, function ($mock): void {
        $mock->shouldReceive('updateInventoryUnits')
            ->once();
    });

    $this->saveOrderDetailsService->updateInventory(
        $this->orderItem,
        $this->cartOrderItems,
        $this->storeManager,
        $this->checkOrderDetailsService
    );
});
