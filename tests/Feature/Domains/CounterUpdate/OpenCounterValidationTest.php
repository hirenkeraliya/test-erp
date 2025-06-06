<?php

declare(strict_types=1);

use App\Domains\Counter\DataObjects\OpenCounterData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('open counter validation fails as expected', function (): void {
    $request = new Request([
        'counter_id' => '',
        'opening_balance' => '',
    ]);

    $request->validate(OpenCounterData::rules());
})->throws(ValidationException::class);

test('open counter validation pass', function (): void {
    $request = new Request([
        'counter_id' => '1',
        'opening_balance' => 10.00,
    ]);

    $request->validate(OpenCounterData::rules());

    $this->assertTrue(true);
});
