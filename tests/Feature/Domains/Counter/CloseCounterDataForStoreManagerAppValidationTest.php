<?php

declare(strict_types=1);

use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'validation works while closing a counter.',
    function (): void {
        $closeCounterDetails = [
            'closing_balance' => 100,
            'mismatch_amount_reason' => null,
        ];

        $request = new Request($closeCounterDetails);

        $request->validate(CloseCounterDataForStoreManager::rules());
        $this->assertTrue(true);
    }
);

test(
    'validation fails without closing balance',
    function (): void {
        $closeCounterDetails = [
            'closing_balance' => null,
            'mismatch_amount_reason' => null,
        ];

        $request = new Request($closeCounterDetails);

        $request->validate(CloseCounterDataForStoreManager::rules());
    }
)->throws(ValidationException::class);
