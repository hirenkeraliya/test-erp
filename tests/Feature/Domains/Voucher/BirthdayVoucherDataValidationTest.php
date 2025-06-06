<?php

declare(strict_types=1);

use App\Domains\Voucher\DataObjects\BirthdayVoucherData;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all birthday voucher details are provided', function (): void {
    $request = new Request([
        'voucher_configuration_id' => 1,
        'discount_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        'number' => '123',
        'minimum_spend_amount' => 12,
        'expired_at' => '2025-01-01',
        'flat_amount' => 1,
        'happened_at' => '2025-01-01 01:01:01',
    ]);

    $request->validate(BirthdayVoucherData::rules());

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'voucher_configuration_id' => null,
        'discount_type' => null,
        'number' => null,
        'minimum_spend_amount' => null,
        'expired_at' => null,
        'flat_amount' => null,
        'happened_at' => null,
    ]);

    $request->validate(BirthdayVoucherData::rules());
})->throws(ValidationException::class);
