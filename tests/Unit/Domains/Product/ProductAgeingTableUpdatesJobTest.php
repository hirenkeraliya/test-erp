<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\Jobs\ProductAgeingTableUpdatesJob;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Models\Inventory;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\Carbon;

test('if new location is created', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'created_at' => Carbon::now()->yesterday()->format('Y-m-d'),
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $product->getKey(),
        'location_id' => $location->getKey(),
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => null,
        'counter_update_id' => 1,
        'happened_at' => Carbon::now()->yesterday()->format('Y-m-d'),
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'derivative_id' => null,
    ]);

    $saleItem->sale = $sale;

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getYesterdayCreatedLocationsIds')
            ->once()
            ->andReturn([$location->id]);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getAllActiveProductsIds')
            ->once()
            ->andReturn([$product]);

        $mock->shouldReceive('getByIdWithOriginalCreatedAt')
            ->once()
            ->andReturn();
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getInventoryByProductAndLocationWithReservedStock')
            ->once()
            ->andReturn($inventory->stock);
    });

    $this->mock(InventoryUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getByLocationAndProductId')
            ->twice()
            ->andReturn();
    });

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getSaleItemsForTheProductAgeingReport')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $this->mock(ProductAgeingQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();

        $mock->shouldReceive('getDetailsByProductIdAndLocationId')
            ->once();
    });

    $productAgeingTableUpdatesJob = new ProductAgeingTableUpdatesJob(now()->format('Y-m-d'), 1);
    $productAgeingTableUpdatesJob->checkIfAnyStoreIsCreated();
});

test('if new product is created', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'created_at' => Carbon::now()->yesterday()->format('Y-m-d'),
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $product->getKey(),
        'location_id' => $location->getKey(),
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => null,
        'counter_update_id' => 1,
        'happened_at' => Carbon::now()->yesterday()->format('Y-m-d'),
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'derivative_id' => null,
    ]);

    $saleItem->sale = $sale;

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getYesterdayCreatedProductsIds')
            ->once()
            ->andReturn([$product]);

        $mock->shouldReceive('getByIdWithOriginalCreatedAt')
            ->once()
            ->andReturn();
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getAllLocationsIds')
            ->once()
            ->andReturn([$location->id]);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getInventoryByProductAndLocationWithReservedStock')
            ->once()
            ->andReturn($inventory->stock);
    });

    $this->mock(InventoryUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getByLocationAndProductId')
            ->twice()
            ->andReturn();
    });

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getSaleItemsForTheProductAgeingReport')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $this->mock(ProductAgeingQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();

        $mock->shouldReceive('getDetailsByProductIdAndLocationId')
            ->once();
    });

    $productAgeingTableUpdatesJob = new ProductAgeingTableUpdatesJob(now()->format('Y-m-d'), 1);
    $productAgeingTableUpdatesJob->checkIfAnyProductIsCreated();
});

test('update the product stock and the age of the products', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'created_at' => Carbon::now()->yesterday()->format('Y-m-d'),
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $product->getKey(),
        'location_id' => $location->getKey(),
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => null,
        'counter_update_id' => 1,
        'happened_at' => Carbon::now()->yesterday()->format('Y-m-d'),
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'derivative_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'member_id' => null,
        'offline_sale_return_id' => 1,
        'original_sale_id' => $sale->getKey(),
        'counter_update_id' => 1,
        'happened_at' => Carbon::now()->yesterday()->format('Y-m-d'),
    ]);

    $saleReturnItem = SaleReturnItem::factory()->make([
        'id' => 1,
        'sale_return_id' => $saleReturn->getKey(),
        'original_sale_item_id' => $saleReturn->getKey(),
        'sale_return_reason_id' => 1,
        'product_id' => $product->getKey(),
        'derivative_id' => null,
    ]);

    $saleItem->sale = $sale;

    $dayBeforeYesterdayDate = Carbon::yesterday()->subDay()->format('Y-m-d');

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getInventoryByProductAndLocationWithReservedStock')
            ->times(1)
            ->andReturn($inventory->stock);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithOriginalCreatedAt')
            ->times(1)
            ->andReturn();
    });

    $this->mock(InventoryUpdateQueries::class, function ($mock) use (
        $dayBeforeYesterdayDate,
        $product,
        $location
    ): void {
        $mock->shouldReceive('getYesterdayInventoryUpdateWithInventory')
            ->once()
            ->with($dayBeforeYesterdayDate)
            ->andReturn(collect([
                [
                    'product_id' => $product->getKey(),
                    'location_id' => $location->getKey(),
                ],
            ]));

        $mock->shouldReceive('getByLocationAndProductId')
            ->times(2);
    });

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getSaleItemsForTheProductAgeingReport')
            ->times(1)
            ->andReturn(collect([$saleItem]));
    });

    $this->mock(ProductAgeingQueries::class, function ($mock): void {
        $mock->shouldReceive('update')
            ->times(1);

        $mock->shouldReceive('getDetailsByProductIdAndLocationId')
            ->times(1);
    });

    $productAgeingTableUpdatesJob = new ProductAgeingTableUpdatesJob($dayBeforeYesterdayDate, 1);
    $productAgeingTableUpdatesJob->updateTheProductsStock();
});

test(
    'update the product stock and the age of the products using inventory when sale and sale return are null',
    function (): void {
        $yesterdayDate = Carbon::now()->yesterday()->format('Y-m-d');

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'created_at' => Carbon::now()->yesterday()->format('Y-m-d'),
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->getKey(),
            'location_id' => $location->getKey(),
            'stock' => 500,
        ]);

        $inventoryUpdate = InventoryUpdate::factory()->make([
            'product_id' => $product->getKey(),
            'location_id' => $location->getKey(),
            'affected_by' => 1,
            'affected_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
            'batch_id' => null,
            'purchase_amount_id' => null,
            'user_type' => ModelMapping::ADMIN->name,
            'happened_at' => $yesterdayDate,
        ]);

        $inventoryUpdate->product = $product;
        $inventoryUpdate->location = $location;

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getSaleItemsForTheProductAgeingReport')
                ->times(1)
                ->andReturn(collect([]));
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock) use ($inventoryUpdate, $yesterdayDate): void {
            $mock->shouldReceive('getYesterdayInventoryUpdateWithInventory')
                ->once()
                ->with($yesterdayDate)
                ->andReturn(collect([$inventoryUpdate]));

            $mock->shouldReceive('getByLocationAndProductId')
                ->times(2);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getInventoryByProductAndLocationWithReservedStock')
                ->times(1)
                ->andReturn($inventory->stock);
        });

        $this->mock(ProductAgeingQueries::class, function ($mock): void {
            $mock->shouldReceive('getDetailsByProductIdAndLocationId')
                ->times(1);

            $mock->shouldReceive('update')
                ->times(1);
        });

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithOriginalCreatedAt')
                ->times(1);
        });

        $productAgeingTableUpdatesJob = new ProductAgeingTableUpdatesJob($yesterdayDate, 1);
        $productAgeingTableUpdatesJob->updateTheProductsStock();
    }
);
