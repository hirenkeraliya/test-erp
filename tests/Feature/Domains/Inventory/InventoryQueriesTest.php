<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationProduct;
use App\Models\AutomatedNotificationStore;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->automatedNotification = AutomatedNotification::factory()->create([
        'company_id' => $this->company->id,
        'low_stock_alert_threshold' => 10,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'is_non_inventory' => false,
        'status' => Statuses::ACTIVE->value,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    $this->inventory = Inventory::factory()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
    ]);

    $this->inventoryQueries = new InventoryQueries();
});

test('It returns inventory records that match with given parameters.', function (): void {
    $response = $this->inventoryQueries->fetchOrCreate($this->location->id, $this->product->id);

    expect($response)->toBeInstanceOf(Inventory::class);
});

test('It adds new inventory record', function (): void {
    $response = $this->inventoryQueries->fetchOrCreate($this->location->id, $this->product->id);

    expect($response)->toBeInstanceOf(Inventory::class);

    $this->assertDatabaseHas('inventories', [
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
    ]);
});

test('It increases inventory stock', function (): void {
    $inventory = Inventory::factory()->create([
        'location_id' => $this->location->id,
        'stock' => 10,
    ]);

    $response = $this->inventoryQueries->increaseStock($inventory, 10);

    expect($response)->toBeFloat();

    $this->assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'stock' => 20,
    ]);
});

test('the getInventoriesByProductIds method returns inventory stock by product ids', function (): void {
    $inventory = Inventory::factory()->create([
        'location_id' => $this->location->id,
        'stock' => 10.11,
    ]);

    $response = $this->inventoryQueries->getInventoriesByProductIds(
        $inventory->location_id,
        [$inventory->product_id]
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $inventory->id)
        ->toHaveKey('product_id', $inventory->product_id)
        ->toHaveKey('stock', $inventory->stock);
});

test('the getInventoriesWithProductByProductIds method returns inventory stock by product ids', function (): void {
    $inventory = Inventory::factory()->create([
        'location_id' => $this->location->id,
        'stock' => 10.11,
    ]);

    $response = $this->inventoryQueries->getInventoriesWithProductByProductIds(
        $inventory->location_id,
        [$inventory->product_id]
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $inventory->id)
        ->toHaveKey('product_id', $inventory->product_id)
        ->toHaveKey('stock', $inventory->stock)
        ->toHaveKey('product.id', $inventory->product_id);
});

test('the getInventoriesWithProductByProductUpcs method returns inventory stock by product ids', function (): void {
    $product = Product::factory()->create();

    $inventory = Inventory::factory()->create([
        'location_id' => $this->location->id,
        'product_id' => $product->id,
        'stock' => 10.11,
    ]);

    $response = $this->inventoryQueries->getInventoriesWithProductByProductUpcs(
        $inventory->location_id,
        [$product->upc]
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $inventory->id)
        ->toHaveKey('product_id', $inventory->product_id)
        ->toHaveKey('stock', $inventory->stock)
        ->toHaveKey('product.id', $inventory->product_id);
});

test('the getInventoriesByLocation method returns inventory stock by location', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'is_non_inventory' => false,
        'is_available_in_ecommerce' => true,
    ]);

    $inventory = Inventory::factory()->create([
        'location_id' => $this->location->id,
        'stock' => 10.11,
        'product_id' => $product->id,
    ]);

    $response = $this->inventoryQueries->getInventoriesByLocation(
        $inventory->location_id,
        [
            'per_page' => 10,
            'after_updated_at' => null,
            'sort_by' => 'product_id',
            'sort_direction' => 'desc',
        ]
    );

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('product_id', $product->id)
        ->toHaveKey('stock', $inventory->stock)
        ->toHaveKey('reserved_stock', $inventory->reserved_stock)
        ->toHaveKey('updated_at');
});

test('the getInventoryBy method returns inventory stock by product id', function (): void {
    $inventory = Inventory::factory()->create([
        'location_id' => $this->location->id,
        'stock' => 10.11,
    ]);

    $response = $this->inventoryQueries->getInventoryBy($inventory->location_id, $inventory->product_id);

    expect($response->toArray())
        ->toHaveKey('id', $inventory->id)
        ->toHaveKey('product_id', $inventory->product_id)
        ->toHaveKey('stock', $inventory->stock);
});

