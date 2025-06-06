<?php

declare(strict_types=1);

use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all CompleteCreditSaleData details are provided', function (): void {
    $request = new Request([
        'happened_at' => now()->format('Y-m-d H:i:s'),
        'payments' => [
            [
                'type_id' => StaticPaymentTypes::CASH->value,
                'amount' => 10,
                'currency_id' => 1,
                'current_currency_rate' => 1,
                'currency_amount' => 10,
            ],
        ],
    ]);

    $request->validate(CompleteCreditSaleData::rules());

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'happened_at' => null,
        'payments' => null,
    ]);

    $request->validate(CompleteCreditSaleData::rules());
})->throws(ValidationException::class);
