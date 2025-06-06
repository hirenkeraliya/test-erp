<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Http\Controllers\WarehouseManager\DashboardController;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;

test('getPurchaseRequest method returns required data', function (): void {
    $locationId = 1;

    setWarehouseManagerWarehouseIdInSession();
    setWarehouseManagerWarehouseCompanyIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
    ]);

    $purchaseOrder->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getPurchaseRequest();

    expect($response['purchaseRequests'])->not->toBeEmpty();
});

test('getTransferRequest method returns required data', function (): void {
    $locationId = 1;

    setWarehouseManagerWarehouseIdInSession();
    setWarehouseManagerWarehouseCompanyIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
    ]);

    $purchaseOrder->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTransferRequest();
    expect($response['transferRequests'])->not->toBeEmpty();
});

test('getSalesOrder method returns required data', function (): void {
    $locationId = 1;

    setWarehouseManagerWarehouseIdInSession();
    setWarehouseManagerWarehouseCompanyIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ]);

    $purchaseOrder->count = 1;

    $fulfillments = PurchaseOrderFulfillment::factory()->make([
        'id' => 1,
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_invoice_id' => 1,
        'delivery_order_number' => '1234567',
    ]);

    $fulfillments->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($data, $fulfillments): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$fulfillments]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getSalesOrder();

    expect($response['salesOrders'])->not->toBeEmpty();
    expect($response['salesDeliveryOrders'])->not->toBeEmpty();
});

test('getPurchaseOrder method returns required data', function (): void {
    $locationId = 1;

    setWarehouseManagerWarehouseIdInSession();
    setWarehouseManagerWarehouseCompanyIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::PURCHASE_ORDER->value,
    ]);

    $purchaseOrder->count = 1;

    $fulfillments = PurchaseOrderFulfillment::factory()->make([
        'id' => 1,
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_invoice_id' => 1,
        'delivery_order_number' => '1234567',
    ]);

    $fulfillments->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::PURCHASE_ORDER->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($data, $fulfillments): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$fulfillments]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getPurchaseOrder();
    expect($response['purchaseOrders'])->not->toBeEmpty();
    expect($response['purchaseDeliveryOrders'])->not->toBeEmpty();
});

it('getNegativeStockStockOverview it returns the negative stocks', function (): void {
    setWarehouseManagerWarehouseIdInSession();
    setWarehouseManagerWarehouseCompanyIdInSession();

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getNegativeStockItems')
            ->andReturn(10);
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getNegativeStockStockOverview();

    expect($response['negativeStockItemCount'])->toBe(10);
});
