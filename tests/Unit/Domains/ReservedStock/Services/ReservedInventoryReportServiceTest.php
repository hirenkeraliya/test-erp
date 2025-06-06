<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\ReservedStock\Services\ReservedInventoryReportService;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseAmount;
use App\Models\ReservedStock;
use App\Models\Sale;
use App\Models\SaleItem;

test(
    'It calls getReservedInventoryReportReferenceNumber method and returns proper response',
    function (): void {
        $companyId = 1;
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
        ]);
        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
        ]);
        $purchaseAmount = PurchaseAmount::factory()->make([
            'id' => 1,
        ]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => $purchaseAmount->id,
            'batch_id' => null,
            'quantity' => 10,
            'reserved_stock' => 10,
        ]);
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => $sale->id,
            'product_id' => fn (): int => 1,
            'derivative_id' => fn (): int => 1,
        ]);
        $saleItem->sale = $sale;
        $reservedStock = ReservedStock::factory()->make([
            'inventory_id' => $inventory->id,
            'inventory_unit_id' => $inventoryUnit->id,
            'affected_by_id' => $saleItem->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'quantity' => 10.0,
            'notes' => 'Test',
        ]);
        $reservedStock->affectedBy = $saleItem;
        $reservedInventoryReportService = new ReservedInventoryReportService();
        $response = $reservedInventoryReportService->getReservedInventoryReportReferenceNumber($reservedStock);
        expect($response)->toHaveKey('message', 'Sale: ' . $sale->getOfflineSaleId());
    }
);

test(
    'It calls getStoresAndWarehouses method and returns proper response',
    function (): void {
        $companyId = 1;

        Location::factory()->make([
            'company_id' => fn (): int => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        Location::factory()->make([
            'company_id' => fn (): int => $companyId,
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
                ->once();
            $mock->shouldReceive('getWithBasicColumnsOfWarehouse')
                ->once();
        });

        $reservedInventoryReportService = new ReservedInventoryReportService();
        $response = $reservedInventoryReportService->getStoresAndWarehouses($companyId);
        expect($response)->toBeArray();
    }
);
