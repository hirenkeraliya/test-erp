<?php

declare(strict_types=1);

use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\HappyHourDiscount\DataPreparer\HappyHourDiscountDataPreparer;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Models\Employee;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\StoreManager;

beforeEach(function (): void {
    $this->companyId = 1;
    $this->counterUpdateId = 1;
    $this->locationId = 1;

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'designation_id' => 1,
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->happyHourDiscount = HappyHourDiscount::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'location_id' => $this->locationId,
        'product_type_id' => ProductTypes::BRAND->value,
    ]);

    $this->happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->make([
        'offline_id' => '1323sfff',
        'happy_hour_discount_id' => $this->happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdateId,
        'authorizer_id' => $this->storeManager->id,
        'authorizer_type' => AuthorizerTypes::STORE_MANAGER->name,
        'happened_at' => now()->format('Y-m-d h:i:s'),
    ]);

    $this->happyHourDiscountTransaction->authorizer = $this->storeManager;
    $this->happyHourDiscountTransaction->authorizer->employee = $this->employee;

    $this->happyHourDiscount->happyHourDiscountTransactions = collect([$this->happyHourDiscountTransaction]);
});

test('getOfflineIds method return each offline ids', function (): void {
    $response = HappyHourDiscountDataPreparer::getOfflineIds($this->happyHourDiscount->happyHourDiscountTransactions);

    expect($response)->toBeArray();
    $this->assertEquals($this->happyHourDiscountTransaction->offline_id, $response[0]);
});

test('getHappenedAtDates method get each happened at dates', function (): void {
    $response = HappyHourDiscountDataPreparer::getHappenedAtDates(
        $this->happyHourDiscount->happyHourDiscountTransactions
    );

    expect($response)->toBeArray();
});

test('getHappenedAtDatesForApi method get each happened at dates', function (): void {
    $response = HappyHourDiscountDataPreparer::getHappenedAtDatesForApi(
        $this->happyHourDiscount->happyHourDiscountTransactions
    );

    expect($response)->toBeArray();
    $this->assertEquals($this->happyHourDiscountTransaction->happened_at, $response[0]);
});

test('getAuthorizerNames method get each authorizer names', function (): void {
    $response = HappyHourDiscountDataPreparer::getAuthorizerNames(
        $this->happyHourDiscount->happyHourDiscountTransactions
    );

    $name = $this->employee->getFullName() . ' (' . $this->happyHourDiscountTransaction->authorizer_type . ')';

    expect($response)->toBeArray();
    $this->assertEquals($name, $response[0]);
});
