<?php

declare(strict_types=1);

use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all birthday voucher details are provided', function (): void {
    $request = new Request([
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'loyalty_points' => 123,
    ]);

    $request->validate(LoyaltyPointVoucherData::rules());

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'voucher_configuration_id' => null,
        'member_id' => null,
        'loyalty_points' => null,
    ]);

    $request->validate(LoyaltyPointVoucherData::rules());
})->throws(ValidationException::class);
