<?php

declare(strict_types=1);

use App\Domains\ExternalConnection\Enums\Statuses;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\Statuses as EnumsStatuses;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses as EnumStatuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Config;

test('Purchase Order can be added', function (): void {
    $purchaseOrderQueries = new PurchaseOrderQueries();

    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $externalLocation = ExternalLocation::factory()->create([
        'external_company_id' => $externalCompany->id,
    ]);

    $companyId = Company::Factory()->create()->id;

    $locationId = Location::factory()->create()->id;

    $purchaseOrderData = [
        'external_company_id' => $externalCompany->id,
        'external_location_id' => $externalLocation->id,
        'location_id' => $locationId,
        'company_id' => $companyId,
        'reference_number' => null,
        'remarks' => null,
        'attention' => null,
        'require_date' => null,
        'order_type' => 1,
    ];

    $purchaseOrderQueries->addNew($purchaseOrderData);

    $this->assertDatabaseHas('purchase_orders', $purchaseOrderData);
});

test('purchase order can be fetch', function (): void {
    $companyId = Company::factory()->create()->id;
    $purchaseOrderQueries = new PurchaseOrderQueries();

    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $externalLocation = ExternalLocation::factory()->create([
        'external_company_id' => $externalCompany->id,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $purchaseOrder = PurchaseOrder::factory()->create([
        'external_company_id' => $externalCompany->id,
        'external_location_id' => $externalLocation->id,
        'company_id' => $companyId,
        'location_id' => $locationId,
    ]);

    $response = $purchaseOrderQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'select_status' => null,
        'location_id' => null,
        'external_location_id' => null,
        'order_type' => null,
        'date_range' => null,
        'order_number' => null,
    ], $companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('external_company_id', $purchaseOrder->external_company_id)
        ->toHaveKey('external_location_id', $purchaseOrder->external_location_id);

    $externalCompany->delete();
    $response = $purchaseOrderQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'select_status' => null,
        'location_id' => null,
        'external_location_id' => null,
        'order_type' => null,
        'date_range' => null,
        'order_number' => null,
    ], $companyId);

    expect($response->total())->toBe(0);
});

test('getDashboardStatusCount method call and return proper response', function (): void {
    $companyId = Company::factory()->create()->id;
    $locationId = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;
    $purchaseOrderId = PurchaseOrder::factory()->create([
        'company_id' => $companyId,
        'location_id' => $locationId,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        'status' => EnumStatuses::CLOSED->value,
    ])->id;
    $purchaseOrderQueries = new PurchaseOrderQueries();
    $data = [
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        'location_id' => $locationId,
    ];
    $response = $purchaseOrderQueries->getDashboardStatusCount($data, $companyId);
    expect($response->first()->toArray())
            ->toHaveKey('status', EnumStatuses::CLOSED->value)
            ->toHaveKey('count', 1);
});

test('fetch inter company stock transfer with stock transfer items by date and location', function (): void {
    $purchaseOrderQueries = new PurchaseOrderQueries();
    $company = Company::factory()->create();
    $locationId = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;
    $externalCompany = ExternalCompany::factory()->create();
    $externalLocationId = ExternalLocation::factory()->create()->id;
    $productId = Product::factory()->create([
        'company_id' => $company->id,
        'is_non_inventory' => false,
    ])->id;
    $currentDate = now();
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'created_at' => $currentDate->format('Y-m-d'),
        'status' => EnumStatuses::DRAFT->value,
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
        'location_id' => $locationId,
        'external_company_id' => $externalCompany->id,
        'external_location_id' => $externalLocationId,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $productId,
    ]);
    $filterData = [
        'location_id' => $purchaseOrder->location_id,
        'transfer_type' => InterCompanyTransferType::TRANSFER_REQUEST->value,
        'product_id' => null,
        'article_number' => null,
        'external_location_id' => $purchaseOrder->external_location_id,
        'external_company_id' => $purchaseOrder->external_company_id,
        'date_range' => [$currentDate->subDay()->format('Y-m-d'), $currentDate->addDay()->format('Y-m-d')],
    ];
    $response = $purchaseOrderQueries->getByDateAndLocationWithStockTransfer($filterData, $company->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $purchaseOrder->id)
        ->toHaveKey('reference_number', $purchaseOrder->reference_number)
        ->toHaveKeys(['items']);
});

test(
    'fetch inter company stock transfer with stock transfer items and products and package type by date and location',
    function (): void {
        $purchaseOrderQueries = new PurchaseOrderQueries();
        $company = Company::factory()->create();
        $locationId = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;
        $externalCompany = ExternalCompany::factory()->create();
        $externalLocationId = ExternalLocation::factory()->create()->id;
        $productId = Product::factory()->create([
            'company_id' => $company->id,
            'is_non_inventory' => false,
        ])->id;
        $currentDate = now();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $company->id,
            'created_at' => $currentDate->format('Y-m-d'),
            'status' => EnumStatuses::DRAFT->value,
            'order_type' => OrderTypes::TRANSFER_REQUEST->value,
            'location_id' => $locationId,
            'external_company_id' => $externalCompany->id,
            'external_location_id' => $externalLocationId,
        ]);
        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $productId,
        ]);
        $filterData = [
            'location_id' => $purchaseOrder->location_id,
            'transfer_type' => InterCompanyTransferType::TRANSFER_REQUEST->value,
            'product_id' => null,
            'article_number' => null,
            'external_location_id' => $purchaseOrder->external_location_id,
            'external_company_id' => $purchaseOrder->external_company_id,
            'date_range' => [$currentDate->subDay()->format('Y-m-d'), $currentDate->addDay()->format('Y-m-d')],
        ];
        $response = $purchaseOrderQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $company->id
        );
        expect($response->first()->toArray())
            ->toHaveKey('id', $purchaseOrder->id)
            ->toHaveKey('reference_number', $purchaseOrder->reference_number)
            ->toHaveKeys(['items']);
    }
);

