<?php

declare(strict_types=1);

use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountListDataForPos;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

function getHappyHourDiscountRequestData(): Request
{
    return new Request([
        'product_type_id' => '',
        'per_page' => 10,
        'page' => 1,
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
    ]);
}

test('validation passes when all details are provided are valid', function (): void {
    $request = getHappyHourDiscountRequestData();

    $cashier = Cashier::factory()->create();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $request->validate(HappyHourDiscountListDataForPos::rules());

    $this->assertTrue(true);
});

test('cannot add happyHourDiscount with incomplete details', function (): void {
    $request = getHappyHourDiscountRequestData();
    $request['per_page'] = null;
    $request['page'] = null;

    $cashier = Cashier::factory()->create();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $request->validate(HappyHourDiscountListDataForPos::rules());
})->throws(ValidationException::class);

test('validation not passes when product_type_id are not valid', function (): void {
    $request = getHappyHourDiscountRequestData();
    $request['product_type_id'] = 66;

    $cashier = Cashier::factory()->create();
    $request->setUserResolver(fn (): Cashier => $cashier);
    $request->validate(HappyHourDiscountListDataForPos::rules());
})->throws(ValidationException::class);
