<?php

declare(strict_types=1);

use App\Domains\Sale\DataObjects\CancelLayawaySaleData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all CancelLayawaySaleData details are provided', function (): void {
    $request = new Request([
        'store_manager_id' => 1,
        'passcode' => '123',
        'happened_at' => now()->format('Y-m-d H:i:s'),
        'reason' => 'Test',
    ]);

    $request->validate(CancelLayawaySaleData::rules());

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'store_manager_id' => null,
        'passcode' => null,
        'happened_at' => null,
        'reason' => null,
    ]);

    $request->validate(CancelLayawaySaleData::rules());
})->throws(ValidationException::class);