test('purchase order can be export', function (): void {
    $companyId = Company::factory()->create()->id;
    $purchaseOrderQueries = new PurchaseOrderQueries();
    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);
    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);
    $externalLocation = ExternalLocation::factory()->create([
        'external_company_id' => $externalCompany->id,
    ]);
    $locationId = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;
    $purchaseOrder = PurchaseOrder::factory()->create([
        'external_company_id' => $externalCompany->id,
        'external_location_id' => $externalLocation->id,
        'company_id' => $companyId,
        'location_id' => $locationId,
    ]);
    $response = $purchaseOrderQueries->exportPurchaseOrder([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'select_status' => null,
        'location_id' => null,
        'external_location_id' => null,
        'order_type' => null,
        'date_range' => null,
        'order_number' => null,
    ], $companyId);
    expect($response->first()->toArray())
        ->toHaveKey('external_company_id', $purchaseOrder->external_company_id)
        ->toHaveKey('external_location_id', $purchaseOrder->external_location_id);
});

test(
    'it calls the getByPurchaseOrderIdForCreateDo of the purchase order queries class as return response as expected when product variant is true',
    function (): void {
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
            'status' => EnumsStatuses::ACTIVE->value,
            'is_non_inventory' => false,
            'is_non_selling_item' => false,
            'is_available_in_pos' => true,
            'is_available_in_ecommerce' => false,
            'master_product_id' => $masterProduct->id,
        ]);

        $purchaseOrderQueries = new PurchaseOrderQueries();
        $externalConnection = ExternalConnection::factory()->create([
            'status' => Statuses::APPROVED->value,
        ]);
        $externalCompany = ExternalCompany::factory()->create([
            'external_connection_id' => $externalConnection->id,
        ]);
        $externalLocation = ExternalLocation::factory()->create([
            'external_company_id' => $externalCompany->id,
        ]);
        $locationId = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ])->id;
        $purchaseOrder = PurchaseOrder::factory()->create([
            'external_company_id' => $externalCompany->id,
            'external_location_id' => $externalLocation->id,
            'company_id' => $companyId,
            'location_id' => $locationId,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'rejected_quantity' => 5,
            'transferred_quantity' => 5,
        ]);

        $response = $purchaseOrderQueries->getByPurchaseOrderIdForCreateDo($purchaseOrder->getKey(), $companyId);

        expect($response->toArray())
            ->toHaveKey('external_location_id', $purchaseOrder->external_location_id)
            ->toHaveKey('items.0.product.upc', $product->upc)
            ->toHaveKey('items.0.product.master_product.name', $masterProduct->name);
    }
);

test(
    'it calls the getByPurchaseOrderIdForCreateDo of the purchase order queries class as return response as expected when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = Company::factory()->create()->id;
        $purchaseOrderQueries = new PurchaseOrderQueries();
        $externalConnection = ExternalConnection::factory()->create([
            'status' => Statuses::APPROVED->value,
        ]);
        $externalCompany = ExternalCompany::factory()->create([
            'external_connection_id' => $externalConnection->id,
        ]);
        $externalLocation = ExternalLocation::factory()->create([
            'external_company_id' => $externalCompany->id,
        ]);
        $locationId = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ])->id;
        $purchaseOrder = PurchaseOrder::factory()->create([
            'external_company_id' => $externalCompany->id,
            'external_location_id' => $externalLocation->id,
            'company_id' => $companyId,
            'location_id' => $locationId,
        ]);
        $response = $purchaseOrderQueries->getByPurchaseOrderIdForCreateDo($purchaseOrder->getKey(), $companyId);
        expect($response->toArray())
            ->toHaveKey('external_location_id', $purchaseOrder->external_location_id);
    }
);

test(
    'it calls the getByPurchaseOrderIdAndLocation of the purchase order queries class as return response as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;
        $purchaseOrderQueries = new PurchaseOrderQueries();
        $externalConnection = ExternalConnection::factory()->create([
            'status' => Statuses::APPROVED->value,
        ]);
        $externalCompany = ExternalCompany::factory()->create([
            'external_connection_id' => $externalConnection->id,
        ]);
        $externalLocation = ExternalLocation::factory()->create([
            'external_company_id' => $externalCompany->id,
        ]);
        $locationId = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ])->id;
        $purchaseOrder = PurchaseOrder::factory()->create([
            'external_company_id' => $externalCompany->id,
            'external_location_id' => $externalLocation->id,
            'company_id' => $companyId,
            'location_id' => $locationId,
        ]);
        $response = $purchaseOrderQueries->getByPurchaseOrderIdAndLocation(
            $purchaseOrder->getKey(),
            $companyId,
            $locationId,
        );
        expect($response->toArray())
            ->toHaveKey('external_location_id', $purchaseOrder->external_location_id);
    }
);
