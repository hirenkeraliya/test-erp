<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchaseAmount;
use App\Models\ReservedStock;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->reservedStockQueries = new ReservedStockQueries();
});

test('ReservedStock can be added', function (): void {
    $inventory = Inventory::factory()->create();
    $purchaseAmount = PurchaseAmount::factory()->create();
    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $inventory->id,
        'purchase_amount_id' => $purchaseAmount->id,
        'batch_id' => null,
        'quantity' => 10,
        'reserved_stock' => 10,
    ]);

    $saleItem = SaleItem::factory()->create();

    $this->reservedStockQueries->addNew($inventory->id, $inventoryUnit->id, 10.0, $saleItem, 'Test');

    $this->assertDatabaseHas('reserved_stocks', [
        'inventory_id' => $inventory->id,
        'inventory_unit_id' => $inventoryUnit->id,
        'affected_by_id' => $saleItem->id,
        'affected_by_type' => ModelMapping::SALE_ITEM->name,
        'quantity' => 10.0,
        'notes' => 'Test',
    ]);
});

test('return reservedStock affected id and type', function (): void {
    $inventory = Inventory::factory()->create();
    $purchaseAmount = PurchaseAmount::factory()->create();
    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $inventory->id,
        'purchase_amount_id' => $purchaseAmount->id,
        'batch_id' => null,
        'quantity' => 10,
        'reserved_stock' => 10,
    ]);

    $saleItem = SaleItem::factory()->create();
    ReservedStock::factory()->create([
        'inventory_id' => $inventory->id,
        'inventory_unit_id' => $inventoryUnit->id,
        'affected_by_id' => $saleItem->id,
        'affected_by_type' => ModelMapping::SALE_ITEM->name,
        'quantity' => 10.0,
        'notes' => 'Test',
    ]);

    $response = $this->reservedStockQueries->getByAffectedBy($saleItem);

    expect($response->first()->toArray())
        ->toHaveKey('inventory_id', $inventory->id)
        ->toHaveKey('inventory_unit_id', $inventoryUnit->id)
        ->toHaveKey('affected_by_id', $saleItem->id)
        ->toHaveKey('affected_by_type', ModelMapping::SALE_ITEM->name)
        ->toHaveKey('quantity', 10.0)
        ->toHaveKey('notes', 'Test');
});

test('delete method delete the reserved stock', function (): void {
    $reservedStock = ReservedStock::factory()->create();
    $this->reservedStockQueries->delete($reservedStock);

    $this->assertSoftDeleted('reserved_stocks', [
        'id' => $reservedStock->id,
    ]);
});

test(
    'the getPaginatedReservedInventoryForLocation method returns the reserved inventory reports list as expected',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);
        $company = Company::factory()->create();

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $company->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->getKey(),
        ]);

        $purchaseAmount = PurchaseAmount::factory()->create();

        $inventoryUnit = InventoryUnit::factory()->create([
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => $purchaseAmount->id,
            'batch_id' => null,
            'quantity' => 10,
            'reserved_stock' => 10,
        ]);

        $saleItem = SaleItem::factory()->create();

        ReservedStock::factory()->create([
            'inventory_id' => $inventory->id,
            'inventory_unit_id' => $inventoryUnit->id,
            'affected_by_id' => $saleItem->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'quantity' => 10.0,
            'notes' => 'Test',
        ]);

        $filterData = [
            'per_page' => 10,
            'search_text' => null,
            'sort_by' => null,
            'product_id' => null,
            'location_id' => $location->getKey(),
            'product_collection_id' => null,
        ];

        $response = $this->reservedStockQueries->getPaginatedReservedInventoryForLocation(
            $filterData,
            $product->company_id
        );

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKeys(['inventory_id', 'inventory.product', 'inventory.product.product_variant_values']);
        } else {
            expect($response->first()->toArray())
                ->toHaveKeys(
                    ['inventory_id', 'inventory.product', 'inventory.product.color', 'inventory.product.size']
                );
        }
    }
)->with([[true], [false]]);

test(
    'the getReservedInventoryLocationForExport method returns the reserved inventory reports list as expected',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);
        $company = Company::factory()->create();

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $company->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->getKey(),
        ]);

        $purchaseAmount = PurchaseAmount::factory()->create();

        $inventoryUnit = InventoryUnit::factory()->create([
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => $purchaseAmount->id,
            'batch_id' => null,
            'quantity' => 10,
            'reserved_stock' => 10,
        ]);

        $saleItem = SaleItem::factory()->create();

        ReservedStock::factory()->create([
            'inventory_id' => $inventory->id,
            'inventory_unit_id' => $inventoryUnit->id,
            'affected_by_id' => $saleItem->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'quantity' => 10.0,
            'notes' => 'Test',
        ]);

        $filterData = [
            'per_page' => 10,
            'search_text' => null,
            'sort_by' => null,
            'product_id' => null,
            'location_id' => $location->getKey(),
            'product_collection_id' => null,
        ];

        $response = $this->reservedStockQueries->getReservedInventoryLocationForExport(
            $filterData,
            $product->company_id
        );

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKeys(['inventory_id', 'inventory.product', 'inventory.product.product_variant_values']);
        } else {
            expect($response->first()->toArray())
                ->toHaveKeys(
                    ['inventory_id', 'inventory.product', 'inventory.product.color', 'inventory.product.size']
                );
        }
    }
)->with([[true], [false]]);