test('the getInventoryById method returns inventory stock by product id', function (): void {
    $inventory = Inventory::factory()->create([
        'location_id' => $this->location->id,
        'stock' => 10.11,
    ]);

    $response = $this->inventoryQueries->getInventoryById($inventory->id);

    expect($response->toArray())
        ->toHaveKey('id', $inventory->id)
        ->toHaveKey('location_id', $inventory->location_id)
        ->toHaveKey('product_id', $inventory->product_id)
        ->toHaveKey('stock', $inventory->stock)
        ->toHaveKey('reserved_stock', $inventory->reserved_stock);
});

test('decreaseStock method decreases the stock of the specific inventory', function (): void {
    $inventory = Inventory::factory()->create();

    $updatedStock = $inventory->stock - (float) '1';
    $this->inventoryQueries->decreaseStock($inventory, '1');

    $this->assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'stock' => $updatedStock,
    ]);
});

test('It returns the inventory with inventory units based on specified products.', function (): void {
    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $this->inventory->id,
    ]);

    $response = $this->inventoryQueries->getByProductIdWithInventoryUnits($this->product->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->inventory->id)
        ->toHaveKey('product_id', $this->product->id)
        ->toHaveKey('inventory_units.0.id', $inventoryUnit->id)
        ->toHaveKey('inventory_units.0.inventory_id', $inventoryUnit->inventory_id);
});

test('It returns the inventory with inventory units based on specified products and location.', function (): void {
    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $this->inventory->id,
    ]);

    $response = $this->inventoryQueries->getByProductIdsAndLocationWithInventoryUnits(
        $this->location->id,
        [$this->product->id]
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->inventory->id)
        ->toHaveKey('product_id', $this->product->id)
        ->toHaveKey('inventory_units.0.id', $inventoryUnit->id)
        ->toHaveKey('inventory_units.0.inventory_id', $inventoryUnit->inventory_id);
});

test('It returns the inventory based on specified products and location.', function (): void {
    $response = $this->inventoryQueries->getByProductIdsAndLocation($this->location->id, [$this->product->id]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->inventory->id)
        ->toHaveKey('product_id', $this->product->id);
});

test(
    'the inventoryReportsList method returns the inventory reports list as expected',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $filterData = [
            'per_page' => 10,
            'search_text' => null,
            'sort_by' => null,
            'product_id' => null,
            'category_id' => null,
            'brand_id' => null,
            'color_id' => null,
            'size_id' => null,
            'location_ids' => null,
            'article_numbers' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'stock_type' => null,
            'selling_type' => null,
            'style_ids' => null,
            'region_ids' => null,
            'status' => null,
            'product_collection_id' => null,
            'attributes' => [],
        ];

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->company->id,
                'has_batch' => false,
                'is_non_inventory' => false,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            ]);
        }

        if ($productVariant) {
            $this->product->master_product_id = $masterProduct->id;
            $this->product->save();
        }

        $response = $this->inventoryQueries->inventoryReportsList($filterData, $this->product->company_id);

        expect($response->first()->toArray())
            ->toHaveKeys(
                [
                    'id',
                    'product_id',
                    'location_id',
                    'available_stock',
                    'reserved_stock',
                    'created_at',
                    'updated_at',
                    'location',
                    'product',
                    'transit_stocks_sum_quantity',
                ]
            );
    }
)->with([[true], [false]]);

test(
    'the inventoryListsForExport method returns the inventory reports list as expected',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'product_id' => null,
            'category_id' => null,
            'brand_id' => null,
            'color_id' => null,
            'size_id' => null,
            'location_ids' => null,
            'article_numbers' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'stock_type' => null,
            'selling_type' => null,
            'style_ids' => null,
            'region_ids' => null,
            'status' => null,
            'product_collection_id' => null,
            'attributes' => [],
        ];

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->company->id,
                'has_batch' => false,
                'is_non_inventory' => false,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            ]);
        }

        if ($productVariant) {
            $this->product->master_product_id = $masterProduct->id;
            $this->product->save();
        }

        $response = $this->inventoryQueries->inventoryListsForExport($filterData, $this->product->company_id);

        expect($response->first()->toArray())
            ->toHaveKeys(['location', 'product']);
    }
)->with([[true], [false]]);

test('It set updated new stock', function (): void {
    $inventory = Inventory::factory()->create([
        'stock' => 0,
        'reserved_stock' => 5,
    ]);

    $this->inventoryQueries->updateStockBy($inventory->location_id, $inventory->product_id, 10);

    $this->assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'product_id' => $inventory->product_id,
        'location_id' => $inventory->location_id,
        'stock' => 10 - $inventory->reserved_stock,
    ]);
});

