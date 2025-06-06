<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseAmount;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->inventoryUpdateQueries = new InventoryUpdateQueries();
});

test(
    'getPaginatedStockMovementsOfAProductForALocation method returns stock movement ledger report list as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
        ]);

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE->name,
            'affected_by_id' => $sale->id,
        ]);

        $response = $this->inventoryUpdateQueries->getPaginatedStockMovementsOfAProductForALocation([
            'location_ids' => [$location->id],
            'product_id' => $product->id,
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
        ], $companyId);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('id', $inventoryUpdate->id)
            ->toHaveKey('product_id', $inventoryUpdate->product_id)
            ->toHaveKey('location_id', $inventoryUpdate->location_id)
            ->toHaveKey('affected_by_id', $inventoryUpdate->affected_by_id)
            ->toHaveKey('affected_by_type', $inventoryUpdate->affected_by_type)
            ->toHaveKey('quantity', $inventoryUpdate->quantity)
            ->toHaveKey('user_id', $inventoryUpdate->user_id)
            ->toHaveKey('user_type', $inventoryUpdate->user_type)
            ->toHaveKey('closing_stock', $inventoryUpdate->closing_stock);
    }
);

test('Inventory Update can be added', function (): void {
    $company = Company::factory()->create();

    $product = Product::factory()->create([
        'company_id' => $company->id,
    ]);
    $purchaseAmount = PurchaseAmount::factory()->create();
    $locationId = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $locationId,
    ]);

    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
    ]);
    $admin = Admin::factory()->create([
        'employee_id' => $employee->id,
    ]);
    $inventoryStock = '100.00';
    $currentTime = now()->format('Y-m-d H:i:s');
    Carbon::setTestNow($currentTime);

    $this->inventoryUpdateQueries->addNew(
        $product->id,
        (float) 10,
        $locationId,
        $goodsReceivedNoteProduct,
        $admin,
        (float) $inventoryStock,
        null,
        $purchaseAmount->id,
    );

    $this->assertDatabaseHas('inventory_updates', [
        'product_id' => $product->id,
        'batch_id' => null,
        'purchase_amount_id' => $purchaseAmount->id,
        'location_id' => $locationId,
        'affected_by_id' => $goodsReceivedNoteProduct->id,
        'affected_by_type' => ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name,
        'quantity' => '10.00',
        'user_id' => $admin->id,
        'user_type' => ModelMapping::ADMIN->name,
        'happened_at' => $currentTime,
        'notes' => null,
        'closing_stock' => $inventoryStock,
    ]);
});

test(
    'getPaginatedStockMovementsOfAProductForLocationTypeStore method returns stock movement ledger report list as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
        ]);

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE->name,
            'affected_by_id' => $sale->id,
        ]);

        $response = $this->inventoryUpdateQueries->getPaginatedStockMovementsOfAProductForLocationTypeStore([
            'product_id' => $product->id,
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
        ], $location->id);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('id', $inventoryUpdate->id)
            ->toHaveKey('product_id', $inventoryUpdate->product_id)
            ->toHaveKey('location_id', $inventoryUpdate->location_id)
            ->toHaveKey('affected_by_id', $inventoryUpdate->affected_by_id)
            ->toHaveKey('affected_by_type', $inventoryUpdate->affected_by_type)
            ->toHaveKey('quantity', $inventoryUpdate->quantity)
            ->toHaveKey('user_id', $inventoryUpdate->user_id)
            ->toHaveKey('user_type', $inventoryUpdate->user_type)
            ->toHaveKey('closing_stock', $inventoryUpdate->closing_stock);
    }
);

test(
    'getStockMovementsOfAProductForALocationForExport method returns stock movement ledger list as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
        ]);

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE->name,
            'affected_by_id' => $sale->id,
        ]);

        $response = $this->inventoryUpdateQueries->getStockMovementsOfAProductForALocationForExport([
            'location_ids' => [$location->id],
            'product_id' => $product->id,
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
        ], $companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $inventoryUpdate->id)
            ->toHaveKey('product_id', $inventoryUpdate->product_id)
            ->toHaveKey('location_id', $inventoryUpdate->location_id)
            ->toHaveKey('affected_by_id', $inventoryUpdate->affected_by_id)
            ->toHaveKey('affected_by_type', $inventoryUpdate->affected_by_type)
            ->toHaveKey('quantity', $inventoryUpdate->quantity)
            ->toHaveKey('user_id', $inventoryUpdate->user_id)
            ->toHaveKey('user_type', $inventoryUpdate->user_type)
            ->toHaveKey('closing_stock', $inventoryUpdate->closing_stock);
    }
);

