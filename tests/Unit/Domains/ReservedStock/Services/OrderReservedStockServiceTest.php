<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\DataObjects\OrderData;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\ReservedStock\Services\OrderReservedStockService;
use App\Models\AssemblyChildProduct;
use App\Models\Cashier;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReservedStock;

test(
    'addReservedStock method calls the getInventoryUnits method of InventoryService class',
    function (): void {
        $mock = $this->createPartialMock(
            OrderReservedStockService::class,
            ['updateInventoryUnitsForOrderItem', 'updateNegativeInventoryUnitsForOrderItem']
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
            ->method('updateInventoryUnitsForOrderItem');

        $mock->expects($this->any())
            ->method('updateNegativeInventoryUnitsForOrderItem');

        $this->mock(InventoryService::class, function ($mock) use ($inventoryUnitA, $inventoryUnitB): void {
            $mock->shouldReceive('getInventoryUnits')
                ->once()
                ->andReturn(collect([$inventoryUnitA, $inventoryUnitB]));
        });

        $product = commonGetProductDetails();

        $mock->addReservedStock(1, $product, new OrderItem(), $quantity, null);
    }
);

test(
    'updateInventoryUnitsForOrderItem method calls the addNew method of the OrderItemUnitQueries class',
    function (): void {
        $quantity = 10;
        $inventoryUnitA = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);

        $this->mock(ReservedStockQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('increaseReservedStock')
                ->once();
        });

        $OrderReservedStockService = resolve(OrderReservedStockService::class);
        $OrderReservedStockService->updateInventoryUnitsForOrderItem(1, $inventoryUnitA, new OrderItem(), $quantity);
    }
);

test(
    'updateNegativeInventoryUnitsForOrderItem method calls the addBlankRecord method of the PurchaseAmountQueries class',
    function (): void {
        $quantity = 10;

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
        });

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addBlankRecord')
                ->once();
        });

        $mock = $this->createPartialMock(OrderReservedStockService::class, ['updateInventoryUnitsForOrderItem']);

        $mock->expects($this->any())
            ->method('updateInventoryUnitsForOrderItem');

        $mock->updateNegativeInventoryUnitsForOrderItem(1, new OrderItem(), $quantity, null);
    }
);

test(
    'removeReservationStock method calls the getByAffectedBy method of the ReservedStockQueries class',
    function (): void {
        $reservedStock = ReservedStock::factory()->make([
            'inventory_id' => 1,
            'inventory_unit_id' => 1,
            'affected_by_id' => 1,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'quantity' => 10,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);
        $reservedStock->inventory = $inventory;

        $reservedStock->inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);
        $this->mock(ReservedStockQueries::class, function ($mock) use ($reservedStock): void {
            $mock->shouldReceive('getByAffectedBy')
                ->once()
                ->andReturn(collect([$reservedStock]));
            $mock->shouldReceive('delete')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseReservedStock')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('refreshInventory')
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('decreaseReservedStock')
                ->once();
        });

        $this->mock(OrderItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $OrderReservedStockService = resolve(OrderReservedStockService::class);
        $OrderReservedStockService->removeReservationStock(
            new OrderItem(),
            new Cashier(),
            now()->format('Y-m-d H:i:s')
        );
    }
);

test(
    'revertReservedStock method calls the getByAffectedBy method of the ReservedStockQueries class',
    function (): void {
        $reservedStock = ReservedStock::factory()->make([
            'inventory_id' => 1,
            'inventory_unit_id' => 1,
            'affected_by_id' => 1,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'quantity' => 10,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);
        $reservedStock->inventory = $inventory;

        $reservedStock->inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 5,
        ]);
        $this->mock(ReservedStockQueries::class, function ($mock) use ($reservedStock): void {
            $mock->shouldReceive('getByAffectedBy')
                ->once()
                ->andReturn(collect([$reservedStock]));
            $mock->shouldReceive('delete')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('revertReservedStock')
                ->once();
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('refreshInventory')
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('revertReservedStock')
                ->once();
        });

        $OrderReservedStockService = resolve(OrderReservedStockService::class);
        $OrderReservedStockService->revertReservedStock(new OrderItem());
    }
);

