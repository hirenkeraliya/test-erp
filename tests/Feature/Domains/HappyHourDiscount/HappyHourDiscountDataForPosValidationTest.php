<?php

declare(strict_types=1);

use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

function getRequestData(): Request
{
    return new Request([
        'offline_id' => '123456',
        'product_type_id' => ProductTypes::BRAND->value,
        'name' => 'abc',
        'new_price' => '500.30',
        'start_date' => '2024-01-04 04:25:50',
        'end_date' => '2024-01-04 04:50:50',
        'happened_at' => '2024-01-04 04:20:50',
        'store_manager_id' => 1,
        'store_manager_passcode' => '123456',
        'director_id' => null,
        'director_passcode' => null,
        'brand_ids' => [1],
    ]);
}

test('validation passes when all details are provided are valid', function (): void {
    $request = getRequestData();

    $cashier = Cashier::factory()->create();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $request->validate(HappyHourDiscountDataForPos::rules());

    $this->assertTrue(true);
});

test('cannot add happyHourDiscount with incomplete details', function (): void {
    $request = getRequestData();
    $request['offline_id'] = null;
    $request['name'] = null;
    $request['new_price'] = null;
    $request['start_date'] = null;
    $request['end_date'] = null;
    $request['happened_at'] = null;

    $cashier = Cashier::factory()->create();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $request->validate(HappyHourDiscountDataForPos::rules());
})->throws(ValidationException::class);

test('validation not passes when product_type_id are not valid', function (): void {
    $request = getRequestData();
    $request['product_type_id'] = 66;

    $cashier = Cashier::factory()->create();
    $request->setUserResolver(fn (): Cashier => $cashier);
    $request->validate(HappyHourDiscountDataForPos::rules());
})->throws(ValidationException::class);

test('validation not passes when start date and end date to be past compared to happened_at', function (): void {
    $request = getRequestData();
    $request['start_date'] = '2024-01-03 04:25:50';
    $request['end_date'] = '2024-01-04 04:10:50';
    $request['happened_at'] = '2024-01-04 04:20:50';

    $cashier = Cashier::factory()->create();
    $request->setUserResolver(fn (): Cashier => $cashier);
    $request->validate(HappyHourDiscountDataForPos::rules());
})->throws(ValidationException::class);

test('validation not passes when brand_ids are null', function (): void {
    $request = getRequestData();
    $request['brand_ids'] = [];

    $cashier = Cashier::factory()->create();
    $request->setUserResolver(fn (): Cashier => $cashier);
    $request->validate(HappyHourDiscountDataForPos::rules());
})->throws(ValidationException::class);
