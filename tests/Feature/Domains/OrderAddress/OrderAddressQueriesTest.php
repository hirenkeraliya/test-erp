<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\DataObjects\OrderECommerceAddressData;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\State;
use App\Models\StoreManager;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Order Test',
    ]);

    $this->date = Carbon::now();

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->getKey(),
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employee->getKey(),
    ]);

    $this->location = Location::factory()->create([
        'company_id' => $this->company->getKey(),
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->getKey(),
        'created_location_id' => $this->location->getKey(),
    ]);

    $this->order = Order::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
        'created_at' => $this->date,
    ]);

    $this->orderAddressQueries = new OrderAddressQueries();
});

test('addNewAddress method returns the order', function (): void {
    $countryId = Country::factory()->create()->id;
    $stateId = State::factory()->create([
        'country_id' => $countryId,
    ])->id;
    $cityId = City::factory()->create([
        'country_id' => $countryId,
        'state_id' => $stateId,
    ])->id;

    $orderAddress = OrderAddress::factory()->make([
        'country_id' => $countryId,
        'state_id' => $stateId,
        'city_id' => $cityId,
    ])->toArray();
    unset($orderAddress['order_id'], $orderAddress['type_id']);

    $response = $this->orderAddressQueries->addNewAddress(
        $orderAddress,
        [
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'country_name' => null,
            'state_name' => null,
            'city_name' => null,
        ],
        $this->order->getKey(),
        OrderAddressesType::BILLING_ADDRESS->value
    );

    expect($response)->toBeInstanceOf(OrderAddress::class);
    expect($response)->toHaveKeys([...$this->orderAddressQueries->getBasicColumns()]);
});

test('getById can return the OrderAddress By id', function (): void {
    $countryId = Country::factory()->create()->id;
    $stateId = State::factory()->create([
        'country_id' => $countryId,
    ])->id;
    $cityId = City::factory()->create([
        'country_id' => $countryId,
        'state_id' => $stateId,
    ])->id;

    $orderAddress = OrderAddress::factory()->create([
        'order_id' => $this->order->getKey(),
        'type_id' => OrderAddressesType::BILLING_ADDRESS->value,
        'country_id' => $countryId,
        'state_id' => $stateId,
        'city_id' => $cityId,
    ]);

    $response = $this->orderAddressQueries->getById($orderAddress->getKey());

    expect($response)->toBeInstanceOf(OrderAddress::class);
    expect($response)->toHaveKeys([...$this->orderAddressQueries->getBasicColumns()]);
});

test(
    'getOrderShippingAddress method will return the shipping address by Order ID.',
    function (): void {
        $orderAddress = OrderAddress::factory()->create([
            'order_id' => $this->order->getKey(),
            'type_id' => 1,
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'area_code' => '12345',
        ]);

        $typeId = OrderAddressesType::SHIPPING_ADDRESS->value;
        $response = $this->orderAddressQueries->getOrderAddress((string) $this->order->id, $typeId);

        expect($response->address_line_1)->toBe($orderAddress->address_line_1);
        expect($response->address_line_2)->toBe($orderAddress->address_line_2);
        expect($response->first_name)->toBe($orderAddress->first_name);
        expect($response->last_name)->toBe($orderAddress->last_name);
        expect($response->phone)->toBe($orderAddress->phone);
        expect($response->area_code)->toBe($orderAddress->area_code);
    }
);

test(
    'getOrderBillingAddress method will return the Billing address by Order ID.',
    function (): void {
        $orderAddress = OrderAddress::factory()->create([
            'order_id' => $this->order->id,
            'type_id' => 2,
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'area_code' => '12345',
        ]);
        $typeId = OrderAddressesType::BILLING_ADDRESS->value;
        $response = $this->orderAddressQueries->getOrderAddress((string) $this->order->id, $typeId);

        expect($response->address_line_1)->toBe($orderAddress->address_line_1);
        expect($response->address_line_2)->toBe($orderAddress->address_line_2);
        expect($response->first_name)->toBe($orderAddress->first_name);
        expect($response->last_name)->toBe($orderAddress->last_name);
        expect($response->phone)->toBe($orderAddress->phone);
        expect($response->area_code)->toBe($orderAddress->area_code);
    }
);

test('updateOrderAddressECommerce can update the address data', function (): void {
    OrderAddress::factory()->create([
        'order_id' => $this->order->id,
        'type_id' => 1,
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '1234567890',
        'area_code' => '12345',
    ]);
    City::factory()->create([
        'name' => 'Mountain View Gardens',
        'state_id' => 1,
    ]);

    $orderECommerceAddressData = new OrderECommerceAddressData(
        1,
        'john',
        'doe',
        '6012345678',
        '1',
        'Mountain View Gardens',
        '123 Main St',
        'Apt 4B',
    );
    $orderAddressId = 1;

    $response = $this->orderAddressQueries->updateOrderAddressECommerce($orderECommerceAddressData, $orderAddressId);
    expect($response)->toBeTrue();
});
