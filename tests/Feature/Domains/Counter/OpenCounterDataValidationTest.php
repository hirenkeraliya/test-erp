<?php

declare(strict_types=1);

use App\Domains\Counter\DataObjects\OpenCounterData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'validation works while opening a counter.',
    function (): void {
        $openCounterDetails = [
            'counter_id' => 1,
            'opening_balance' => 100,
        ];

        $request = new Request($openCounterDetails);

        $request->validate(OpenCounterData::rules());
        $this->assertTrue(true);
    }
);

test(
    'validation fails without opening balance',
    function (): void {
        $openCounterDetails = [
            'counter_id' => null,
            'opening_balance' => null,
        ];

        $request = new Request($openCounterDetails);

        $request->validate(OpenCounterData::rules());
    }
)->throws(ValidationException::class);