test(
    'getStockMovementsOfProductsForALocationForPrint method returns stock movement list as expected',
    function (): void {
        $company = Company::factory()->create();

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'is_non_inventory' => false,
        ]);

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'affected_by_id' => $sale->id,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->inventoryUpdateQueries->getStockMovementsOfProductsForALocationForPrint([
            'location_ids' => [$location->id],
            'product_ids' => [$product->id],
            'date_range' => [Carbon::now()->yesterday()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
            'category_ids' => null,
            'product_id' => null,
            'article_number' => null,
            'brand_ids' => null,
            'department_ids' => null,
        ]);

        expect($response->first()->toArray())
            ->toHaveKey('product_id', $product->id);
    }
);

test(
    'getStockMovementsOfAProductForALocationForExportInStoreManagerPanel method returns stock movement ledger list as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
        ]);

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE->name,
            'affected_by_id' => $sale->id,
        ]);

        $response = $this->inventoryUpdateQueries->getStockMovementsOfAProductForALocationForExportInStoreManagerPanel([
            'location_id' => $location->id,
            'product_id' => $product->id,
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
        ], $location->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $inventoryUpdate->id)
            ->toHaveKey('product_id', $inventoryUpdate->product_id)
            ->toHaveKey('location_id', $inventoryUpdate->location_id)
            ->toHaveKey('affected_by_id', $inventoryUpdate->affected_by_id)
            ->toHaveKey('affected_by_type', $inventoryUpdate->affected_by_type)
            ->toHaveKey('quantity', $inventoryUpdate->quantity)
            ->toHaveKey('user_id', $inventoryUpdate->user_id)
            ->toHaveKey('user_type', $inventoryUpdate->user_type)
            ->toHaveKey('closing_stock', $inventoryUpdate->closing_stock);
    }
);

test(
    'getPaginatedStockMovementsOfAProductForLocationTypeWarehouse method returns stock movement ledger report list as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
        ]);

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE->name,
            'affected_by_id' => $sale->id,
        ]);

        $response = $this->inventoryUpdateQueries->getPaginatedStockMovementsOfAProductForLocationTypeWarehouse([
            'product_id' => $product->id,
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
        ], $location->id);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('id', $inventoryUpdate->id)
            ->toHaveKey('product_id', $inventoryUpdate->product_id)
            ->toHaveKey('location_id', $inventoryUpdate->location_id)
            ->toHaveKey('affected_by_id', $inventoryUpdate->affected_by_id)
            ->toHaveKey('affected_by_type', $inventoryUpdate->affected_by_type)
            ->toHaveKey('quantity', $inventoryUpdate->quantity)
            ->toHaveKey('user_id', $inventoryUpdate->user_id)
            ->toHaveKey('user_type', $inventoryUpdate->user_type)
            ->toHaveKey('closing_stock', $inventoryUpdate->closing_stock);
    }
);

test(
    'getStockMovementsOfAProductForALocationForExportInWarehouseManagerPanel method returns stock movement ledger list as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
        ]);

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $sale = Sale::factory()->create();

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE->name,
            'affected_by_id' => $sale->id,
        ]);

        $response = $this->inventoryUpdateQueries->getStockMovementsOfAProductForALocationForExportInWarehouseManagerPanel(
            [
                'location_id' => $location->id,
                'product_id' => $product->id,
                'search_text' => null,
                'sort_by' => null,
                'sort_direction' => null,
            ],
            $location->id,
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $inventoryUpdate->id)
            ->toHaveKey('product_id', $inventoryUpdate->product_id)
            ->toHaveKey('location_id', $inventoryUpdate->location_id)
            ->toHaveKey('affected_by_id', $inventoryUpdate->affected_by_id)
            ->toHaveKey('affected_by_type', $inventoryUpdate->affected_by_type)
            ->toHaveKey('quantity', $inventoryUpdate->quantity)
            ->toHaveKey('user_id', $inventoryUpdate->user_id)
            ->toHaveKey('user_type', $inventoryUpdate->user_type)
            ->toHaveKey('closing_stock', $inventoryUpdate->closing_stock);
    }
);

test(
    'getByProductIdAndLocationForStockCardPrint method returns stock card list as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
            'is_non_inventory' => false,
        ]);

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE->name,
            'affected_by_id' => $sale->id,
            'happened_at' => now(),
        ]);

        $response = $this->inventoryUpdateQueries->getByProductIdAndLocationForStockCardPrint([
            'location_id' => $location->id,
            'product_id' => $product->id,
            'article_number' => null,
            'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
        ]);

        expect($response->first()->toArray())
            ->toHaveKey('id', $inventoryUpdate->id)
            ->toHaveKey('product_id', $inventoryUpdate->product_id)
            ->toHaveKey('location_id', $inventoryUpdate->location_id)
            ->toHaveKey('affected_by_id', $inventoryUpdate->affected_by_id)
            ->toHaveKey('affected_by_type', $inventoryUpdate->affected_by_type)
            ->toHaveKey('quantity', $inventoryUpdate->quantity)
            ->toHaveKey('closing_stock', $inventoryUpdate->closing_stock)
            ->toHaveKey('affected_by');
    }
);