test('updateReservedStock method calls the same class methods as expected', function (): void {
    $checkSaleDetailsService = new CheckOrderDetailsService();
    $product = Product::factory()->make([
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

    $orderDetails = [
        'order_type' => OrderTypes::PENDING_LAYAWAY_ORDER->value,
        'channel_type' => OrderChannels::B2B_ORDERS->value,
        'member_id' => null,
        'notes' => 'Notes goes here',
        'bill_reference_number' => null,
        'return_items' => null,
        'order_items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'order_round_off_amount' => 0.0,
        'order_return_round_off_amount' => 0.0,
        'total_tax_amount' => 0.0,
        'cart_discount_amount' => 0.0,
        'member_details' => [],
        'location_id' => 1,
        'cart_price_override_amount' => 0.01,
        'cart_price_override_percentage' => 0.01,
        'is_layaway' => true,
        'layaway_pending_amount' => 2,
    ];

    $orderData = new OrderData(...$orderDetails);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $checkSaleDetailsService->products = collect([$product]);
    $checkSaleDetailsService->orderData = $orderData;
    $checkSaleDetailsService->location = $location;
    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 50,
    ]);
    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('fetchOrCreate')
            ->once()
            ->andReturn($inventory);
        $mock->shouldReceive('increaseReservedStock')
            ->once();
    });

    $mock = $this->createPartialMock(OrderReservedStockService::class, ['addReservedStock']);

    $mock->expects($this->any())
        ->method('addReservedStock');

    $mock->updateReservedStock(new OrderItem(), $orderDetails['order_items'][0], $checkSaleDetailsService);
});

test('updateAssemblyProductReservedStock method calls the same class methods as expected', function (): void {
    $checkOrderDetailsService = new CheckOrderDetailsService();
    $product = Product::factory()->make([
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
        'type_id' => ProductTypes::ASSEMBLY_PRODUCT->value,
    ]);

    $assemblyChildProduct = AssemblyChildProduct::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'child_product_id' => 1,
        'units' => 10.10,
    ]);

    $assemblyChildProduct->product = $product;

    $product->assemblyChildProducts = collect([$assemblyChildProduct]);

    $orderDetails = [
        'order_type' => OrderTypes::PENDING_LAYAWAY_ORDER->value,
        'channel_type' => OrderChannels::B2B_ORDERS->value,
        'member_id' => null,
        'notes' => 'Notes goes here',
        'bill_reference_number' => null,
        'return_items' => null,
        'order_items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'order_round_off_amount' => 0.0,
        'order_return_round_off_amount' => 0.0,
        'total_tax_amount' => 0.0,
        'cart_discount_amount' => 0.0,
        'member_details' => [],
        'location_id' => 1,
        'cart_price_override_amount' => 0.01,
        'cart_price_override_percentage' => 0.01,
        'is_layaway' => true,
        'layaway_pending_amount' => 2,
    ];

    $orderData = new OrderData(...$orderDetails);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $checkOrderDetailsService->products = collect([$product]);
    $checkOrderDetailsService->orderData = $orderData;
    $checkOrderDetailsService->location = $location;
    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 50,
    ]);
    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('fetchOrCreate')
            ->once()
            ->andReturn($inventory);
        $mock->shouldReceive('increaseReservedStock')
            ->once();
    });

    $mock = $this->createPartialMock(OrderReservedStockService::class, ['addReservedStock']);

    $mock->expects($this->any())
        ->method('addReservedStock');

    $mock->updateAssemblyProductReservedStock(
        new OrderItem(),
        $orderDetails['order_items'][0],
        $checkOrderDetailsService,
        $product
    );
});