test(
    'getProductCountOutOfStock returns correct inventory count for a Store location with zero stock',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;
        $productId = Product::factory()->create([
            'company_id' => $this->company->id,
            'status' => Statuses::ACTIVE->value,
            'upc' => 'tester',
        ])->id;
        Inventory::factory()->create([
            'location_id' => $locationId,
            'product_id' => $productId,
            'stock' => 0,
        ]);

        $response = $this->inventoryQueries->getProductCountOutOfStock($locationId, $this->company->id);

        $this->assertEquals(1, $response);
    }
);

test(
    'getInventoryByProductIdWithLocation returns correct inventories list as expected',
    function (): void {
        $filterData = [
            'product_id' => $this->product->id,
            'after_updated_at' => null,
        ];
        $response = $this->inventoryQueries->getInventoryByProductIdWithLocation($filterData, $this->company->id);
        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'product_id', 'location_id', 'stock', 'location']);
    }
);

test(
    'getStoresHavingInventoriesByProductIds returns collection of product with location list as expected',
    function (): void {
        $cartDetails = [
            [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ],
        ];

        $response = $this->inventoryQueries->getStoresHavingInventoriesByProductIds($this->company->id, $cartDetails);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'product_id', 'location_id', 'location']);
    }
);

test(
    'getInventoryByProductAndLocationWithReservedStock returns remaining stock of product with location as expected',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $productId = Product::factory()->create([
            'company_id' => $this->company->id,
            'status' => Statuses::ACTIVE->value,
        ])->id;

        $inventory = Inventory::factory()->create([
            'location_id' => $locationId,
            'product_id' => $productId,
            'stock' => 9,
        ]);

        $response = $this->inventoryQueries->getInventoryByProductAndLocationWithReservedStock(
            $productId,
            $locationId,
        );

        $stock = $inventory->stock + $inventory->reserved_stock;

        expect($response)
            ->toBe((float) $stock);
    }
);

test(
    'getInventoryByStoreAndProduct returns remaining stock of product specific store and product with location as expected ',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'status' => Statuses::ACTIVE->value,
        ]);

        $inventory = Inventory::factory()->create([
            'location_id' => $locationId,
            'product_id' => $product->id,
            'stock' => 9,
        ]);

        $response = $this->inventoryQueries->getInventoryByStoreAndProduct(
            $locationId,
            $product->id,
            $this->company->id,
            $inventory->stock,
        );

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'product_id', 'location_id']);
    }
);

test(
    'getProductsCountWithExcludeInventoryAndProduct returns exclude product with location as expected ',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'status' => Statuses::ACTIVE->value,
        ]);

        $product2 = Product::factory()->create([
            'company_id' => $this->company->id,
            'status' => Statuses::ACTIVE->value,
        ]);

        $inventory = Inventory::factory()->create([
            'location_id' => $locationId,
            'product_id' => $product->id,
            'stock' => 9,
        ]);

        $inventory2 = Inventory::factory()->create([
            'location_id' => $locationId,
            'product_id' => $product2->id,
            'stock' => 9,
        ]);

        $response = $this->inventoryQueries->getProductsCountWithExcludeInventoryAndProduct(
            $this->company->id,
            $inventory->stock,
            $locationId,
            [$product->id],
            [],
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $inventory2->id)
            ->toHaveKey('product_id', $product2->id);
    }
);

test('It call getActiveProductsByUpcAndStoreCode method return collection by upc and store code', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'code' => '123456',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $inventory = Inventory::factory()->create([
        'product_id' => $this->product->id,
        'location_id' => $location->id,
    ]);

    $inventory->location = $location;

    $productUpcWithStoreCode = [
        [
            'upc' => $this->product->upc,
            'code' => $location->code,
        ],
    ];

    $response = $this->inventoryQueries->getActiveProductsByUpcAndStoreCode(
        $productUpcWithStoreCode,
        $this->company->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $inventory->id)
        ->toHaveKey('product_id', $inventory->product_id)
        ->toHaveKey('location_id', $inventory->location_id);
});

test('It call checkInventoryExportLimit method return collection by upc and store code', function (): void {
    Config::set('app.product_variant', false);

    $filterData = [
        'per_page' => 10,
        'search_text' => null,
        'sort_by' => null,
        'product_id' => null,
        'category_id' => null,
        'brand_id' => null,
        'color_id' => null,
        'size_id' => null,
        'location_ids' => null,
        'location_type' => null,
        'article_numbers' => null,
        'department_ids' => null,
        'tag_ids' => null,
        'stock_type' => null,
        'selling_type' => null,
        'style_ids' => null,
        'region_ids' => null,
        'status' => null,
        'product_collection_id' => null,
        'attributes' => null,
    ];

    $response = $this->inventoryQueries->exportInventoryRecords($filterData, $this->company->id, 0, 10);

    expect($response->count())
        ->toBe(Inventory::count());
});

