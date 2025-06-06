<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;

test(
    'the getDeliveryOrdersStatusCount method calls and return the delivery order statuses array',
    function (): void {
        $companyId = 1;
        $requestParameter = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => '',
            'per_page' => '',
            'order_type' => '',
            'select_status' => '',
            'date_range' => '',
            'location_id' => '',
            'external_location_id' => '',
            'order_number' => '',
        ];

        $purchaseOrderFulfillment = PurchaseOrderFulfillment::factory()->make([
            'purchase_order_id' => 1,
        ]);

        $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use (
            $companyId,
            $requestParameter,
            $purchaseOrderFulfillment
        ): void {
            $mock->shouldReceive('allDeliveryOrdersStatusCount')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(collect([$purchaseOrderFulfillment]));
        });

        $purchaseOrderFulfillmentService = new PurchaseOrderFulfillmentService();

        $response = $purchaseOrderFulfillmentService->getDeliveryOrdersStatusCount($requestParameter, $companyId);

        expect($response)->toBeArray();
    }
);

test(
    'the getOrderNumbers method calls and return the all order numbers array',
    function (): void {
        $companyId = 1;
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'ABC',
            'type_id' => LocationTypes::STORE->value,
        ]);
        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => $location->id,
            'created_by_company_id' => 1,
            'status' => Statuses::OPENED->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
            'order_type' => OrderTypes::SALES_ORDER->value,
        ]);
        $purchaseOrderFulfillment = PurchaseOrderFulfillment::factory()->make([
            'purchase_order_id' => 1,
        ]);
        $purchaseOrder->fulfillments = collect([$purchaseOrderFulfillment]);
        $response = PurchaseOrderFulfillmentService::getOrderNumbers(
            $purchaseOrder,
            $purchaseOrderFulfillment->delivery_order_number
        );
        expect($response)->toBeArray();
    }
);
