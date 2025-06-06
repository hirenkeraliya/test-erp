<?php

declare(strict_types=1);

use App\Domains\Counter\DataObjects\CloseCounterData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'validation works while closing a counter.',
    function (): void {
        $closeCounterDetails = [
            'closing_balance' => 100,
            'mismatch_amount_reason' => null,
            'closed_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ];

        $request = new Request($closeCounterDetails);

        $request->validate(CloseCounterData::rules());
        $this->assertTrue(true);
    }
);

test(
    'validation fails without closing balance',
    function (): void {
        $closeCounterDetails = [
            'closing_balance' => null,
            'mismatch_amount_reason' => null,
            'closed_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ];

        $request = new Request($closeCounterDetails);

        $request->validate(CloseCounterData::rules());
    }
)->throws(ValidationException::class);