test('It call getInventoriesExportCount method return false', function (): void {
    Config::set('app.product_variant', false);

    $filterData = [
        'per_page' => 10,
        'search_text' => null,
        'sort_by' => null,
        'product_id' => null,
        'category_id' => null,
        'brand_id' => null,
        'color_id' => null,
        'size_id' => null,
        'location_ids' => null,
        'location_type' => null,
        'article_numbers' => null,
        'department_ids' => null,
        'tag_ids' => null,
        'stock_type' => null,
        'selling_type' => null,
        'style_ids' => null,
        'region_ids' => null,
        'status' => null,
        'product_collection_id' => null,
        'attributes' => null,
    ];

    $response = $this->inventoryQueries->getInventoriesExportCount($filterData, $this->company->id);

    expect($response)
        ->toBe(Inventory::count());
});

test('It call getNegativeStockItems method return negative stock counts', function (): void {
    $company = Company::factory()->create([
        'name' => 'test',
    ]);
    $location = Location::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $product = Product::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'is_non_inventory' => false,
        'status' => Statuses::ACTIVE->value,
    ]);

    Inventory::factory()->create([
        'stock' => -1,
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    $filterData = [
        'location_id' => $location->id,
    ];

    $response = $this->inventoryQueries->getNegativeStockItems($filterData, $company->id, false);

    expect((float) $response)
        ->toBe(abs((float) Inventory::where('stock', '<', 0)->sum('stock')));
});

test('It call getCompanyLowStockItems method return company low stock counts', function (): void {
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $location = Location::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $product = Product::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'is_non_inventory' => false,
        'status' => Statuses::ACTIVE->value,
    ]);

    Inventory::factory()->create([
        'stock' => 5,
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    AutomatedNotification::factory()->create([
        'company_id' => $company->id,
        'low_stock_alert_threshold' => 10,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
    ]);

    $filterData = [
        'location_id' => $location->id,
    ];

    $response = $this->inventoryQueries->getCompanyLowStockItems($filterData, $company->id, false);

    expect((float) $response)
        ->toBe(
            (float) Inventory::where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->count()
        );
});

test('It call getLocationLowStockItems method return location low stock counts', function (): void {
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $location = Location::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $product = Product::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'is_non_inventory' => false,
        'status' => Statuses::ACTIVE->value,
    ]);

    Inventory::factory()->create([
        'stock' => 5,
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    $automatedNotification = AutomatedNotification::factory()->create([
        'company_id' => $company->id,
        'low_stock_alert_threshold' => 0,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_LOCATION->value,
    ]);

    AutomatedNotificationStore::factory()->create([
        'automated_notification_id' => $automatedNotification->id,
        'location_id' => $location->id,
        'low_stock_alert_threshold' => 10,
    ]);

    $filterData = [
        'location_id' => $location->id,
    ];

    $response = $this->inventoryQueries->getLocationLowStockItems($filterData, $company->id, false);

    expect((float) $response)
        ->toBe(
            (float) Inventory::where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->count()
        );
});

test('It call getProductLowStockItems method return product low stock counts', function (): void {
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $location = Location::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $product = Product::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'is_non_inventory' => false,
        'status' => Statuses::ACTIVE->value,
    ]);

    Inventory::factory()->create([
        'stock' => 5,
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    $automatedNotification = AutomatedNotification::factory()->create([
        'company_id' => $company->id,
        'low_stock_alert_threshold' => 0,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value,
    ]);

    AutomatedNotificationProduct::factory()->create([
        'automated_notification_id' => $automatedNotification->id,
        'product_id' => $product->id,
        'location_id' => $location->id,
        'low_stock_alert_threshold' => 10,
    ]);

    $filterData = [
        'location_id' => $location->id,
    ];

    $response = $this->inventoryQueries->getProductLowStockItems($filterData, $company->id, false);

    expect((float) $response)
        ->toBe(
            (float) Inventory::where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->count()
        );
});

test('It call getInventoryStock method return inventory', function (): void {
    $response = $this->inventoryQueries->getInventoryStock($this->product->id, $this->location->id);

    expect($response)
        ->toHaveKeys(['id', 'stock']);
});

test('getAllByCompanyId returns the inventory details', function (): void {
    $response = $this->inventoryQueries->getAllByCompanyId($this->company->id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->inventory->id)
        ->toHaveKey('location_id', $this->inventory->location_id)
        ->toHaveKey('product_id', $this->inventory->product_id);
});
