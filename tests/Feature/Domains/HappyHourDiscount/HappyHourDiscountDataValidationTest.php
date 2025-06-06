<?php

declare(strict_types=1);

use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountData;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all details are provided are valid', function (): void {
    setCompanyIdInSession();

    $request = new Request([
        'product_type_id' => ProductTypes::ALL->value,
        'name' => 'abc',
        'new_price' => '500.30',
        'start_date' => '2024-01-04 04:25:50',
        'end_date' => '2024-01-04 04:50:50',
    ]);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $request->validate(HappyHourDiscountData::rules($request));

    $this->assertTrue(true);
});

test('cannot add happyHourDiscount with incomplete details', function (): void {
    setCompanyIdInSession();

    $request = new Request([
        'name' => '',
        'new_price' => '',
        'start_date' => '',
        'end_date' => '',
    ]);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $request->validate(HappyHourDiscountData::rules($request));
})->throws(ValidationException::class);
