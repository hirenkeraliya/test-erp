<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    $this->externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $externalLocation = ExternalLocation::factory()->create([
        'external_company_id' => $this->externalCompany->id,
    ]);

    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->companyId,
        'external_company_id' => $this->externalCompany->id,
        'external_location_id' => $externalLocation->id,
        'location_id' => $location->id,
    ]);

    PurchaseOrderFulfillment::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
    ]);
});

test('getDashboardStatusCount method call and return proper response', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $purchaseOrderId = PurchaseOrder::factory()->create([
        'company_id' => $this->companyId,
        'location_id' => $locationId,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        'status' => Statuses::CLOSED->value,
    ])->id;

    PurchaseOrderFulfillment::factory()->create([
        'purchase_order_id' => $purchaseOrderId,
        'status' => FulfillmentStatuses::DRAFT->value,
    ]);

    $data = [
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        'location_id' => $locationId,
    ];

    $purchaseOrderFulfillmentQueries = new PurchaseOrderFulfillmentQueries();

    $response = $purchaseOrderFulfillmentQueries->getDashboardStatusCount($data, $this->companyId);

    expect($response->first()->toArray())
            ->toHaveKey('status', FulfillmentStatuses::DRAFT->value)
            ->toHaveKey('count', 1);
});

test('it is deliveryOrderListQuery method call the purchaseOrderFulfillment query get the lists', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
        'per_page' => '',
        'select_status' => '',
        'select_order_type' => '',
        'date_range' => '',
        'location_type' => null,
        'location_id' => null,
    ];

    $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
    $response = $purchaseOrderFulfillmentQueries->deliveryOrderListQuery($filterData, $this->companyId);
    expect($response->first()->toArray())
        ->toHaveKeys([
            'id', 'purchase_order_id', 'delivery_order_number', 'status', 'transactions', 'purchase_order', 'purchase_order.parent_purchase_order', 'purchase_order.child_purchase_order',
        ]);

    $this->externalCompany->delete();
    $response = $purchaseOrderFulfillmentQueries->deliveryOrderListQuery($filterData, $this->companyId);

    expect($response->count())->toBe(0);
});

test(
    'it is allDeliveryOrdersStatusCount method call the purchaseOrderFulfillment query return the delivery order status with count',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => '',
            'per_page' => '',
            'select_status' => '',
            'select_order_type' => '',
            'date_range' => '',
            'location_type' => null,
            'location_id' => null,
        ];

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $response = $purchaseOrderFulfillmentQueries->allDeliveryOrdersStatusCount($filterData, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKeys(['status', 'count']);
    }
);
