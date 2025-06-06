<?php

declare(strict_types=1);

use App\Domains\Cashier\DataObjects\CashierChangePinData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'An exception is thrown if the cashier new pin and confirmation new pin are not same and new pin cannot be greater than 4 character',
    function (string $newPin, $confirmPin): void {
        $request = new Request([
            'new_pin' => $newPin,
            'new_pin_confirmation' => $confirmPin,
        ]);

        CashierChangePinData::validate($request);
    }
)->with([['', ''], ['11', '11'], ['1111', '1122']])->throws(ValidationException::class);

test('Cashier change pin request is validated.', function (): void {
    $request = new Request([
        'new_pin' => '1111',
        'new_pin_confirmation' => '1111',
    ]);

    CashierChangePinData::validate($request);
    $this->assertTrue(true);
});