test(
    'getRecordsAfterDateByLocationAndProduct method return records by location, product id by after date',
    function (): void {
        $product = Product::factory()->create();
        $location = Location::factory()->create([
            'company_id' => $product->company_id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $inventory = InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'happened_at' => Carbon::now()->addDay(),
        ]);

        InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'happened_at' => Carbon::now()->addDays(2),
        ]);

        $response = $this->inventoryUpdateQueries->getRecordsAfterDateByLocationAndProduct(
            Carbon::now()->format('Y-m-d'),
            $location->id,
            $product->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $inventory->id)
            ->toHaveKey('quantity', $inventory->quantity)
            ->toHaveKey('notes', $inventory->notes)
            ->toHaveKey('closing_stock', $inventory->closing_stock);
    }
);

test('getLatestClosingStockBy method return latest records by location, product id & date', function (): void {
    $product = Product::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $product->company_id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'happened_at' => Carbon::now()->subDay(),
    ]);

    $inventoryUpdateTwo = InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'happened_at' => Carbon::now()->subDay(),
    ]);

    $response = $this->inventoryUpdateQueries->getLatestClosingStockBy(
        Carbon::now()->format('Y-m-d'),
        $location->id,
        $product->id
    );

    expect($response->toArray())
        ->toHaveKey('id', $inventoryUpdateTwo->id)
        ->toHaveKey('closing_stock', $inventoryUpdateTwo->closing_stock);
});

test(
    'updateClosingStockOfPreviousRecord method return latest records by location, product id & date',
    function (): void {
        $stockTransfer = StockTransfer::factory()->create();
        $stockTransferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
        ]);

        $inventoryUpdate = InventoryUpdate::factory()->create([
            'location_id' => $stockTransfer->destination_location_id,
            'affected_by_id' => $stockTransferItem->id,
            'affected_by_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
            'quantity' => 1,
            'closing_stock' => 1,
        ]);

        $closingStock = 100;

        $response = $this->inventoryUpdateQueries->updateClosingStockOfPreviousRecord(
            $inventoryUpdate,
            $closingStock,
            $stockTransferItem->id,
        );

        $this->assertDatabaseHas('inventory_updates', [
            'id' => $inventoryUpdate->id,
            'closing_stock' => $inventoryUpdate->quantity + $closingStock,
        ]);

        $this->assertEquals($inventoryUpdate->quantity + $closingStock, $response);
    }
);

test(
    'getStockMovementsByLocationsAndProductIdsForPrint method returns stock movement data as expected',
    function (): void {
        $company = Company::factory()->create();

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'is_non_selling_item' => false,
        ]);

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'name' => 'ABCD',
            'code' => 'XYZW',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->create();

        InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'affected_by_id' => $sale->id,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $filterData = [
            'location_ids' => [$location->id],
            'product_ids' => [$product->id],
        ];

        $response = $this->inventoryUpdateQueries->getStockMovementsByLocationsAndProductIdsForPrint($filterData);

        expect($response->first()->toArray())
            ->toHaveKey('product_id', $product->id)
            ->toHaveKeys(
                [
                    'affected_by_id',
                    'affected_by_type',
                    'location_id',
                    'quantity',
                    'happened_at',
                    'closing_stock',
                    'created_at',
                ]
            );
    }
);

test(
    'getAffectedDatesForSellThroughAggregate method return date in array',
    function (): void {
        $product = Product::factory()->create([
            'is_non_selling_item' => false,
            'deleted_at' => null,
        ]);

        $location = Location::factory()->create([
            'company_id' => $product->company_id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'happened_at' => Carbon::now()->subDay()->format('Y-m-d h:i:s'),
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'happened_at' => Carbon::now()->subDays(3)->format('Y-m-d h:i:s'),
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        InventoryUpdate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->inventoryUpdateQueries->getAffectedDatesForSellThroughAggregate(
            now()->subDay()->format('Y-m-d')
        );

        expect($response)
            ->toHaveKey(0, Carbon::now()->subDays(3)->format('Y-m-d'))
            ->toHaveKey(1, Carbon::now()->subDay()->format('Y-m-d'))
            ->toHaveKey(2, Carbon::now()->format('Y-m-d'));
    }
);

test('getAllByCompanyId returns the inventory update details', function (): void {
    $companyId = Company::factory()->create()->id;

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'status' => Statuses::ACTIVE->value,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    $location = Location::factory()->create([
        'company_id' => $companyId,
        'name' => 'ABCD',
        'code' => 'XYZW',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->create();

    $inventoryUpdate = InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'affected_by_type' => ModelMapping::SALE->name,
        'affected_by_id' => $sale->id,
    ]);

    $response = $this->inventoryUpdateQueries->getAllByCompanyId($companyId);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $inventoryUpdate->id)
        ->toHaveKey('location_id', $inventoryUpdate->location_id)
        ->toHaveKey('product_id', $inventoryUpdate->product_id);
});
