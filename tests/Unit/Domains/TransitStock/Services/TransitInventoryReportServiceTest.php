<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\TransitStock\Services\TransitInventoryReportService;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Product;
use App\Models\PurchaseAmount;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\TransitStock;

test(
    'It calls getTransitInventoryReportReferenceNumber method and returns proper response',
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
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'stock_transfer_reason_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
        ]);
        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'package_type_id' => 1,
        ]);
        $stockTransferItem->stockTransfer = $stockTransfer;
        $transitStock = TransitStock::factory()->make([
            'inventory_id' => $inventory->id,
            'inventory_unit_id' => $inventoryUnit->id,
            'affected_by_id' => $stockTransferItem->id,
            'affected_by_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
            'quantity' => 10.0,
            'notes' => 'Test',
        ]);
        $transitStock->affectedBy = $stockTransferItem;
        $transitInventoryReportService = new TransitInventoryReportService();
        $response = $transitInventoryReportService->getTransitInventoryReportReferenceNumber($transitStock);
        expect($response)->toHaveKey('message', 'Stock Transfer: ' . $stockTransfer->id);
    }
);
