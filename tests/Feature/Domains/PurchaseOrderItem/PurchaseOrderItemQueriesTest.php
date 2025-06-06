<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->externalConnectionId = ExternalConnection::factory()->create()->id;

    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $this->externalConnectionId,
        'external_company_id' => $this->companyId,
    ]);

    $this->externalLocation = ExternalLocation::factory()->create([
        'external_company_id' => $this->externalCompany->id,
        'external_location_id' => $this->location->id,
    ]);

    $this->purchaseOrder = PurchaseOrder::factory()->create([
        'external_company_id' => $this->externalCompany->id,
        'external_location_id' => $this->externalLocation->id,
        'company_id' => $this->companyId,
        'location_id' => $this->location->id,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->companyId,
        'purchase_cost' => 20,
    ]);

    $this->purchaseOrderItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $this->purchaseOrder->id,
        'product_id' => $this->product->id,
        'purchase_cost' => 10,
    ]);
});

test('fetch purchase order item by purchase id when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $purchaseOrderItemQueries = new PurchaseOrderItemQueries();

    $response = $purchaseOrderItemQueries->getByPurchaseOrderId(
        $this->purchaseOrderItem->purchase_order_id,
        $this->companyId
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->purchaseOrderItem->id)
        ->toHaveKey('product_id', $this->purchaseOrderItem->product_id)
        ->toHaveKey('quantity', $this->purchaseOrderItem->quantity)
        ->toHaveKey('remarks', $this->purchaseOrderItem->remarks);
});

test('fetch purchase order item by purchase id when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
        'compound_product_name' => 'ABCD131333',
        'code' => '131313',
        'upc' => 'wrwrwr',
        'article_number' => '12346644',
        'status' => ProductStatuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $this->purchaseOrder->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'rejected_quantity' => 5,
        'transferred_quantity' => 5,
    ]);

    $purchaseOrderItemQueries = new PurchaseOrderItemQueries();

    $response = $purchaseOrderItemQueries->getByPurchaseOrderId(
        $purchaseOrderItem->purchase_order_id,
        $this->companyId
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->purchaseOrderItem->id)
        ->toHaveKey('product_id', $this->purchaseOrderItem->product_id)
        ->toHaveKey('quantity', $this->purchaseOrderItem->quantity)
        ->toHaveKey('remarks', $this->purchaseOrderItem->remarks);
});

test(
    'fetch purchase order items by purchase order id with pagination when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $filterData = [
            'id' => $this->purchaseOrder->id,
            'per_page' => 10,
            'page' => 1,
            'location_id' => $this->location->id,
            'search_text' => null,
        ];

        $purchaseOrderItemQueries = new PurchaseOrderItemQueries();
        $response = $purchaseOrderItemQueries->getPaginatedByPurchaseOrderId($filterData, $this->companyId);

        expect($response[0])
            ->toHaveKeys(
                [
                    'id',
                    'purchase_order_id',
                    'product_id',
                    'quantity',
                    'rejected_quantity',
                    'transferred_quantity',
                    'price_per_unit',
                    'remarks',
                    'product',
                    'product.color',
                    'product.size',
                ]
            );
    }
);

test('fetch purchase order items by purchase order id with pagination when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = Company::factory()->create()->id;

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'compound_product_name' => 'ABCD131333',
        'code' => '131313',
        'upc' => 'wrwrwr',
        'article_number' => '12346644',
        'status' => ProductStatuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $this->purchaseOrder->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'rejected_quantity' => 5,
        'transferred_quantity' => 5,
    ]);

    $filterData = [
        'id' => $this->purchaseOrder->id,
        'per_page' => 10,
        'page' => 1,
        'location_id' => $this->location->id,
        'search_text' => 'wrwrwr',
    ];

    $purchaseOrderItemQueries = new PurchaseOrderItemQueries();
    $response = $purchaseOrderItemQueries->getPaginatedByPurchaseOrderId($filterData, $this->companyId);

    expect($response[0])
        ->toHaveKeys(
            [
                'id',
                'purchase_order_id',
                'product_id',
                'quantity',
                'rejected_quantity',
                'transferred_quantity',
                'price_per_unit',
                'remarks',
                'product',
                'product.master_product',
            ]
        );
});

test('fetch inter company stock transfer items by date and location', function (): void {
    $company = Company::factory()->create();
    $productId = Product::factory()->create([
        'company_id' => $company->id,
        'is_non_inventory' => false,
    ])->id;
    $currentDate = now();

    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'created_at' => $currentDate->format('Y-m-d'),
        'status' => Statuses::DRAFT->value,
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
        'location_id' => $this->location->id,
        'external_company_id' => $this->externalCompany->id,
        'external_location_id' => $this->externalLocation->id,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $productId,
    ]);

    $filterData = [
        'location_id' => $purchaseOrder->location_id,
        'transfer_type' => InterCompanyTransferType::TRANSFER_REQUEST->value,
        'date_range' => [$currentDate->subDay()->format('Y-m-d'), $currentDate->addDay()->format('Y-m-d')],
        'product_id' => null,
        'product_collection_id' => null,
        'article_number' => null,
        'external_location_id' => $purchaseOrder->external_location_id,
        'external_company_id' => $purchaseOrder->external_company_id,
    ];

    $purchaseOrderItemQueries = new PurchaseOrderItemQueries();
    $response = $purchaseOrderItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $purchaseOrderItem->id)
        ->toHaveKey('product_id', $purchaseOrderItem->product_id)
        ->toHaveKey('purchase_order_id', $purchaseOrder->id)
        ->toHaveKey('quantity', $purchaseOrderItem->quantity)
        ->toHaveKeys(['product.color', 'product.size']);
});

test('updatePurchaseCostOfDraftStatus method is update the total cost in purchase order item', function (): void {
    $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->create([
        'purchase_order_id' => $this->purchaseOrder->id,
        'company_id' => $this->companyId,
    ]);

    $purchaseOrderFulfillment = PurchaseOrderFulfillment::factory()->create([
        'purchase_order_id' => $this->purchaseOrder->id,
        'purchase_order_invoice_id' => $purchaseOrderInvoice->id,
    ]);

    PurchaseOrderFulfillmentItem::factory()->create([
        'purchase_order_item_id' => $this->purchaseOrderItem->id,
        'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
    ]);

    $purchaseOrderItemQueries = new PurchaseOrderItemQueries();
    $purchaseOrderItemQueries->updatePurchaseCostOfDraftStatus($this->purchaseOrder->id);

    $this->assertDatabaseHas('purchase_order_items', [
        'id' => $this->purchaseOrderItem->id,
        'product_id' => $this->product->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'purchase_cost' => $this->product->purchase_cost,
    ]);
});
