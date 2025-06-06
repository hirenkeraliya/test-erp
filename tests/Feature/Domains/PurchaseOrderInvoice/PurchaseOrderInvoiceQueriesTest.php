<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderInvoice;

test('getPurchaseOrderInvoicesForReport', function (): void {
    $companyId = Company::Factory()->create()->id;
    $locationId = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $companyId,
        'location_id' => $locationId,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $companyId,
        'is_non_inventory' => false,
    ])->id;

    $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'created_by_company_id' => $companyId,
        'company_id' => $companyId,
        'created_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $purchaseOrderFulfillment = PurchaseOrderFulfillment::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_invoice_id' => $purchaseOrderInvoice->id,
    ]);

    PurchaseOrderFulfillmentItem::factory()->create([
        'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
        'product_id' => $productId,
    ]);
    $filterData = [
        'location_id' => $locationId,
        'date_range' => [now()->format('Y-m-d'), now()->format('Y-m-d')],
        'external_location_id' => null,
        'external_company_id' => null,
        'product_id' => null,
        'article_number' => null,
        'product_collection_id' => null,
    ];
    $purchaseOrderInvoiceQueries = new PurchaseOrderInvoiceQueries();
    $response = $purchaseOrderInvoiceQueries->getPurchaseOrderInvoicesForReport($filterData, $companyId);
    expect($response->first()->toArray())
        ->toHaveKey('purchase_order_id', $purchaseOrder->id)
        ->toHaveKey('status', $purchaseOrderInvoice->status)
        ->toHaveKeys(['invoice_number', 'created_by_company_id', 'fulfillments']);
});
