<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Models\AssemblyChildProduct;
use App\Models\Cashier;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\Product;
use App\Models\ReservedStock;
use App\Models\SaleItem;

test(
    'addReservedStock method calls the getInventoryUnits method of InventoryService class',
    function (): void {
        $mock = $this->createPartialMock(
            SaleReservedStockService::class,
            ['updateInventoryUnitsForSaleItem', 'updateNegativeInventoryUnitsForSaleItem']
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
            ->method('updateInventoryUnitsForSaleItem');

        $mock->expects($this->any())
            ->method('updateNegativeInventoryUnitsForSaleItem');

        $this->mock(InventoryService::class, function ($mock) use ($inventoryUnitA, $inventoryUnitB): void {
            $mock->shouldReceive('getInventoryUnits')
                ->once()
                ->andReturn(collect([$inventoryUnitA, $inventoryUnitB]));
        });

        $product = commonGetProductDetails();

        $mock->addReservedStock(1, $product, new SaleItem(), $quantity, null);
    }
);

test(
    'updateInventoryUnitsForSaleItem method calls the addNew method of the SaleItemUnitQueries class',
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

        $saleReservedStockService = resolve(SaleReservedStockService::class);
        $saleReservedStockService->updateInventoryUnitsForSaleItem(1, $inventoryUnitA, new SaleItem(), $quantity);
    }
);

test(
    'updateNegativeInventoryUnitsForSaleItem method calls the addBlankRecord method of the PurchaseAmountQueries class',
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

        $mock = $this->createPartialMock(SaleReservedStockService::class, ['updateInventoryUnitsForSaleItem']);

        $mock->expects($this->any())
            ->method('updateInventoryUnitsForSaleItem');

        $mock->updateNegativeInventoryUnitsForSaleItem(1, new SaleItem(), $quantity, null);
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

        $this->mock(SaleItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $saleReservedStockService = resolve(SaleReservedStockService::class);
        $saleReservedStockService->removeReservationStock(
            new SaleItem(),
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

        $saleReservedStockService = resolve(SaleReservedStockService::class);
        $saleReservedStockService->revertReservedStock(new SaleItem());
    }
);

test('updateReservedStock method calls the same class methods as expected', function (): void {
    $checkSaleDetailsService = new CheckSaleDetailsService();
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

    $saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => 1,
        'cashback_amount' => 12,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promotion_id' => 1,
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => false,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $saleData = new SaleData(...$saleDetails);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $checkSaleDetailsService->products = collect([$product]);
    $checkSaleDetailsService->saleData = $saleData;
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

    $mock = $this->createPartialMock(SaleReservedStockService::class, ['addReservedStock']);

    $mock->expects($this->any())
        ->method('addReservedStock');

    $mock->updateReservedStock(new SaleItem(), $saleDetails['items'][0], $checkSaleDetailsService);
});

test(
    'updateReservedStock method calls the same class methods as expected when product type Assembly Product',
    function (): void {
        $checkSaleDetailsService = new CheckSaleDetailsService();
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

        $saleDetails = [
            'offline_sale_id' => '1',
            'employee_id' => null,
            'return_items' => null,
            'vouchers' => null,
            'cashback_id' => 1,
            'cashback_amount' => 12,
            'items' => [
                [
                    'id' => 1,
                    'price' => '10.00',
                    'quantity' => '10',
                    'promotion_id' => 1,
                ],
            ],
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => '100',
                ],
            ],
            'sale_notes' => 'Notes goes here',
            'happened_at' => '2022-01-04 04:20:50',
            'member_id' => 1,
            'is_layaway' => false,
            'cart_promotion_id' => null,
            'sale_round_off_amount' => 0.01,
        ];

        $saleData = new SaleData(...$saleDetails);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $checkSaleDetailsService->products = collect([$product]);
        $checkSaleDetailsService->saleData = $saleData;
        $checkSaleDetailsService->location = $location;

        $mock = $this->createPartialMock(SaleReservedStockService::class, ['updateAssemblyProductReservedStock']);

        $mock->expects($this->once())
            ->method('updateAssemblyProductReservedStock');

        $mock->updateReservedStock(new SaleItem(), $saleDetails['items'][0], $checkSaleDetailsService);
    }
);

test('updateAssemblyProductReservedStock method calls the same class methods as expected', function (): void {
    $checkSaleDetailsService = new CheckSaleDetailsService();
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

    $saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => 1,
        'cashback_amount' => 12,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promotion_id' => 1,
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => false,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $saleData = new SaleData(...$saleDetails);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $checkSaleDetailsService->products = collect([$product]);
    $checkSaleDetailsService->saleData = $saleData;
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

    $mock = $this->createPartialMock(SaleReservedStockService::class, ['addReservedStock']);

    $mock->expects($this->any())
        ->method('addReservedStock');

    $mock->updateAssemblyProductReservedStock(
        new SaleItem(),
        $saleDetails['items'][0],
        $checkSaleDetailsService,
        $product
    );
});
