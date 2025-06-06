<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Services\OrderShipmentService;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderAddress;

test(
    'call prepareShipmentData method get proper response',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => null,
        ]);
        $company->country = null;
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
        ]);
        $order = Order::factory()->make([
            'store_manager_id' => 1,
            'location_id' => $location->id,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'pickup_store_id' => null,
        ]);
        $orderAddress = OrderAddress::factory()->make([
            'order_id' => $order->id,
            'type_id' => OrderAddressesType::BILLING_ADDRESS->value,
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
        ]);
        $orderAddress->city = null;
        $orderAddress->country = null;
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'created_location_id' => $location->id,
        ]);
        $order->location = $location;
        $order->member = $member;
        $order->billingAddress = $orderAddress;
        $order->shippingAddress = $orderAddress;
        $location->company = $company;
        $orderShipmentService = resolve(OrderShipmentService::class);
        $response = $orderShipmentService->prepareShipmentData($order);
        expect($response)->toBeArray();
    }
);
